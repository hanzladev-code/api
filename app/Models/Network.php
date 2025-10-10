<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    protected $fillable = ['user_id', 'name', 'tracker', 'status'];

    public function tracker()
    {
        return $this->belongsTo(Tracker::class, 'tracker', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
