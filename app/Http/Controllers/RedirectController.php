<?php

namespace App\Http\Controllers;

use App\Models\Offers;
use App\Models\Staff;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function verifyCampaign(Request $request)
    {
        $validated = $request->validate([
            'o' => 'required|numeric|exists:offers,id',
            'p' => 'required|numeric|exists:staff,id',
        ]);
        try {

            $offer = Offers::find($validated['o']);
            $staff = Staff::find($validated['p']);

            if (!$offer || !$staff) {
                return response()->json(['status' => 'error', 'message' => 'Offer or query not found']);
            }

            if ($offer->status !== 'active') {
                return response()->json(['status' => 'error', 'message' => 'offer is not active']);
            }

            if ($staff->status !== 'active') {
                return response()->json(['status' => 'error', 'message' => 'publisher is not active']);
            }

            return response()->json(['status' => 'success', 'message' => 'Campaign verified']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to verify campaign: ' . $e->getMessage()]);
        }
    }
}
