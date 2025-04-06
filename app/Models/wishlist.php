<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class wishlist extends Model
{
    // Mass assignable attributes
    protected $fillable = ['user_id', 'product_id'];

    // A wishlist entry belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A wishlist entry belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
