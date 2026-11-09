<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['organizer_id','title','description','venue','starts_at','status'];
    protected $casts = ['starts_at' => 'datetime'];

    public function organizer() 
    { 
        return $this->belongsTo(User::class, 'organizer_id'); 
    }
    public function seats() 
    { 
        return $this->hasMany(Seat::class); 
    }
}
