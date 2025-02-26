<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at']; // Ensure deleted_at is treated as a date

    protected $fillable = [
        'product_name',
        'product_description',
        'product_price',
        'product_rating',
        'product_stock',
        'product_sold',
        'product_view',
        'product_barcode',
        'slug',
        'images',
        'is_active',
        'category_id',
    ];

    // Cast the images column to an array (JSON to Array)
    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'product_price' => 'decimal:2',
        'product_rating' => 'decimal:2',
    ];

    // Each product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
