<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dog extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'age',
        'gender',
        'breed',
        'owner_name',
        'phone',
        'email',
        'photo_url',
        'status',
        'show_phone'
    ];
}
