<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'label', 'row', 'number', 'price'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function hold()
    {
        return $this->hasOne(SeatHold::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_seats');
    }
}
