<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemPerformanceLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'log_type',
        'message',
        'context',
        'user_id',
        'status_code',
    ];

    /**
     * Relationship: A system performance log belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
