<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offers extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'network_id',
        'domain_id',
        'device_urls',
        'age',
        'click_rate',
        'details',
        'countries',
        'status',
        'port',
        'allow_multiple_clicks',
        'proxy_check',
        'vpn_allowed',
        'tor_allowed',
        'max_risk_score',
        'expires_at',
        'daily_cap',
        'total_cap',
        'payout',
        'revenue',
        'targeting_rules',
        'utm_sources'
    ];

    protected $casts = [
        'device_urls' => 'array',
        'countries' => 'array',
        'vpn_allowed' => 'boolean',
        'tor_allowed' => 'boolean',
        'expires_at' => 'datetime',
        'targeting_rules' => 'array',
    ];

    protected $dates = [
        'expires_at',
    ];

    /**
     * Get the user that owns the offer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the network associated with the offer
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class, 'network_id');
    }

    /**
     * Get the domain associated with the offer
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    /**
     * Get all clicks for this offer
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(ClickData::class, 'offer_id');
    }

    /**
     * Check if the offer is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the offer has reached its daily cap
     */
    public function hasDailyCapReached(): bool
    {
        if (!$this->daily_cap) {
            return false;
        }

        $today = now()->startOfDay();
        $clickCount = $this->clicks()
            ->where('created_at', '>=', $today)
            ->count();

        return $clickCount >= $this->daily_cap;
    }

    /**
     * Check if the offer has reached its total cap
     */
    public function hasTotalCapReached(): bool
    {
        if (!$this->total_cap) {
            return false;
        }

        $clickCount = $this->clicks()->count();
        return $clickCount >= $this->total_cap;
    }

    /**
     * Check if traffic from a VPN is allowed for this offer
     */
    public function isVpnAllowed(): bool
    {
        return $this->vpn_allowed;
    }

    /**
     * Check if traffic from Tor is allowed for this offer
     */
    public function isTorAllowed(): bool
    {
        return $this->tor_allowed;
    }
}
