<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room;

class Meter extends Model
{
    protected $fillable = [
    'm_date',
    'm_water',
    'm_elec',
    'rooms_r_id',
];

    public function room() {
        return $this->belongsTo(Room::class,'room_r_id','r_id');
    }
}
