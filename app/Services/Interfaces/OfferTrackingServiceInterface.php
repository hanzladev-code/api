<?php

namespace App\Services\Interfaces;

use Illuminate\Http\Request;

interface OfferTrackingServiceInterface
{
    /**
     * Track an offer click and return appropriate response
     *
     * @param Request $request
     * @return array
     */
    public function trackOfferClick(Request $request): array;
    
    /**
     * Validate offer tracking request
     *
     * @param Request $request
     * @return array
     */
    public function validateRequest(Request $request): array;
    
    /**
     * Get real IP address from request
     *
     * @param Request $request
     * @return string
     */
    public function getRealIpAddress(Request $request): string;
    
    /**
     * Get geolocation data for an IP address
     *
     * @param string $ip
     * @return array
     */
    public function getGeoLocationData(string $ip): array;
    
    /**
     * Get proxy/VPN detection data for an IP address
     *
     * @param string $ip
     * @return array
     */
    public function getProxyCheckData(string $ip): array;
    
    /**
     * Calculate IP risk score
     *
     * @param string $ip
     * @param array $geoData
     * @param bool $vpnDetected
     * @param array $ipQualityData
     * @return int
     */
    public function calculateIPRiskScore(string $ip, array $geoData, bool $vpnDetected, array $ipQualityData): int;
    
    /**
     * Detect if traffic is coming from a VPN
     *
     * @param string $ip
     * @param array $ipQualityData
     * @return bool
     */
    public function detectVPN(string $ip, array $ipQualityData): bool;
    
    /**
     * Get detailed device information
     *
     * @param mixed $agent
     * @return array
     */
    public function getDeviceInfo($agent): array;
    
    /**
     * Log tracking event with detailed information
     *
     * @param array $data
     * @return void
     */
    public function logTrackingEvent(array $data): void;
}