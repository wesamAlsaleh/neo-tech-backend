<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'uuid',
        'total_price',
        'status',
        'payment_method',
        'shipping_address',
    ];

    // This will force the UUID to be generated before saving the model
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid(); // Generates a v4 UUID
        });
    }

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
