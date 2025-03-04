<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopFeature extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'is_active',
    ];
}
