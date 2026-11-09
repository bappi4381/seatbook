<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = ['user_id','event_id','status','total_amount'];

    public function seats() 
    { 
        return $this->belongsToMany(Seat::class, 'booking_seats'); 
    }
}
