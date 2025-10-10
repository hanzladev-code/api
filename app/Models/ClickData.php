<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ClickData extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'click_id',
        'offer_id',
        'ref_id',
        'ip',
        'real_ip',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'country',
        'city',
        'region',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'sub_id1',
        'sub_id2',
        'sub_id3',
        'sub_id4',
        'sub_id5',
        'sub_id6',
        'sub_id7',
        'sub_id8',
        'sub_id9',
        'sub_id10',
        'vpn_detected',
        'proxy_detected',
        'tor_detected',
        'ip_risk_score',
        'fraud_score',
        'converted',
        'converted_at',
        'payout',
        'revenue',
        'metadata',
        'status'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vpn_detected' => 'boolean',
        'proxy_detected' => 'boolean',
        'tor_detected' => 'boolean',
        'converted' => 'boolean',
        'converted_at' => 'datetime',
        'metadata' => 'json',
        'payout' => 'decimal:2',
        'revenue' => 'decimal:2',
    ];
    
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($clickData) {
            // Generate a UUID for the click_id if not set
            if (!$clickData->click_id) {
                $clickData->click_id = (string) Str::uuid();
            }
        });
    }
    
    /**
     * Get the offer that owns the click.
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }
    
    /**
     * Get the referring user for this click.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ref_id');
    }
    
    /**
     * Mark this click as converted.
     *
     * @param float|null $payout
     * @param float|null $revenue
     * @return bool
     */
    public function markAsConverted(?float $payout = null, ?float $revenue = null): bool
    {
        $this->converted = true;
        $this->converted_at = now();
        $this->status = 'converted';
        
        if ($payout !== null) {
            $this->payout = $payout;
        }
        
        if ($revenue !== null) {
            $this->revenue = $revenue;
        }
        
        return $this->save();
    }
    
    /**
     * Scope a query to only include clicks from a specific date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $startDate
     * @param string|null $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query;
    }
    
    /**
     * Scope a query to only include clicks with specific fraud parameters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool|null $vpn
     * @param bool|null $proxy
     * @param int|null $minRiskScore
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFraudParams($query, $vpn = null, $proxy = null, $minRiskScore = null)
    {
        if ($vpn !== null) {
            $query->where('vpn_detected', $vpn);
        }
        
        if ($proxy !== null) {
            $query->where('proxy_detected', $proxy);
        }
        
        if ($minRiskScore !== null) {
            $query->where('ip_risk_score', '>=', $minRiskScore);
        }
        
        return $query;
    }
}
