<?php

namespace App\Http\Controllers\CRM\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\ClickData;
use App\Models\Offers; // Changed from Offers to follow Laravel naming convention
use App\Models\UsersOffers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Offers::with(['user', 'network.tracker', 'domain']);

        $domain_name = $request->query('domain_name');
        if ($domain_name) {
            $query->whereHas('domain', function ($query) use ($domain_name) {
                $query->where('name', $domain_name);
            });
        }

        $offers = match ($user->role) {
            'administrator' => $query->get(),
            'admin' => $this->getAdminOffers($query, $user),
            default => $this->getUsersOffers($query, $user)
        };

        // Get today's date 
        $today = now()->startOfDay();

        $offers = $offers->map(function ($offer) use ($user, $today) {
            $offer->link = $offer->domain->name . '/offer/' . $offer->id . '?ref=' . $user->id;

            // Get all clicks data using the ClickData model
            $clicksData = ClickData::where('offer_id', $offer->id)
                ->where('ref_id', $user->id)
                ->get();

            // Get today's clicks data
            $todayClicksData = $clicksData->filter(function ($click) use ($today) {
                return $click->created_at >= $today;
            });

            $offer->clicks = $todayClicksData->count();

            // Get conversions count
            $todayConversionsCount = $todayClicksData->where('converted', true)->count();

            $offer->conversions = $todayConversionsCount;

            // Calculate CVR (Conversion Rate)
            $offer->cvr = $offer->clicks > 0 ? round(($todayConversionsCount / $offer->clicks) * 100, 2) : 0;
            $offer->today_cvr = $offer->clicks > 0 ? round(($todayConversionsCount / $offer->clicks) * 100, 2) : 0;

            // Calculate revenue
            $offer->revenue = round($todayClicksData->where('converted', true)->sum('payout'), 2);

            // Add UTM sources to the offer
            if (!empty($offer->utm_sources)) {
                $utmSourceIds = json_decode($offer->utm_sources, true);
                if (is_array($utmSourceIds)) {
                    $offer->utm_sources_data = \App\Models\UtmSources::whereIn('id', $utmSourceIds)->get();
                }
            } else {
                $offer->utm_sources_data = [];
            }

            return $offer;
        });

        return response()->json([
            'data' => $offers,
            'pagination' => $this->getPaginationData($offers)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateOffer($request);
            $validated['user_id'] = $request->user()->id;

            DB::beginTransaction();

            $offer = Offers::create($validated);
            $this->handleUserAssignments($request->input('assigned_users'), $offer->id);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Offer created successfully',
                'offer' => $offer
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        try {
            $offer = Offers::with(['user', 'network', 'domain'])->findOrFail($id);
            $offer->assigned_users = $this->getAssignedUsers($id);

            return response()->json($offer);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Offer not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $offer = Offers::findOrFail($id);
            $validated = $this->validateOffer($request);
            $offer->update($validated);

            $this->updateUserAssignments($request->input('assigned_users'), $id);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Offer updated successfully',
                'offer' => $offer
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $offer = Offers::findOrFail($id);
            $this->removeAllUserAssignments($id);
            $offer->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Offer deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate offer request data
     * 
     * @param Request $request
     * @return array
     */
    private function validateOffer(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'network_id' => 'required|exists:networks,id',
            'domain_id' => 'required|exists:domains,id',
            'age' => 'required|string|max:50',
            'click_rate' => 'required|string|max:50',
            'details' => 'nullable|string',
            'countries' => 'required|string',
            'status' => 'required|in:active,paused,draft',
            'port' => 'required|string|max:50',
            'allow_multiple_clicks' => 'boolean',
            'proxy_check' => 'boolean',
            'vpn_allowed' => 'boolean',
            'tor_allowed' => 'boolean',
            'max_risk_score' => 'nullable|integer|min:0|max:100',
            'expires_at' => 'nullable|date',
            'daily_cap' => 'nullable|integer|min:0',
            'total_cap' => 'nullable|integer|min:0',
            'payout' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
            'targeting_rules' => 'nullable|array',
            'assigned_users' => 'nullable|string',
            'device_urls' => 'required|array',
            'device_urls.*.deviceType' => 'required|string|max:50',
            'device_urls.*.url' => 'required|string|url|max:2048',
            'utm_sources' => 'nullable|array',
            'utm_sources.*' => 'required|string|max:50',
        ]);
    }

    /**
     * Get offers for admin users
     */
    private function getAdminOffers($query, $user)
    {
        $userOffer = UsersOffers::where('user_id', $user->id)->first();

        return $query->where(function ($q) use ($user, $userOffer) {
            $q->where('user_id', $user->id);

            if ($userOffer && !empty($userOffer->offer_ids)) {
                $q->orWhereIn('id', explode(',', $userOffer->offer_ids));
            }
        })->get();
    }

    /**
     * Get offers for regular users
     */
    private function getUsersOffers($query, $user)
    {
        $userOffer = UsersOffers::where('user_id', $user->id)->first();

        if ($userOffer && !empty($userOffer->offer_ids)) {
            return $query->whereIn('id', explode(',', $userOffer->offer_ids))->get();
        }

        return collect([]);
    }

    /**
     * Handle user assignments for an offer
     */
    private function handleUserAssignments(?string $assignedUsers, int $offerId): void
    {
        if (empty($assignedUsers)) {
            return;
        }

        $userIds = explode(',', $assignedUsers);
        foreach ($userIds as $userId) {
            $this->assignOfferToUser($userId, $offerId);
        }
    }

    /**
     * Update user assignments for an offer
     */
    private function updateUserAssignments(?string $assignedUsers, int $offerId): void
    {
        $this->removeAllUserAssignments($offerId);
        $this->handleUserAssignments($assignedUsers, $offerId);
    }

    /**
     * Remove all user assignments for an offer
     */
    private function removeAllUserAssignments(int $offerId): void
    {
        $existingRecords = UsersOffers::whereRaw("FIND_IN_SET(?, offer_ids)", [$offerId])->get();

        foreach ($existingRecords as $record) {
            $offerIds = array_diff(
                array_filter(explode(',', $record->offer_ids)),
                [$offerId]
            );

            if (empty($offerIds)) {
                $record->delete();
            } else {
                $record->offer_ids = implode(',', $offerIds);
                $record->save();
            }
        }
    }

    /**
     * Get pagination data
     */
    private function getPaginationData($offers): array
    {
        return [
            'total' => $offers->count(),
            'per_page' => 15,
            'current_page' => 1,
            'last_page' => 1,
            'from' => 1,
            'to' => $offers->count()
        ];
    }

    /**
     * Get assigned users for an offer
     */
    private function getAssignedUsers(int $offerId): string
    {
        return UsersOffers::whereRaw("FIND_IN_SET(?, offer_ids)", [$offerId])
            ->pluck('user_id')
            ->join(',');
    }

    /**
     * Assign offer to user
     */
    private function assignOfferToUser(int $userId, int $offerId): void
    {
        $existingRecord = UsersOffers::where('user_id', $userId)->first();

        if ($existingRecord) {
            $offerIds = array_filter(explode(',', $existingRecord->offer_ids));
            if (!in_array($offerId, $offerIds)) {
                $offerIds[] = $offerId;
            }
            $offerIdsString = implode(',', array_unique($offerIds));
        } else {
            $offerIdsString = (string) $offerId;
        }

        UsersOffers::updateOrCreate(
            ['user_id' => $userId],
            ['offer_ids' => $offerIdsString]
        );
    }
}
