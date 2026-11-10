<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatHold extends Model
{
    protected $fillable = ['seat_id', 'user_id', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
