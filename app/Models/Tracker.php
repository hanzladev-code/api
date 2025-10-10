<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    protected $fillable = ['user_id', 'name', 'param', 'value', 'source', 'description', 'rate', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
