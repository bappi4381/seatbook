<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = ['event_id','label','row','number','price','status'];

    public function event() 
    { 
        return $this->belongsTo(Event::class); 
    }
    public function hold() 
    { 
        return $this->hasOne(SeatHold::class); 
    }       
}
