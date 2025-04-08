<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'payment_method',
        'shipping_address',
    ];

    /**
     * Relationship: An order belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: An order can have many order items.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
