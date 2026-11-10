<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['organizer_id', 'title', 'description', 'venue', 'starts_at', 'status'];

    protected $casts = ['starts_at' => 'datetime'];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
