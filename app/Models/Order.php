<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// This trait is used to generate UUIDs for the model
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    // This trait is used to generate UUIDs for the model
    use HasUuids;

    /**
     * The attributes that will be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['hashed_id'];

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

    /**
     * Accessor: Get the hashed ID of the order.
     *
     * @return string
     */
    public function getHashedIdAttribute()
    {
        // Return the hashed ID of the order
        return hash('sha256', $this->id);
    }
}
