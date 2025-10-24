<?php

namespace App\Services;

use App\Models\ClickData;
use App\Models\Offers;
use App\Models\Tracker;
use App\Models\User;
use App\Services\Interfaces\OfferTrackingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class OfferTrackingService implements OfferTrackingServiceInterface
{
    /**
     * Track an offer click and return appropriate response
     *
     * @param Request $request
     * @return array
     */
    public function trackOfferClick(Request $request): array
    {
        try {
            // Validate the request
            $validationResult = $this->validateRequest($request);
            if (!$validationResult['success']) {
                return [
                    'status' => 'error',
                    'heading' => 'Error',
                    'message' => 'Validation failed',
                    'errors' => $validationResult['errors']
                ];
            }

            // Get the offer details
            $offer = Offers::findOrFail($request->offer_id)->load('network.tracker');
            $tracker = Tracker::findOrFail($offer->network->tracker);
            $param = $tracker->param;

            // Check if offer is expired
            if ($this->isOfferExpired($offer)) {
                return [
                    'status' => 'error',
                    'heading' => 'Expired',
                    'message' => 'This offer has expired',
                    'redirect_url' => null
                ];
            }

            // Check if offer has reached its caps
            if ($this->hasOfferReachedCaps($offer)) {
                return [
                    'status' => 'error',
                    'heading' => 'Cap Reached',
                    'message' => 'This offer has reached its cap',
                    'redirect_url' => null
                ];
            }

            // Get real IP and location data
            $realIp = $this->getRealIpAddress($request);
            $locationData = $this->getGeoLocationData($realIp);

            // Get device information
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());
            $deviceInfo = $this->getDeviceInfo($agent);

            // Get proxy/VPN detection data
            $ipQualityData = $this->getProxyCheckData($realIp);
            $vpnDetected = $this->detectVPN($realIp, $ipQualityData);
            $proxyDetected = $ipQualityData['proxy'] ?? false;
            $torDetected = $ipQualityData['tor'] ?? false;

            // Generate click ID
            $clickId = $this->generateClickId($locationData['country'] ?? 'XX');

            // Check if VPN/Proxy/Tor is allowed for this offer
            if (($vpnDetected && !$offer->isVpnAllowed()) ||
                ($torDetected && !$offer->isTorAllowed()) ||
                ($proxyDetected && !$offer->proxy_check)) {
                return [
                    'status' => 'rejected',
                    'heading' => 'Anti-Fraud',
                    'message' => 'Traffic from VPN/Proxy/Tor is not allowed for this offer',
                    'click_id' => $clickId
                ];
            }

            // Calculate IP risk score
            $ipRiskScore = $this->calculateIPRiskScore($realIp, $locationData, $vpnDetected, $ipQualityData);

            // Check if IP risk score exceeds the maximum allowed for this offer
            if ($offer->max_risk_score && $ipRiskScore > $offer->max_risk_score) {
                return [
                    'status' => 'rejected',
                    'heading' => 'Anti-Fraud',
                    'message' => 'Traffic risk score too high for this offer',
                    'click_id' => $clickId
                ];
            }

            // Process UTM parameters
            $utmParams = $this->processUtmParameters($request, $realIp);

            // Detect traffic source
            $trafficSource = $this->detectTrafficSource($request);

            // Collect sub IDs
            $subIds = $this->collectSubIds($request);

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
                    return [
                        'status' => 'duplicate',
                        'heading' => 'Anti-Fraud',
                        'message' => 'Duplicate click detected',
                        'click_id' => $recentClick->click_id
                    ];
                }
            }

            // Store click data
            $clickData = $this->storeClickData([
                'offer_id' => $offer->id,
                'click_id' => $clickId,
                'ip' => $realIp,
                'user_agent' => $request->userAgent(),
                'referrer_id' => $request->ref,
                'geo_data' => $locationData,
                'device_info' => $deviceInfo,
                'utm_params' => $utmParams,
                'sub_ids' => $subIds,
                'traffic_source' => $trafficSource,
                'fraud_metrics' => $fraudMetrics,
                'fingerprint' => $request->fingerprint ?? null,
                'timezone_offset' => $request->timezone_offset ?? null,
                'local_time' => $request->local_time ?? null,
            ]);

            // Log the tracking event
            $this->logTrackingEvent([
                'click_id' => $clickId,
                'offer_id' => $offer->id,
                'ip' => $realIp,
                'status' => 'success',
                'geo_data' => $locationData,
                'device_info' => $deviceInfo,
                'fraud_metrics' => $fraudMetrics,
            ]);

            // Generate redirect URL
            $redirectUrl = $this->generateRedirectUrl($offer, $clickId, $subIds, $utmParams);

            return [
                'status' => 'success',
                'redirect_url' => $redirectUrl,
                'click_id' => $clickId
            ];
        } catch (\Exception $e) {
            Log::error('Offer tracking error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return [
                'status' => 'error',
                'heading' => 'Error',
                'message' => 'An error occurred while processing your request',
                'redirect_url' => null
            ];
        }
    }

    /**
     * Validate offer tracking request
     *
     * @param Request $request
     * @return array
     */
    public function validateRequest(Request $request): array
    {
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
            'user_agent' => 'nullable|string',
            'screen_resolution' => 'nullable|string',
            'color_depth' => 'nullable|integer',
            'browser_language' => 'nullable|string',
            'platform' => 'nullable|string',
            'device_memory' => 'nullable|string',
            'hardware_concurrency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'success' => true
        ];
    }

    /**
     * Get real IP address from request
     *
     * @param Request $request
     * @return string
     */
    public function getRealIpAddress(Request $request): string
    {
        // Log all headers for debugging
        Log::debug('Request Headers: ' . json_encode($request->headers->all()));
        Log::debug('Server Variables: ' . json_encode($_SERVER));
        
        // Check for client-side provided headers (from our frontend)
        if ($request->header('X-Client-IP') && $request->header('X-Client-IP') !== 'true') {
            return $request->header('X-Client-IP');
        }
        
        // Check for CloudFlare IP (most reliable if using CloudFlare)
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Check for True-Client-IP header
        if ($request->header('True-Client-IP')) {
            return $request->header('True-Client-IP');
        }

        // Check for Fastly-specific headers
        if ($request->header('Fastly-Client-IP')) {
            return $request->header('Fastly-Client-IP');
        }

        // Check for Akamai headers
        if ($request->header('True-Client-Ip')) {
            return $request->header('True-Client-Ip');
        }

        // Check X-Forwarded-For header (common in many proxy setups)
        if ($request->header('X-Forwarded-For')) {
            $xForwardedFor = explode(',', $request->header('X-Forwarded-For'));
            return trim($xForwardedFor[0]); // First IP is the client
        }

        // Check X-Real-IP header
        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }
        
        // Check for Fly.io headers
        if ($request->header('Fly-Client-IP')) {
            return $request->header('Fly-Client-IP');
        }
        
        // Check for Heroku headers
        if ($request->header('X-Forwarded-For-Original')) {
            return $request->header('X-Forwarded-For-Original');
        }
        
        // Check for AWS ELB/ALB headers
        if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }
        
        // Check for Vercel headers
        if ($request->header('X-Vercel-Forwarded-For')) {
            $forwardedIps = explode(',', $request->header('X-Vercel-Forwarded-For'));
            return trim($forwardedIps[0]);
        }
        
        // Check for Netlify headers
        if ($request->header('X-Netlify-Original-IP')) {
            return $request->header('X-Netlify-Original-IP');
        }

        // Fallback to the standard IP method
        $ip = $request->ip();
        
        // If we're in production but got a private/local IP, use a fallback
        if (app()->environment('production') && $this->isPrivateIP($ip)) {
            return env('FALLBACK_IP_ADDRESS', '169.197.85.173');
        }
        
        return $ip;
    }

    /**
     * Check if an IP address is private/local
     * 
     * @param string $ip
     * @return bool
     */
    private function isPrivateIP($ip)
    {
        $privateRanges = [
            '10.0.0.0|10.255.255.255',     // 10.0.0.0/8
            '172.16.0.0|172.31.255.255',   // 172.16.0.0/12
            '192.168.0.0|192.168.255.255', // 192.168.0.0/16
            '169.254.0.0|169.254.255.255', // 169.254.0.0/16
            '127.0.0.0|127.255.255.255',   // 127.0.0.0/8
        ];
        
        $ipLong = ip2long($ip);
        
        if ($ipLong === false) {
            return true; // Invalid IP, treat as private
        }
        
        foreach ($privateRanges as $range) {
            list($start, $end) = explode('|', $range);
            if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get geolocation data for an IP address
     *
     * @param string $ip
     * @return array
     */
    public function getGeoLocationData(string $ip): array
    {
        try {
            // Try to get from cache first
            $cacheKey = 'geo_' . md5($ip);
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $locationData = Location::get($ip);
            
            if (!$locationData) {
                return [
                    'country' => 'XX',
                    'country_code' => 'XX',
                    'region' => 'Unknown',
                    'city' => 'Unknown',
                    'postal_code' => 'Unknown',
                    'latitude' => 0,
                    'longitude' => 0,
                    'timezone' => 'Unknown',
                ];
            }
            
            $geoData = [
                'country' => $locationData->countryName ?? 'Unknown',
                'country_code' => $locationData->countryCode ?? 'XX',
                'region' => $locationData->regionName ?? 'Unknown',
                'city' => $locationData->cityName ?? 'Unknown',
                'postal_code' => $locationData->zipCode ?? 'Unknown',
                'latitude' => $locationData->latitude ?? 0,
                'longitude' => $locationData->longitude ?? 0,
                'timezone' => $locationData->timezone ?? 'Unknown',
            ];
            
            // Cache the result for 1 hour
            Cache::put($cacheKey, $geoData, now()->addHour());
            
            return $geoData;
        } catch (\Exception $e) {
            Log::error('Error getting geolocation data: ' . $e->getMessage(), [
                'ip' => $ip,
                'exception' => $e
            ]);
            
            return [
                'country' => 'XX',
                'country_code' => 'XX',
                'region' => 'Unknown',
                'city' => 'Unknown',
                'postal_code' => 'Unknown',
                'latitude' => 0,
                'longitude' => 0,
                'timezone' => 'Unknown',
            ];
        }
    }

    /**
     * Get proxy/VPN detection data for an IP address
     *
     * @param string $ip
     * @return array
     */
    public function getProxyCheckData(string $ip): array
    {
        try {
            // Cache key for this IP to avoid repeated API calls
            $cacheKey = 'proxycheck_' . md5($ip);
            
            // Check if we have cached results for this IP
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
            
            $apiKey = env('PROXYCHECK_API_KEY') ?? '33812r-47v9z6-b5i430-i83158';

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => 'API key not configured'
                ];
            }

            // Make request to ProxyCheck.io API with enhanced parameters
            $response = Http::timeout(5)->get("https://proxycheck.io/v2/{$ip}", [
                'key' => $apiKey,
                'vpn' => 1,
                'asn' => 1,
                'risk' => 1,
                'port' => 1,
                'seen' => 1,
                'days' => 7,
                'tag' => 'visitor-tracking',
                'time' => 1,
                'inf' => 1, // Get more detailed information
                'node' => 1  // Use multiple detection nodes for better accuracy
            ]);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['status']) && $result['status'] === 'ok' && isset($result[$ip])) {
                    $ipData = $result[$ip];

                    $data = [
                        'success' => true,
                        'proxy' => $ipData['proxy'] === 'yes',
                        'vpn' => $ipData['type'] === 'VPN',
                        'tor' => $ipData['type'] === 'TOR',
                        'type' => $ipData['type'] ?? 'Unknown',
                        'risk' => $ipData['risk'] ?? 0,
                        'bot_score' => isset($ipData['risk']) ? min(100, $ipData['risk'] * 20) : 0,
                        'asn' => $ipData['asn'] ?? 'Unknown',
                        'provider' => $ipData['provider'] ?? 'Unknown',
                        'continent' => $ipData['continent'] ?? 'Unknown',
                        'country' => $ipData['country'] ?? 'Unknown',
                        'isocode' => $ipData['isocode'] ?? 'XX',
                        'region' => $ipData['region'] ?? 'Unknown',
                        'city' => $ipData['city'] ?? 'Unknown',
                        'last_seen' => $ipData['seen'] ?? 'Unknown',
                    ];
                    
                    // Cache the result for 1 hour
                    Cache::put($cacheKey, $data, now()->addHour());
                    
                    return $data;
                }
            }

            // Fallback data if API call fails
            $fallbackData = [
                'success' => false,
                'proxy' => false,
                'vpn' => false,
                'tor' => false,
                'type' => 'Unknown',
                'risk' => 0,
                'bot_score' => 0,
                'message' => 'API call failed or returned invalid data'
            ];
            
            // Cache the fallback data for 15 minutes (shorter time for failed results)
            Cache::put($cacheKey, $fallbackData, now()->addMinutes(15));
            
            return $fallbackData;
        } catch (\Exception $e) {
            Log::error('Error checking proxy data: ' . $e->getMessage(), [
                'ip' => $ip,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'proxy' => false,
                'vpn' => false,
                'tor' => false,
                'type' => 'Unknown',
                'risk' => 0,
                'bot_score' => 0,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate IP risk score
     *
     * @param string $ip
     * @param array $geoData
     * @param bool $vpnDetected
     * @param array $ipQualityData
     * @return int
     */
    public function calculateIPRiskScore(string $ip, array $geoData, bool $vpnDetected, array $ipQualityData): int
    {
        $score = 0;
        
        // Base risk from ProxyCheck
        $score += ($ipQualityData['risk'] ?? 0) * 10;
        
        // VPN/Proxy detection adds risk
        if ($vpnDetected) {
            $score += 20;
        }
        
        if ($ipQualityData['proxy'] ?? false) {
            $score += 15;
        }
        
        if ($ipQualityData['tor'] ?? false) {
            $score += 30;
        }
        
        // High-risk countries (customize this list based on your data)
        $highRiskCountries = ['XX', 'N/A', 'T1', 'O1', 'A1', 'A2'];
        if (in_array($geoData['country_code'] ?? 'XX', $highRiskCountries)) {
            $score += 25;
        }
        
        // Cap the score at 100
        return min(100, $score);
    }

    /**
     * Detect if traffic is coming from a VPN
     *
     * @param string $ip
     * @param array $ipQualityData
     * @return bool
     */
    public function detectVPN(string $ip, array $ipQualityData): bool
    {
        // Check ProxyCheck.io data first
        if (isset($ipQualityData['vpn']) && $ipQualityData['vpn']) {
            return true;
        }
        
        if (isset($ipQualityData['type']) && $ipQualityData['type'] === 'VPN') {
            return true;
        }
        
        // Additional VPN detection logic could be added here
        
        return false;
    }

    /**
     * Get detailed device information
     *
     * @param mixed $agent
     * @return array
     */
    public function getDeviceInfo($agent): array
    {
        return [
            'device' => $agent->device(),
            'platform' => $agent->platform(),
            'platform_version' => $agent->version($agent->platform()),
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'is_desktop' => $agent->isDesktop(),
            'is_mobile' => $agent->isMobile(),
            'is_tablet' => $agent->isTablet(),
            'is_robot' => $agent->isRobot(),
            'robot_name' => $agent->isRobot() ? $agent->robot() : null,
            'device_type' => $this->getDeviceType($agent),
            'os_info' => $this->getDetailedOSInfo($agent),
        ];
    }

    /**
     * Get device type
     *
     * @param mixed $agent
     * @return string
     */
    private function getDeviceType($agent): string
    {
        if ($agent->isRobot()) {
            return 'robot';
        }
        
        if ($agent->isTablet()) {
            return 'tablet';
        }
        
        if ($agent->isMobile()) {
            return 'mobile';
        }
        
        if ($agent->isDesktop()) {
            return 'desktop';
        }
        
        return 'unknown';
    }

    /**
     * Get detailed OS information
     *
     * @param mixed $agent
     * @return array
     */
    private function getDetailedOSInfo($agent): array
    {
        $platform = $agent->platform();
        $version = $agent->version($platform);
        
        $osInfo = [
            'name' => $platform,
            'version' => $version,
            'full' => $platform . ' ' . $version,
        ];
        
        // Add more detailed OS information based on platform
        if (strtolower($platform) === 'windows') {
            $osInfo['family'] = 'Windows';
            $osInfo['type'] = 'Desktop';
        } elseif (strtolower($platform) === 'android') {
            $osInfo['family'] = 'Android';
            $osInfo['type'] = 'Mobile';
        } elseif (in_array(strtolower($platform), ['iphone', 'ipad', 'ipod'])) {
            $osInfo['family'] = 'iOS';
            $osInfo['type'] = 'Mobile';
        } elseif (strtolower($platform) === 'macintosh') {
            $osInfo['family'] = 'macOS';
            $osInfo['type'] = 'Desktop';
        } elseif (strtolower($platform) === 'linux') {
            $osInfo['family'] = 'Linux';
            $osInfo['type'] = 'Desktop';
        } else {
            $osInfo['family'] = 'Other';
            $osInfo['type'] = 'Unknown';
        }
        
        return $osInfo;
    }

    /**
     * Check if offer is expired
     *
     * @param Offers $offer
     * @return bool
     */
    private function isOfferExpired(Offers $offer): bool
    {
        if (!$offer->end_date) {
            return false;
        }
        
        return now()->greaterThan($offer->end_date);
    }

    /**
     * Check if offer has reached its caps
     *
     * @param Offers $offer
     * @return bool
     */
    private function hasOfferReachedCaps(Offers $offer): bool
    {
        return $offer->hasDailyCapReached() || $offer->hasTotalCapReached();
    }

    /**
     * Generate a unique click ID
     *
     * @param string $countryCode
     * @return string
     */
    private function generateClickId(string $countryCode): string
    {
        $todayClickCount = ClickData::whereDate('created_at', now()->toDateString())->count();
        $date = now()->format('dmY');
        $sequentialNumber = str_pad(($todayClickCount + 1), 4, '0', STR_PAD_LEFT);
        return $countryCode . $date . $sequentialNumber;
    }

    /**
     * Process UTM parameters
     *
     * @param Request $request
     * @param string $ip
     * @return array
     */
    private function processUtmParameters(Request $request, string $ip): array
    {
        $utmParams = [
            'utm_source' => $request->utm_source,
            'utm_medium' => $request->utm_medium,
            'utm_campaign' => $request->utm_campaign,
            'utm_term' => $request->utm_term,
            'utm_content' => $request->utm_content,
        ];
        
        // If utm_id exists but utm_source doesn't, generate realistic UTM parameters
        if ($request->has('utm_id') && !$request->has('utm_source')) {
            // Get the UTM source from the database based on utm_id
            $utmSource = \App\Models\UtmSources::find($request->utm_id);
            
            if ($utmSource) {
                // Use the slug from the UTM source as the utm_source value
                $sourceSlug = $utmSource->slug;
                
                // Define UTM parameter options based on the source slug
                $utmOptions = $this->getUtmOptions();
                
                // If the source slug exists in our options, use it; otherwise use a default
                $sourceOptions = $utmOptions[$sourceSlug] ?? $utmOptions['google'];
                
                // Generate random UTM parameters with variations
                $randomSeed = microtime(true) . $ip . rand(1000, 9999);
                
                // Select random values for each parameter based on the source
                $mediumOptions = $sourceOptions['medium'];
                $campaignOptions = $sourceOptions['campaign'];
                $contentOptions = $sourceOptions['content'];
                $termOptions = $sourceOptions['term'];
                
                // Add random variations to make parameters more unique
                $campaignSuffix = rand(100, 999);
                $contentSuffix = substr(md5($randomSeed), 0, 5);
                
                // Set the UTM parameters with randomization
                $utmParams = [
                    'utm_source' => $sourceSlug,
                    'utm_medium' => $mediumOptions[array_rand($mediumOptions)],
                    'utm_campaign' => $campaignOptions[array_rand($campaignOptions)] . '_' . $campaignSuffix,
                    'utm_content' => $contentOptions[array_rand($contentOptions)] . '_' . $contentSuffix,
                    'utm_term' => $termOptions[array_rand($termOptions)]
                ];
                
                // Cache the parameters briefly to avoid identical parameters for same IP in short timeframe
                $cacheKey = 'utm_params_' . md5($ip);
                if (Cache::has($cacheKey)) {
                    // If we've generated parameters for this IP recently, slightly modify them
                    $utmParams['utm_content'] = $utmParams['utm_content'] . '_v' . rand(2, 9);
                }
                Cache::put($cacheKey, true, now()->addMinutes(5));
            }
        }
        
        return $utmParams;
    }

    /**
     * Get UTM options for different sources
     *
     * @return array
     */
    private function getUtmOptions(): array
    {
        return [
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
            'google' => [
                'medium' => ['cpc', 'search', 'display', 'remarketing'],
                'campaign' => ['brand', 'generic', 'competitor', 'display_network'],
                'content' => ['text_ad', 'responsive_ad', 'banner', 'video'],
                'term' => ['exact', 'phrase', 'broad', 'keyword']
            ],
            // Add more sources as needed
        ];
    }

    /**
     * Detect traffic source
     *
     * @param Request $request
     * @return string
     */
    private function detectTrafficSource(Request $request): string
    {
        if ($request->has('utm_source')) {
            return $request->utm_source;
        }
        
        $referer = $request->header('referer');
        if ($referer) {
            $parsedUrl = parse_url($referer);
            if (isset($parsedUrl['host'])) {
                return $parsedUrl['host'];
            }
        }
        
        return 'direct';
    }

    /**
     * Collect sub IDs from request
     *
     * @param Request $request
     * @return array
     */
    private function collectSubIds(Request $request): array
    {
        $subIds = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $key = 'sub_id' . $i;
            if ($request->has($key)) {
                $subIds[$key] = $request->$key;
            }
        }
        
        return $subIds;
    }

    /**
     * Check for recent clicks from the same IP
     *
     * @param int $offerId
     * @param string $ip
     * @return mixed
     */
    private function checkRecentClicks(int $offerId, string $ip)
    {
        return ClickData::where('offer_id', $offerId)
            ->where('ip', $ip)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();
    }

    /**
     * Store click data
     *
     * @param array $data
     * @return ClickData
     */
    private function storeClickData(array $data): ClickData
    {
        $clickData = new ClickData();
        $clickData->offer_id = $data['offer_id'];
        $clickData->click_id = $data['click_id'];
        $clickData->ip = $data['ip'];
        $clickData->user_agent = $data['user_agent'];
        $clickData->referrer_id = $data['referrer_id'];
        $clickData->geo_data = json_encode($data['geo_data']);
        $clickData->device_info = json_encode($data['device_info']);
        $clickData->utm_params = json_encode($data['utm_params']);
        $clickData->sub_ids = json_encode($data['sub_ids']);
        $clickData->traffic_source = $data['traffic_source'];
        $clickData->fraud_metrics = json_encode($data['fraud_metrics']);
        $clickData->fingerprint = $data['fingerprint'];
        $clickData->timezone_offset = $data['timezone_offset'];
        $clickData->local_time = $data['local_time'];
        $clickData->save();
        
        return $clickData;
    }

    /**
     * Generate redirect URL
     *
     * @param Offers $offer
     * @param string $clickId
     * @param array $subIds
     * @param array $utmParams
     * @return string
     */
    private function generateRedirectUrl(Offers $offer, string $clickId, array $subIds, array $utmParams): string
    {
        // Implementation depends on your specific requirements
        // This is a placeholder
        return $offer->tracking_url . '?click_id=' . $clickId;
    }

    /**
     * Log tracking event with detailed information
     *
     * @param array $data
     * @return void
     */
    public function logTrackingEvent(array $data): void
    {
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'click_id' => $data['click_id'],
            'offer_id' => $data['offer_id'],
            'ip' => $data['ip'],
            'status' => $data['status'],
            'geo_data' => $data['geo_data'],
            'device_info' => $data['device_info'],
            'fraud_metrics' => $data['fraud_metrics'],
        ];
        
        // Use color-coded logging based on status
        $logMessage = "Offer Tracking: [{$data['click_id']}] - {$data['status']}";
        
        switch ($data['status']) {
            case 'success':
                Log::info($logMessage, $logData);
                break;
            case 'rejected':
                Log::warning($logMessage, $logData);
                break;
            case 'error':
                Log::error($logMessage, $logData);
                break;
            default:
                Log::debug($logMessage, $logData);
                break;
        }
    }
}