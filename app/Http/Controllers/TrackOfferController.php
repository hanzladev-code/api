<?php

namespace App\Http\Controllers;

use App\Models\ClickData;
use App\Models\Offers;
use App\Models\Tracker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class TrackOfferController extends Controller
{
    /**
     * Store a new click and track offer interaction
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'offer_id' => 'required|exists:offers,id',
            'ref' => 'required|exists:users,id',
            'utm_id' => 'nullable|exists:utm_sources,id',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'utm_term' => 'nullable|string|max:100',
            'utm_content' => 'nullable|string|max:100',
            'sub_id1' => 'nullable|string|max:100',
            'sub_id2' => 'nullable|string|max:100',
            'sub_id3' => 'nullable|string|max:100',
            'sub_id4' => 'nullable|string|max:100',
            'sub_id5' => 'nullable|string|max:100',
            'sub_id6' => 'nullable|string|max:100',
            'sub_id7' => 'nullable|string|max:100',
            'sub_id8' => 'nullable|string|max:100',
            'sub_id9' => 'nullable|string|max:100',
            'sub_id10' => 'nullable|string|max:100',
            'fingerprint' => 'nullable|string',
            'timezone_offset' => 'nullable|integer',
            'local_time' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'heading' => 'Error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the offer details
        $offer = Offers::findOrFail($request->offer_id)->load('network.tracker');

        $tracker = Tracker::findOrFail($offer->network->tracker);
        $param = $tracker->param;
        // The error was happening because we were returning the offer and then trying to return again
        // Now we're just retrieving the offer and continuing with the flow
        // Check if offer is expired
        if ($offer->isExpired()) {
            return response()->json([
                'status' => 'error',
                'heading' => 'Expired',
                'message' => 'This offer has expired',
                'redirect_url' => null
            ], 410);
        }

        // Check if offer has reached its caps
        if ($offer->hasDailyCapReached()) {
            return response()->json([
                'status' => 'error',
                'heading' => 'Cap Reached',
                'message' => 'This offer has reached its daily cap',
                'redirect_url' => null
            ], 429);
        }

        if ($offer->hasTotalCapReached()) {
            return response()->json([
                'status' => 'error',
                'heading' => 'Cap Reached',
                'message' => 'This offer has reached its total cap',
                'redirect_url' => null
            ], 429);
        }

        // Use real IP in production, fallback IP in other environments
        $ip = $request->ip();

        // Advanced IP detection to handle proxies and load balancers
        $realIp = $this->getRealIpAddress($request);

        // Retrieve geolocation data based on the IP address
        $locationData = Location::get($realIp);

        // Initialize Agent for better device detection
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        // Get more accurate device information
        $deviceType = $this->getDeviceType($agent);

        // Enhanced geolocation data
        $geoData = $this->getGeoLocationData($locationData);

        // Advanced IP fraud detection using ProxyCheck.io
        $ipQualityData = $this->getProxyCheckData($realIp);

        // Check for VPN/Proxy usage with multiple methods
        $vpnDetected = $this->detectVPN($realIp, $ipQualityData);
        $proxyDetected = $ipQualityData['proxy'] ?? false;
        $torDetected = $ipQualityData['tor'] ?? false;
        $todayClickCount = ClickData::whereDate('created_at', now()->toDateString())->count();

        // Generate a unique click ID based on date format and sequential click count
        $date = now()->format('dmY');
        $sequentialNumber = str_pad(($todayClickCount + 1), 4, '0', STR_PAD_LEFT);
        $clickId = $geoData['country'] . $date . $sequentialNumber;
        // Check if VPN/Proxy/Tor is allowed for this offer
        if (
            ($vpnDetected && !$offer->isVpnAllowed()) ||
            ($torDetected && !$offer->isTorAllowed()) ||
            ($proxyDetected && !$offer->proxy_check)
        ) {
            return response()->json([
                'status' => 'rejected',
                'heading' => 'Anti-Fraud',
                'message' => 'Traffic from VPN/Proxy/Tor is not allowed for this offer',
                'click_id' => $clickId
            ], 403);
        }

        // Calculate IP risk score (0-100, higher means riskier)
        $ipRiskScore = $this->calculateIPRiskScore($realIp, $geoData, $vpnDetected, $ipQualityData);

        // Check if IP risk score exceeds the maximum allowed for this offer
        if ($offer->max_risk_score && $ipRiskScore > $offer->max_risk_score) {
            return response()->json([
                'status' => 'rejected',
                'heading' => 'Anti-Fraud',
                'message' => 'Traffic risk score too high for this offer',
                'click_id' => $clickId
            ], 403);
        }

        // Get detailed OS information
        $osInfo = $this->getDetailedOSInfo($agent);

        // Generate realistic UTM parameters if utm_id exists but utm_source doesn't
        if ($request->has('utm_id') && !$request->has('utm_source')) {
            // Get the UTM source from the database based on utm_id
            $utmSource = \App\Models\UtmSources::find($request->utm_id);

            if ($utmSource) {
                // Use the slug from the UTM source as the utm_source value
                $sourceSlug = $utmSource->slug;

                // Define UTM parameter options based on the source slug
                $utmOptions = [
                    'facebook' => [
                        'medium' => ['cpc', 'social', 'paid', 'display'],
                        'campaign' => ['conversion', 'awareness', 'engagement', 'retargeting'],
                        'content' => ['image_ad', 'carousel_ad', 'video_ad', 'story_ad'],
                        'term' => ['interest_targeting', 'lookalike', 'custom_audience']
                    ],
                    'instagram' => [
                        'medium' => ['social', 'cpc', 'story', 'influencer'],
                        'campaign' => ['brand_awareness', 'engagement', 'conversion', 'app_installs'],
                        'content' => ['feed_post', 'story', 'reel', 'carousel'],
                        'term' => ['hashtag', 'interest', 'follower_targeting']
                    ],
                    'tiktok' => [
                        'medium' => ['video', 'cpc', 'social', 'influencer'],
                        'campaign' => ['brand_takeover', 'in_feed', 'hashtag_challenge', 'conversion'],
                        'content' => ['video_ad', 'spark_ad', 'branded_effect'],
                        'term' => ['interest', 'behavior', 'age_targeting']
                    ],
                    'snapchat' => [
                        'medium' => ['snap_ad', 'story_ad', 'filter', 'lens'],
                        'campaign' => ['awareness', 'consideration', 'conversion'],
                        'content' => ['single_image', 'video', 'collection_ad', 'ar_lens'],
                        'term' => ['age', 'interest', 'location']
                    ],
                    'whatsapp' => [
                        'medium' => ['message', 'status', 'broadcast', 'group'],
                        'campaign' => ['business_message', 'promotion', 'announcement', 'customer_service'],
                        'content' => ['text', 'image', 'video', 'document'],
                        'term' => ['direct', 'broadcast', 'group_message']
                    ],
                    'google' => [
                        'medium' => ['cpc', 'search', 'display', 'remarketing'],
                        'campaign' => ['brand', 'generic', 'competitor', 'display_network'],
                        'content' => ['text_ad', 'responsive_ad', 'banner', 'video'],
                        'term' => ['exact', 'phrase', 'broad', 'keyword']
                    ],
                    'youtube' => [
                        'medium' => ['video', 'pre_roll', 'mid_roll', 'discovery'],
                        'campaign' => ['brand_awareness', 'consideration', 'action', 'trueview'],
                        'content' => ['skippable', 'non_skippable', 'bumper', 'masthead'],
                        'term' => ['interest', 'topic', 'channel', 'keyword']
                    ],
                    'twitter' => [
                        'medium' => ['promoted_tweet', 'promoted_account', 'promoted_trend'],
                        'campaign' => ['followers', 'engagement', 'awareness', 'website_clicks'],
                        'content' => ['text', 'image', 'video', 'carousel'],
                        'term' => ['keyword', 'interest', 'follower', 'behavior']
                    ],
                    'pinterest' => [
                        'medium' => ['promoted_pin', 'shopping', 'video', 'carousel'],
                        'campaign' => ['awareness', 'consideration', 'conversion', 'catalog'],
                        'content' => ['standard_pin', 'video_pin', 'carousel', 'collection'],
                        'term' => ['interest', 'keyword', 'audience', 'placement']
                    ],
                    'linkedin' => [
                        'medium' => ['sponsored_content', 'message_ad', 'text_ad', 'dynamic'],
                        'campaign' => ['brand_awareness', 'website_visits', 'engagement', 'lead_generation'],
                        'content' => ['single_image', 'carousel', 'video', 'document'],
                        'term' => ['job_title', 'company', 'skills', 'industry']
                    ]
                ];

                // If the source slug exists in our options, use it; otherwise use a default or the name
                $sourceOptions = $utmOptions[$sourceSlug] ?? $utmOptions['google'];

                // Generate truly random UTM parameters with variations
                $randomSeed = microtime(true) . $realIp . rand(1000, 9999);

                // Select random values for each parameter based on the source
                $mediumOptions = $sourceOptions['medium'];
                $campaignOptions = $sourceOptions['campaign'];
                $contentOptions = $sourceOptions['content'];
                $termOptions = $sourceOptions['term'];

                // Add random variations to make parameters more unique
                $campaignSuffix = rand(100, 999);
                $contentSuffix = substr(md5($randomSeed), 0, 5);

                // Set the UTM parameters with randomization
                $request->merge([
                    'utm_source' => $sourceSlug,
                    'utm_medium' => $mediumOptions[array_rand($mediumOptions)],
                    'utm_campaign' => $campaignOptions[array_rand($campaignOptions)] . '_' . $campaignSuffix,
                    'utm_content' => $contentOptions[array_rand($contentOptions)] . '_' . $contentSuffix,
                    'utm_term' => $termOptions[array_rand($termOptions)]
                ]);

                // Cache the parameters briefly to avoid identical parameters for same IP in short timeframe
                $cacheKey = 'utm_params_' . md5($realIp);
                if (Cache::has($cacheKey)) {
                    // If we've generated parameters for this IP recently, slightly modify them
                    $request->merge([
                        'utm_content' => $request->utm_content . '_v' . rand(2, 9)
                    ]);
                }
                Cache::put($cacheKey, true, now()->addMinutes(5));
            }
        }

        // Detect traffic source (where the user came from)
        $trafficSource = $this->detectTrafficSource($request);

        // Collect all sub IDs
        $subIds = $this->collectSubIds($request);

        // Collect all UTM parameters
        $utmParams = $this->collectUtmParams($request);

        // Fraud detection metrics
        $fraudMetrics = [
            'vpn_detected' => $vpnDetected,
            'ip_risk_score' => $ipRiskScore,
            'proxy_detected' => $proxyDetected,
            'tor_detected' => $torDetected,
            'bot_likelihood' => $ipQualityData['bot_score'] ?? 0,
            'fraud_score' => $ipQualityData['risk'] ?? 0,
        ];

        // Get the referring user
        $referrer = User::find($request->ref);

        // Check if multiple clicks are allowed from the same IP
        if (!$offer->allow_multiple_clicks) {
            $recentClick = $this->checkRecentClicks($offer->id, $realIp);
            if ($recentClick) {
                return response()->json([
                    'status' => 'duplicate',
                    'heading' => 'Anti-Fraud',
                    'message' => 'Duplicate click detected',
                    'click_id' => $recentClick->click_id
                ], 409);
            }
        }

        // Collect visitor information
        $data = $this->collectVisitorData(
            $request,
            $ip,
            $realIp,
            $agent,
            $geoData,
            $deviceType,
            $osInfo,
            $fraudMetrics,
            $ipQualityData,
            $trafficSource,
            $subIds,
            $utmParams,
            $clickId
        );
        // Save the click data to the database
        try {
            $clickData = ClickData::create([
                'click_id' => $clickId,
                'offer_id' => $offer->id,
                'ref_id' => $referrer->id,
                'ip' => $ip,
                'real_ip' => $realIp,
                'user_agent' => $request->userAgent(),
                'device_type' => $deviceType,
                'browser' => $agent->browser(),
                'platform' => $agent->platform(),
                'country' => $geoData['country'],
                'city' => $geoData['city'],
                'region' => $geoData['region'],
                'utm_source' => $utmParams['utm_source'],
                'utm_medium' => $utmParams['utm_medium'],
                'utm_campaign' => $utmParams['utm_campaign'],
                'utm_term' => $utmParams['utm_term'],
                'utm_content' => $utmParams['utm_content'],
                'sub_id1' => $subIds['sub_id1'] ?? null,
                'sub_id2' => $subIds['sub_id2'] ?? null,
                'sub_id3' => $subIds['sub_id3'] ?? null,
                'sub_id4' => $subIds['sub_id4'] ?? null,
                'sub_id5' => $subIds['sub_id5'] ?? null,
                'sub_id6' => $subIds['sub_id6'] ?? null,
                'sub_id7' => $subIds['sub_id7'] ?? null,
                'sub_id8' => $subIds['sub_id8'] ?? null,
                'sub_id9' => $subIds['sub_id9'] ?? null,
                'sub_id10' => $subIds['sub_id10'] ?? null,
                'vpn_detected' => $vpnDetected,
                'proxy_detected' => $proxyDetected,
                'tor_detected' => $torDetected,
                'ip_risk_score' => $ipRiskScore,
                'fraud_score' => $fraudMetrics['fraud_score'],
                'metadata' => $data,
                'status' => 'pending'
            ]);

            // Determine the redirect URL based on device type and offer settings
            // Find the matching device URL from the offer's device_urls
            $redirectUrl = null;
            foreach ($offer->device_urls as $deviceUrl) {
                if ($deviceUrl['deviceType'] === $deviceType) {
                    $redirectUrl = $deviceUrl['url'];
                    break;
                }
            }

            // If no matching device URL found, fall back to default URL
            if (!$redirectUrl) {
                $redirectUrl = $this->getRedirectUrl($offer, $deviceType, $data);
            }

            // Cache the click data for quick lookups (useful for conversion tracking)
            $this->cacheClickData($clickId, $clickData->id);

            // Build base redirect URL with the click ID parameter
            $baseRedirectUrl = $redirectUrl;

            // Initialize query parameters array with the tracker parameter
            $queryParams = [$param => $clickId];

            // Add all request parameters to the redirect URL except offer_id and ref
            foreach ($request->except(['offer_id', 'ref']) as $key => $value) {
                if (!empty($value)) {
                    $queryParams[$key] = $value;
                }
            }

            // Build the final URL with all parameters properly appended
            $finalRedirectUrl = $baseRedirectUrl . (parse_url($baseRedirectUrl, PHP_URL_QUERY) ? '&' : '?') . http_build_query($queryParams);

            // Return success response with click ID and redirect URL
            return response()->json([
                'status' => 'success',
                'heading' => 'Success',
                'message' => 'Click tracked successfully',
                'redirect_url' => $finalRedirectUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'heading' => 'Error',
                'message' => 'Failed to track click',
                'error' => app()->environment('production') ? 'Server error' : $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get metadata for an offer
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMetaData(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'offer_id' => 'required|exists:offers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $offer = Offers::findOrFail($request->offer_id);
        $metaData = [
            'title' => $offer->name,
            'description' => $offer->details,
            'image' => $offer->image,
            'url' => $offer->url,
            'keywords' => $offer->keywords,
            'author' => $offer->author,
            'publisher' => $offer->publisher,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Meta data retrieved successfully',
            'meta_data' => $metaData
        ], 200);
    }
    /**
     * Get device type from Agent
     * 
     * @param Agent $agent
     * @return string
     */
    private function getDeviceType(Agent $agent): string
    {
        if ($agent->isDesktop()) {
            return 'desktop';
        } elseif ($agent->isTablet()) {
            return 'tablet';
        } elseif ($agent->isPhone()) {
            return 'mobile';
        } elseif ($agent->isRobot()) {
            return 'robot';
        }

        return 'unknown';
    }

    /**
     * Get geolocation data from Location
     * 
     * @param mixed $locationData
     * @return array
     */
    private function getGeoLocationData($locationData): array
    {
        return [
            'country' => $locationData ? $locationData->countryCode : null,
            'country_name' => $locationData ? $locationData->countryName : null,
            'city' => $locationData ? $locationData->cityName : null,
            'region' => $locationData ? $locationData->regionName : null,
            'latitude' => $locationData ? $locationData->latitude : null,
            'longitude' => $locationData ? $locationData->longitude : null,
            'timezone' => $locationData ? $locationData->timezone : null,
            'isp' => $locationData ? ($locationData->isp ?? null) : null,
            'organization' => $locationData ? ($locationData->organization ?? null) : null,
            'asn' => $locationData ? ($locationData->asn ?? null) : null,
        ];
    }

    /**
     * Collect all sub IDs from request
     * 
     * @param Request $request
     * @return array
     */
    private function collectSubIds(Request $request): array
    {
        $subIds = [];
        for ($i = 1; $i <= 10; $i++) {
            $key = "sub_id{$i}";
            if ($request->has($key)) {
                $subIds[$key] = $request->input($key);
            }
        }
        return $subIds;
    }

    /**
     * Collect all UTM parameters from request
     * 
     * @param Request $request
     * @return array
     */
    private function collectUtmParams(Request $request): array
    {
        return [
            'utm_source' => $request->input('utm_source'),
            'utm_medium' => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_term' => $request->input('utm_term'),
            'utm_content' => $request->input('utm_content'),
        ];
    }

    /**
     * Check for recent clicks from the same IP for this offer
     * 
     * @param int $offerId
     * @param string $ip
     * @return ClickData|null
     */
    private function checkRecentClicks(int $offerId, string $ip): ?ClickData
    {
        // Look for clicks in the last 24 hours from the same IP
        return ClickData::where('offer_id', $offerId)
            ->where('real_ip', $ip)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();
    }

    /**
     * Cache click data for quick lookups
     * 
     * @param string $clickId
     * @param int $dbId
     * @return void
     */
    private function cacheClickData(string $clickId, int $dbId): void
    {
        // Cache for 30 days (typical conversion window)
        Cache::put("click:{$clickId}", $dbId, now()->addDays(30));
    }

    /**
     * Get the appropriate redirect URL based on device type and offer settings
     * 
     * @param Offers $offer
     * @param string $deviceType
     * @param array $data
     * @return string|null
     */
    private function getRedirectUrl(Offers $offer, string $deviceType, array $data): ?string
    {
        if (!isset($offer->device_urls) || !is_array($offer->device_urls)) {
            return null;
        }

        // Get URL based on device type
        $url = $offer->device_urls[$deviceType] ?? $offer->device_urls['default'] ?? null;

        // If no URL found, try fallback
        if (!$url) {
            if ($deviceType === 'mobile' || $deviceType === 'tablet') {
                $url = $offer->device_urls['mobile'] ?? $offer->device_urls['default'] ?? null;
            } else {
                $url = $offer->device_urls['desktop'] ?? $offer->device_urls['default'] ?? null;
            }
        }

        // Replace placeholders in URL with actual values
        if ($url) {
            $url = $this->replacePlaceholders($url, $data);
        }

        return $url;
    }

    /**
     * Replace placeholders in URL with actual values
     * 
     * @param string $url
     * @param array $data
     * @return string
     */
    private function replacePlaceholders(string $url, array $data): string
    {
        $replacements = [
            '{click_id}' => $data['click_id'] ?? '',
            '{offer_id}' => $data['offer_id'] ?? '',
            '{ref}' => $data['ref'] ?? '',
            '{ip}' => $data['ip'] ?? '',
            '{country}' => $data['geo']['country'] ?? '',
            '{city}' => $data['geo']['city'] ?? '',
            '{device}' => $data['device_type'] ?? '',
            '{os}' => $data['platform'] ?? '',
            '{browser}' => $data['browser'] ?? '',
            '{utm_source}' => $data['utm_params']['utm_source'] ?? '',
            '{utm_medium}' => $data['utm_params']['utm_medium'] ?? '',
            '{utm_campaign}' => $data['utm_params']['utm_campaign'] ?? '',
        ];

        // Replace sub IDs
        for ($i = 1; $i <= 10; $i++) {
            $key = "sub_id{$i}";
            $replacements["{{$key}}"] = $data['sub_ids'][$key] ?? '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $url);
    }

    /**
     * Collect all visitor data
     * 
     * @param Request $request
     * @param string $ip
     * @param string $realIp
     * @param Agent $agent
     * @param array $geoData
     * @param string $deviceType
     * @param array $osInfo
     * @param array $fraudMetrics
     * @param array $ipQualityData
     * @param array $trafficSource
     * @param array $subIds
     * @param array $utmParams
     * @param string $clickId
     * @return array
     */
    private function collectVisitorData(
        Request $request,
        string $ip,
        string $realIp,
        Agent $agent,
        array $geoData,
        string $deviceType,
        array $osInfo,
        array $fraudMetrics,
        array $ipQualityData,
        array $trafficSource,
        array $subIds,
        array $utmParams,
        string $clickId
    ): array {
        return [
            // Click identifier
            'click_id' => $clickId,

            // Basic request data
            'ip' => $ip,
            'real_ip' => $realIp,
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->url(),
            'full_url' => $request->fullUrl(),

            // Headers
            'accept' => $request->header('Accept'),
            'accept_language' => $request->header('Accept-Language'),
            'referer' => $request->header('Referer'),
            'origin' => $request->header('Origin'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'true_client_ip' => $request->header('True-Client-IP'),

            // Enhanced geolocation data
            'geo' => $geoData,

            // Enhanced device information
            'device_type' => $deviceType,
            'device' => $agent->device(),
            'platform' => $agent->platform(),
            'platform_version' => $agent->version($agent->platform()),
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'is_mobile' => $agent->isMobile(),
            'is_tablet' => $agent->isTablet(),
            'is_desktop' => $agent->isDesktop(),
            'is_robot' => $agent->isRobot(),

            // Detailed OS information
            'os_info' => $osInfo,

            // Security and risk assessment
            'fraud_metrics' => $fraudMetrics,
            'ip_quality_data' => $ipQualityData,

            // Traffic source information
            'traffic_source' => $trafficSource,

            // Request parameters
            'ref' => $request->ref,
            'sub_ids' => $subIds,
            'utm_params' => $utmParams,
            'offer_id' => $request->offer_id,
            'all_parameters' => $request->all(),

            // Cookies
            'cookies' => $request->cookies->all(),

            // Browser fingerprinting data
            'fingerprint' => $request->input('fingerprint'),

            // Time data
            'timestamp' => now()->timestamp,
            'timezone_offset' => $request->input('timezone_offset'),
            'local_time' => $request->input('local_time'),
        ];
    }

    /**
     * Record a conversion for a click
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversion(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'click_id' => 'required|uuid',
            'amount' => 'nullable|numeric',
            'revenue' => 'nullable|numeric',
            'transaction_id' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Try to find the click from cache first (faster)
        $clickDataId = Cache::get("click:{$request->click_id}");

        // If not in cache, try to find it in the database
        if (!$clickDataId) {
            $clickData = ClickData::where('click_id', $request->click_id)->first();

            if (!$clickData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Click not found',
                ], 404);
            }

            $clickDataId = $clickData->id;
        } else {
            // Get the click data from database using the cached ID
            $clickData = ClickData::find($clickDataId);

            if (!$clickData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Click not found',
                ], 404);
            }
        }

        // Check if already converted
        if ($clickData->converted) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Click already converted',
                'click_id' => $clickData->click_id,
                'converted_at' => $clickData->converted_at
            ], 200);
        }

        try {
            // Get the offer
            $offer = $clickData->offer;

            // Set payout and revenue
            $payout = $request->amount ?? $offer->payout ?? 0;
            $revenue = $request->revenue ?? $offer->revenue ?? 0;

            // Mark the click as converted
            $clickData->markAsConverted($payout, $revenue);

            // Update metadata with conversion info
            $metadata = $clickData->metadata ?? [];
            $metadata['conversion'] = [
                'timestamp' => now()->timestamp,
                'transaction_id' => $request->transaction_id,
                'amount' => $payout,
                'revenue' => $revenue,
                'status' => $request->status ?? 'completed',
                'ip' => $request->ip(),
            ];

            $clickData->metadata = $metadata;
            $clickData->save();

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Conversion recorded successfully',
                'click_id' => $clickData->click_id,
                'offer_id' => $clickData->offer_id,
                'ref_id' => $clickData->ref_id,
                'payout' => $payout,
                'revenue' => $revenue,
                'converted_at' => $clickData->converted_at
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error recording conversion: ' . $e->getMessage(), [
                'click_id' => $request->click_id,
                'trace' => $e->getTraceAsString()
            ]);

            // Return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record conversion',
                'error' => app()->environment('production') ? 'Server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for a specific offer
     *
     * @param Request $request
     * @param int $offerId
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get a list of all clicks with filtering options
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Build query with filters
        $query = ClickData::query();

        // Filter by offer ID
        if ($request->has('offer_id')) {
            $query->where('offer_id', $request->offer_id);
        }

        // Filter by referrer ID
        if ($request->has('ref_id')) {
            $query->where('ref_id', $request->ref_id);
        }

        // Filter by country
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }

        // Filter by device type
        if ($request->has('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        // Filter by conversion status
        if ($request->has('converted')) {
            $query->where('converted', filter_var($request->converted, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Filter by fraud metrics
        if ($request->has('vpn_detected')) {
            $query->where('vpn_detected', filter_var($request->vpn_detected, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('proxy_detected')) {
            $query->where('proxy_detected', filter_var($request->proxy_detected, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('min_risk_score')) {
            $query->where('ip_risk_score', '>=', $request->min_risk_score);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $clicks = $query->with(['offer', 'referrer'])
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $clicks
        ]);
    }

    /**
     * Get details for a specific click
     *
     * @param string $clickId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $clickId)
    {
        $click = ClickData::with(['offer', 'referrer'])
            ->where('click_id', $clickId)
            ->first();

        if (!$click) {
            return response()->json([
                'status' => 'error',
                'message' => 'Click not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $click
        ]);
    }

    /**
     * Get statistics for a specific offer
     *
     * @param Request $request
     * @param int $offerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function offerStats(Request $request, int $offerId)
    {
        // Validate the offer exists
        $offer = Offers::find($offerId);
        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Offer not found'
            ], 404);
        }

        // Get date range parameters
        $startDate = $request->input('start_date', now()->subDays(30)->startOfDay()->toDateTimeString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());

        // Get clicks within date range
        $clicks = ClickData::where('offer_id', $offerId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Get conversions within date range
        $conversions = ClickData::where('offer_id', $offerId)
            ->where('converted', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Calculate statistics
        $totalClicks = $clicks->count();
        $totalConversions = $conversions->count();
        $conversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
        $totalPayout = $conversions->sum('payout');
        $totalRevenue = $conversions->sum('revenue');
        $profit = $totalRevenue - $totalPayout;
        $roi = $totalPayout > 0 ? ($profit / $totalPayout) * 100 : 0;

        // Group by country
        $clicksByCountry = $clicks->groupBy('country')->map->count();
        $conversionsByCountry = $conversions->groupBy('country')->map->count();

        // Group by device type
        $clicksByDevice = $clicks->groupBy('device_type')->map->count();
        $conversionsByDevice = $conversions->groupBy('device_type')->map->count();

        // Group by referrer
        $clicksByReferrer = $clicks->groupBy('ref_id')->map->count();
        $conversionsByReferrer = $conversions->groupBy('ref_id')->map->count();

        // Get daily stats for chart
        $dailyStats = [];
        $startDateTime = new \DateTime($startDate);
        $endDateTime = new \DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($startDateTime, $interval, $endDateTime);

        foreach ($dateRange as $date) {
            $day = $date->format('Y-m-d');
            $dayStart = $day . ' 00:00:00';
            $dayEnd = $day . ' 23:59:59';

            $dayClicks = $clicks->whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $dayConversions = $conversions->whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $dayRevenue = $conversions->whereBetween('created_at', [$dayStart, $dayEnd])->sum('revenue');
            $dayPayout = $conversions->whereBetween('created_at', [$dayStart, $dayEnd])->sum('payout');

            $dailyStats[] = [
                'date' => $day,
                'clicks' => $dayClicks,
                'conversions' => $dayConversions,
                'revenue' => $dayRevenue,
                'payout' => $dayPayout,
                'profit' => $dayRevenue - $dayPayout,
            ];
        }

        // Return statistics
        return response()->json([
            'status' => 'success',
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'status' => $offer->status,
                'expires_at' => $offer->expires_at,
                'daily_cap' => $offer->daily_cap,
                'total_cap' => $offer->total_cap,
            ],
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_clicks' => $totalClicks,
                'total_conversions' => $totalConversions,
                'conversion_rate' => round($conversionRate, 2),
                'total_payout' => $totalPayout,
                'total_revenue' => $totalRevenue,
                'profit' => $profit,
                'roi' => round($roi, 2),
            ],
            'by_country' => [
                'clicks' => $clicksByCountry,
                'conversions' => $conversionsByCountry,
            ],
            'by_device' => [
                'clicks' => $clicksByDevice,
                'conversions' => $conversionsByDevice,
            ],
            'by_referrer' => [
                'clicks' => $clicksByReferrer,
                'conversions' => $conversionsByReferrer,
            ],
            'daily_stats' => $dailyStats,
        ]);
    }

    /**
     * Get the real IP address considering various headers and proxies
     * 
     * @param Request $request
     * @return string
     */
    private function getRealIpAddress(Request $request)
    {
        // Check for CloudFlare IP
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Check for True-Client-IP header
        if ($request->header('True-Client-IP')) {
            return $request->header('True-Client-IP');
        }

        // Check X-Forwarded-For header
        if ($request->header('X-Forwarded-For')) {
            $xForwardedFor = explode(',', $request->header('X-Forwarded-For'));
            return trim($xForwardedFor[0]);
        }

        // Check X-Real-IP header
        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }

        // Fallback to the standard IP method
        return app()->environment('production')
            ? $request->ip()
            : env('FALLBACK_IP_ADDRESS', '169.197.85.173');
    }

    /**
     * Get IP quality and fraud data from ProxyCheck.io API
     * 
     * @param string $ip
     * @return array
     */
    private function getProxyCheckData($ip)
    {
        try {
            $apiKey = env('PROXYCHECK_API_KEY') ?? '33812r-47v9z6-b5i430-i83158';

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => 'API key not configured'
                ];
            }

            // Make request to ProxyCheck.io API
            $response = Http::get("https://proxycheck.io/v2/{$ip}", [
                'key' => $apiKey,
                'vpn' => 1,
                'asn' => 1,
                'risk' => 1,
                'port' => 1,
                'seen' => 1,
                'days' => 7,
                'tag' => 'visitor-tracking'
            ]);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['status']) && $result['status'] === 'ok' && isset($result[$ip])) {
                    $ipData = $result[$ip];

                    return [
                        'success' => true,
                        'proxy' => $ipData['proxy'] === 'yes',
                        'vpn' => $ipData['type'] === 'VPN',
                        'tor' => $ipData['type'] === 'TOR',
                        'risk' => (int) $ipData['risk'],
                        'country' => $ipData['country'] ?? null,
                        'isp' => $ipData['isp'] ?? null,
                        'asn' => $ipData['asn'] ?? null,
                        'organization' => $ipData['provider'] ?? null,
                        'is_crawler' => isset($ipData['type']) && $ipData['type'] === 'CRAWLER',
                        'bot_score' => isset($ipData['type']) && in_array($ipData['type'], ['CRAWLER', 'BOT']) ? 100 : 0,
                        'type' => $ipData['type'] ?? null,
                        'port' => $ipData['port'] ?? null,
                        'last_seen' => $ipData['last seen'] ?? null,
                        'attack_history' => $ipData['attack history'] ?? null,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to get IP quality data from ProxyCheck.io'
            ];
        } catch (\Exception $e) {
            // Fallback to alternative method if API fails
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'fallback_data' => $this->getFallbackIPData($ip)
            ];
        }
    }

    /**
     * Get fallback IP data when API fails
     * 
     * @param string $ip
     * @return array
     */
    private function getFallbackIPData($ip)
    {
        try {
            // Try free API as fallback
            $response = Http::get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'country' => $data['country_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'isp' => $data['org'] ?? null,
                    'asn' => $data['asn'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                ];
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Detect the traffic source (where the user came from)
     * 
     * @param Request $request
     * @return array
     */
    private function detectTrafficSource(Request $request)
    {
        $source = [
            'type' => 'direct',
            'name' => 'Direct Visit',
            'medium' => 'none',
            'campaign' => null,
            'details' => []
        ];

        // Check UTM parameters first (most reliable)
        if ($request->has('utm_source')) {
            $source['type'] = 'campaign';
            $source['name'] = $request->input('utm_source');
            $source['medium'] = $request->input('utm_medium', 'unknown');
            $source['campaign'] = $request->input('utm_campaign');
            $source['details']['term'] = $request->input('utm_term');
            $source['details']['content'] = $request->input('utm_content');
        }
        // Check referer header
        else if ($referer = $request->header('Referer')) {
            $parsedUrl = parse_url($referer);
            $host = $parsedUrl['host'] ?? '';

            // Social media detection
            $socialMedia = $this->detectSocialMedia($host, $referer);
            if ($socialMedia) {
                $source['type'] = 'social';
                $source['name'] = $socialMedia;
                $source['medium'] = 'social';
            }
            // Search engine detection
            else if ($searchEngine = $this->detectSearchEngine($host, $referer)) {
                $source['type'] = 'organic';
                $source['name'] = $searchEngine;
                $source['medium'] = 'search';
                $source['details']['keywords'] = $this->extractSearchQuery($referer, $searchEngine);
            }
            // Email client detection
            else if ($emailClient = $this->detectEmailClient($referer, $request->userAgent())) {
                $source['type'] = 'email';
                $source['name'] = $emailClient;
                $source['medium'] = 'email';
            }
            // Messaging app detection
            else if ($messagingApp = $this->detectMessagingApp($referer, $request->userAgent())) {
                $source['type'] = 'messaging';
                $source['name'] = $messagingApp;
                $source['medium'] = 'chat';
            }
            // Other website
            else {
                $source['type'] = 'referral';
                $source['name'] = $host;
                $source['medium'] = 'referral';
            }

            $source['details']['full_referer'] = $referer;
        }

        // Check for app-specific user agents
        if ($appSource = $this->detectAppSource($request->userAgent())) {
            $source['type'] = 'app';
            $source['name'] = $appSource;
            $source['medium'] = 'app';
        }

        return $source;
    }

    /**
     * Detect if traffic is coming from a social media platform
     * 
     * @param string $host
     * @param string $referer
     * @return string|null
     */
    private function detectSocialMedia($host, $referer)
    {
        $socialPlatforms = [
            'facebook.com' => 'Facebook',
            'instagram.com' => 'Instagram',
            'twitter.com' => 'Twitter',
            'x.com' => 'Twitter',
            't.co' => 'Twitter',
            'linkedin.com' => 'LinkedIn',
            'lnkd.in' => 'LinkedIn',
            'pinterest.com' => 'Pinterest',
            'reddit.com' => 'Reddit',
            'youtube.com' => 'YouTube',
            'tiktok.com' => 'TikTok',
            'snapchat.com' => 'Snapchat',
            'tumblr.com' => 'Tumblr',
            'quora.com' => 'Quora',
            'vk.com' => 'VKontakte',
            'weibo.com' => 'Weibo',
            'whatsapp.com' => 'WhatsApp',
            'wa.me' => 'WhatsApp',
            'telegram.org' => 'Telegram',
            't.me' => 'Telegram',
            'discord.com' => 'Discord',
            'medium.com' => 'Medium'
        ];

        foreach ($socialPlatforms as $domain => $platform) {
            if (strpos($host, $domain) !== false) {
                return $platform;
            }
        }

        return null;
    }

    /**
     * Detect if traffic is coming from a search engine
     * 
     * @param string $host
     * @param string $referer
     * @return string|null
     */
    private function detectSearchEngine($host, $referer)
    {
        $searchEngines = [
            'google' => 'Google',
            'bing.com' => 'Bing',
            'yahoo.com' => 'Yahoo',
            'duckduckgo.com' => 'DuckDuckGo',
            'baidu.com' => 'Baidu',
            'yandex' => 'Yandex',
            'ask.com' => 'Ask',
            'aol.com' => 'AOL',
            'ecosia.org' => 'Ecosia',
            'qwant.com' => 'Qwant',
            'search.brave.com' => 'Brave Search'
        ];

        foreach ($searchEngines as $domain => $engine) {
            if (strpos($host, $domain) !== false) {
                return $engine;
            }
        }

        return null;
    }

    /**
     * Extract search query from referer URL
     * 
     * @param string $referer
     * @param string $searchEngine
     * @return string|null
     */
    private function extractSearchQuery($referer, $searchEngine)
    {
        $parsedUrl = parse_url($referer);
        if (!isset($parsedUrl['query'])) {
            return null;
        }

        parse_str($parsedUrl['query'], $queryParams);

        $queryParams = array_change_key_case($queryParams, CASE_LOWER);

        // Different search engines use different query parameter names
        $queryParamMap = [
            'Google' => ['q', 'query'],
            'Bing' => ['q', 'search'],
            'Yahoo' => ['p', 'q'],
            'DuckDuckGo' => ['q'],
            'Baidu' => ['wd', 'word'],
            'Yandex' => ['text'],
            'default' => ['q', 'query', 'search', 'p', 'text']
        ];

        $possibleParams = $queryParamMap[$searchEngine] ?? $queryParamMap['default'];

        foreach ($possibleParams as $param) {
            if (isset($queryParams[$param])) {
                return $queryParams[$param];
            }
        }

        return null;
    }

    /**
     * Detect if traffic is coming from an email client
     * 
     * @param string $referer
     * @param string $userAgent
     * @return string|null
     */
    private function detectEmailClient($referer, $userAgent)
    {
        $emailClients = [
            'mail.google.com' => 'Gmail',
            'outlook.live.com' => 'Outlook',
            'outlook.office365.com' => 'Outlook',
            'mail.yahoo.com' => 'Yahoo Mail',
            'mail.proton.me' => 'ProtonMail',
            'aol.com/mail' => 'AOL Mail'
        ];

        $parsedUrl = parse_url($referer);
        $host = $parsedUrl['host'] ?? '';

        foreach ($emailClients as $domain => $client) {
            if (strpos($host, $domain) !== false) {
                return $client;
            }
        }

        // Check for email client user agents
        if (strpos($userAgent, 'Thunderbird') !== false) {
            return 'Thunderbird';
        }
        if (strpos($userAgent, 'Microsoft Outlook') !== false) {
            return 'Outlook';
        }
        if (strpos($userAgent, 'Apple Mail') !== false) {
            return 'Apple Mail';
        }

        return null;
    }

    /**
     * Detect if traffic is coming from a messaging app
     * 
     * @param string $referer
     * @param string $userAgent
     * @return string|null
     */
    private function detectMessagingApp($referer, $userAgent)
    {
        // Check referer for messaging apps
        $messagingApps = [
            'web.whatsapp.com' => 'WhatsApp',
            'web.telegram.org' => 'Telegram',
            'discord.com' => 'Discord',
            'teams.microsoft.com' => 'Microsoft Teams',
            'slack.com' => 'Slack',
            'messenger.com' => 'Facebook Messenger'
        ];

        $parsedUrl = parse_url($referer);
        $host = $parsedUrl['host'] ?? '';

        foreach ($messagingApps as $domain => $app) {
            if (strpos($host, $domain) !== false) {
                return $app;
            }
        }

        // Check for app-specific user agent patterns
        if (strpos($userAgent, 'WhatsApp') !== false) {
            return 'WhatsApp';
        }
        if (strpos($userAgent, 'Telegram') !== false) {
            return 'Telegram';
        }
        if (strpos($userAgent, 'FB_IAB') !== false || strpos($userAgent, 'FBAN') !== false) {
            return 'Facebook';
        }

        return null;
    }

    /**
     * Detect if traffic is coming from a specific app
     * 
     * @param string $userAgent
     * @return string|null
     */
    private function detectAppSource($userAgent)
    {
        // Mobile apps often have specific user agent strings
        if (strpos($userAgent, 'Instagram') !== false) {
            return 'Instagram App';
        }
        if (strpos($userAgent, 'FB_IAB') !== false || strpos($userAgent, 'FBAN') !== false) {
            return 'Facebook App';
        }
        if (strpos($userAgent, 'Twitter') !== false || strpos($userAgent, 'TweetDeck') !== false) {
            return 'Twitter App';
        }
        if (strpos($userAgent, 'LinkedIn') !== false) {
            return 'LinkedIn App';
        }
        if (strpos($userAgent, 'Pinterest') !== false) {
            return 'Pinterest App';
        }
        if (strpos($userAgent, 'Snapchat') !== false) {
            return 'Snapchat App';
        }
        if (strpos($userAgent, 'TikTok') !== false) {
            return 'TikTok App';
        }

        return null;
    }

    /**
     * Detect if the IP is likely using a VPN or proxy
     * 
     * @param string $ip
     * @param array $ipQualityData
     * @return bool
     */
    private function detectVPN($ip, $ipQualityData)
    {
        // First check ProxyCheck.io data if available
        if (isset($ipQualityData['success']) && $ipQualityData['success']) {
            if (isset($ipQualityData['vpn']) && $ipQualityData['vpn']) {
                return true;
            }

            if (isset($ipQualityData['proxy']) && $ipQualityData['proxy']) {
                return true;
            }

            if (isset($ipQualityData['tor']) && $ipQualityData['tor']) {
                return true;
            }
        }

        // Known VPN/proxy IP ranges (example)
        $knownVpnRanges = [
            '103.21.244.0/22',
            '104.16.0.0/12',
            '162.158.0.0/15',
            '172.64.0.0/13',
            '173.245.48.0/20',
            '188.114.96.0/20',
            '190.93.240.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '199.27.128.0/21',
        ];

        foreach ($knownVpnRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        // Check for common VPN ports in use
        // This would require additional server configuration

        // Check for hostname patterns that suggest VPN/proxy
        $hostInfo = gethostbyaddr($ip);
        $vpnKeywords = ['vpn', 'proxy', 'tor', 'exit', 'node', 'relay', 'tunnel'];

        foreach ($vpnKeywords as $keyword) {
            if (stripos($hostInfo, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is in a given range
     * 
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private function ipInRange($ip, $range)
    {
        // Simple implementation for CIDR notation
        if (strpos($range, '/') !== false) {
            list($subnet, $bits) = explode('/', $range);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) == $subnet;
        }

        return $ip === $range;
    }

    /**
     * Calculate a risk score for the IP based on various factors
     * 
     * @param string $ip
     * @param array $geoData
     * @param bool $vpnDetected
     * @param array $ipQualityData
     * @return int
     */
    private function calculateIPRiskScore($ip, $geoData, $vpnDetected, $ipQualityData)
    {
        // If we have ProxyCheck.io data, use their risk score as a base
        if (isset($ipQualityData['success']) && $ipQualityData['success'] && isset($ipQualityData['risk'])) {
            return $ipQualityData['risk'];
        }

        // Otherwise calculate our own score
        $score = 0;

        // VPN detection adds significant risk
        if ($vpnDetected) {
            $score += 50;
        }

        // High-risk countries (example)
        $highRiskCountries = ['RU', 'CN', 'KP', 'IR', 'VE', 'NG', 'UA', 'RO', 'BG', 'ID'];
        $mediumRiskCountries = ['IN', 'PK', 'BR', 'TR', 'VN', 'PH', 'EG', 'MA', 'TH'];

        if (in_array($geoData['country'], $highRiskCountries)) {
            $score += 30;
        } elseif (in_array($geoData['country'], $mediumRiskCountries)) {
            $score += 15;
        }

        // Check if IP is in known data center ranges
        $dataCenterRanges = [
            '35.190.0.0/16', // Google Cloud
            '52.0.0.0/8',    // AWS
            '104.196.0.0/14', // Google Cloud
            '34.64.0.0/10',  // Google Cloud
            '157.240.0.0/16', // Facebook
            '199.16.156.0/22', // Twitter
            '192.30.252.0/22', // GitHub
        ];

        foreach ($dataCenterRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                $score += 25;
                break;
            }
        }

        // Check for suspicious hostname patterns
        $hostInfo = gethostbyaddr($ip);
        $suspiciousKeywords = ['bot', 'crawl', 'spider', 'scan', 'proxy', 'host', 'server'];

        foreach ($suspiciousKeywords as $keyword) {
            if (stripos($hostInfo, $keyword) !== false) {
                $score += 15;
                break;
            }
        }

        // Check for anonymous hosting providers

        // Cap the score at 100
        return min(100, $score);
    }

    /**
     * Get detailed OS information
     * 
     * @param Agent $agent
     * @return array
     */
    private function getDetailedOSInfo($agent)
    {
        $platform = $agent->platform();
        $version = $agent->version($platform);

        $osInfo = [
            'name' => $platform,
            'version' => $version,
            'family' => $this->getOSFamily($platform),
        ];

        // Add more detailed OS detection
        if ($platform == 'iOS') {
            $osInfo['device_type'] = $this->getiOSDeviceType($agent->getUserAgent());
        } elseif ($platform == 'AndroidOS') {
            $osInfo['api_level'] = $this->getAndroidAPILevel($version);
        } elseif ($platform == 'Windows') {
            $osInfo['edition'] = $this->getWindowsEdition($agent->getUserAgent());
        } elseif ($platform == 'OS X') {
            $osInfo['codename'] = $this->getMacOSCodename($version);
        } elseif ($platform == 'Linux') {
            $osInfo['distribution'] = $this->getLinuxDistribution($agent->getUserAgent());
        }

        return $osInfo;
    }

    /**
     * Get OS family (Mobile, Desktop, etc.)
     * 
     * @param string $platform
     * @return string
     */
    private function getOSFamily($platform)
    {
        $mobilePlatforms = ['iOS', 'AndroidOS', 'Windows Phone', 'BlackBerryOS'];
        $desktopPlatforms = ['Windows', 'OS X', 'Linux', 'Ubuntu', 'Debian'];

        if (in_array($platform, $mobilePlatforms)) {
            return 'Mobile';
        } elseif (in_array($platform, $desktopPlatforms)) {
            return 'Desktop';
        }

        return 'Other';
    }

    /**
     * Get iOS device type from user agent
     * 
     * @param string $userAgent
     * @return string
     */
    private function getiOSDeviceType($userAgent)
    {
        if (strpos($userAgent, 'iPhone') !== false) {
            return 'iPhone';
        } elseif (strpos($userAgent, 'iPad') !== false) {
            return 'iPad';
        } elseif (strpos($userAgent, 'iPod') !== false) {
            return 'iPod';
        }

        return 'Unknown';
    }

    /**
     * Map Android version to API level
     * 
     * @param string $version
     * @return int|null
     */
    private function getAndroidAPILevel($version)
    {
        $apiLevels = [
            '13' => 33,
            '12' => 32,
            '11' => 30,
            '10' => 29,
            '9' => 28,
            '8.1' => 27,
            '8.0' => 26,
            '7.1' => 25,
            '7.0' => 24,
            '6.0' => 23,
            '5.1' => 22,
            '5.0' => 21,
        ];

        foreach ($apiLevels as $androidVersion => $apiLevel) {
            if (strpos($version, $androidVersion) === 0) {
                return $apiLevel;
            }
        }

        return null;
    }

    /**
     * Get Windows edition from user agent
     * 
     * @param string $userAgent
     * @return string
     */
    private function getWindowsEdition($userAgent)
    {
        if (strpos($userAgent, 'Windows NT 10.0') !== false) {
            return 'Windows 10/11';
        } elseif (strpos($userAgent, 'Windows NT 6.3') !== false) {
            return 'Windows 8.1';
        } elseif (strpos($userAgent, 'Windows NT 6.2') !== false) {
            return 'Windows 8';
        } elseif (strpos($userAgent, 'Windows NT 6.1') !== false) {
            return 'Windows 7';
        }

        return 'Unknown';
    }

    /**
     * Get macOS codename from version
     * 
     * @param string $version
     * @return string
     */
    private function getMacOSCodename($version)
    {
        $codenames = [
            '13' => 'Ventura',
            '12' => 'Monterey',
            '11' => 'Big Sur',
            '10.15' => 'Catalina',
            '10.14' => 'Mojave',
            '10.13' => 'High Sierra',
            '10.12' => 'Sierra',
        ];

        foreach ($codenames as $osVersion => $codename) {
            if (strpos($version, $osVersion) === 0) {
                return $codename;
            }
        }

        return 'Unknown';
    }

    /**
     * Try to detect Linux distribution from user agent
     * 
     * @param string $userAgent
     * @return string
     */
    private function getLinuxDistribution($userAgent)
    {
        $distributions = [
            'Ubuntu' => 'Ubuntu',
            'Fedora' => 'Fedora',
            'Debian' => 'Debian',
            'CentOS' => 'CentOS',
            'RHEL' => 'Red Hat',
            'SUSE' => 'SUSE',
            'Mint' => 'Linux Mint',
        ];

        foreach ($distributions as $keyword => $distro) {
            if (strpos($userAgent, $keyword) !== false) {
                return $distro;
            }
        }

        return 'Unknown';
    }
}
