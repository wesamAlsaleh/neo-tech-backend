<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at']; // Ensure deleted_at is treated as a date

    protected $appends = ['sale_duration']; // This makes sale_duration available in JSON responses eg. $product->sale_duration; // Outputs the sale duration in days

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
        'onSale',
        'product_price_after_discount',
        'discount',
        'sale_start',
        'sale_end',
    ];

    // Cast the images column to an array (JSON to Array)
    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'product_price' => 'decimal:2',
        'product_price_after_discount' => 'decimal:2',
        'product_rating' => 'decimal:2',
        'product_stock' => 'integer',
        'product_sold' => 'integer',
        'product_view' => 'integer',
        'discount' => 'decimal:2',
    ];

    // Each product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // products can belong to flash sales
    public function flashSales()
    {
        return $this->belongsToMany(FlashSale::class);
    }

    // A product can appear in many wishlists
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Get the sale duration of a product
    public function getSaleDurationAttribute()
    {
        if ($this->sale_start && $this->sale_end) {
            return Carbon::parse($this->sale_start)->diffInDays(Carbon::parse($this->sale_end));
        }
        return null;

        // Usage:
        /**
         * $product = Product::find(1);
         * $product->sale_duration; // Outputs the sale duration in days

         * Output:
         * {
         *   "id": 1,
         * "product_name": "Product 1",
         * "sale_start": "2025-03-05 20:27:38",
         * "sale_end": "2025-03-10 20:27:38",
         * "sale_duration": 5, // Sale duration in days
         * }
         */
    }

    // A product can appear in many cart items
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
