<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'category_name',
        'category_slug',
        'category_description',
        'category_image'
    ];

    // Each category has many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
