<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Options extends Model
{
    protected $fillable = ['key', 'name', 'description', 'value'];
    protected $casts = [
        'value' => 'array',
    ];
}
