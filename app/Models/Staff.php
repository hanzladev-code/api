<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Staff extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'domain',
        'payout',
        'skype',
        'description',
        'gender',
        'role',
        'status',
        'notification',
        'image'
    ];
    protected $hidden = ['password'];
}
