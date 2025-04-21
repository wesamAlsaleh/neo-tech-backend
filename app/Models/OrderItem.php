<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'product_id', // Foreign key to the products table
        'quantity',
        'price', // Price at the time of order
    ];

    /**
     * Relationship: An order item belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: An order item belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
