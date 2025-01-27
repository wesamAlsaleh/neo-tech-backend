<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'product_name',
        'product_description',
        'product_price',
        'product_rating',
        'slug',
        'images',
        'is_active',
        'in_stock',
        'category_id',
    ];

    // Cast the images column to an array (JSON to Array)
    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'in_stock' => 'boolean',
        'product_price' => 'decimal:2',
        'product_rating' => 'integer',
    ];

    // Each product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // helpful accessors/mutators
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    // Optional price formatter accessor
    public function getFormattedPriceAttribute()
    {
        return number_format($this->product_price, 2);
    }
}
