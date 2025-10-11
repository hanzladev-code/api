<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtmSources extends Model
{
    protected $table = 'utm_sources';
    protected $fillable = ['created_by', 'updated_by', 'name', 'slug', 'status'];
    public function clicks()
    {
        return $this->hasMany(ClickData::class);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
