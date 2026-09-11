<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Rental;
use App\Models\Meter;
use App\Models\Repair;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model {

    use SoftDeletes;


    protected $fillable = [
    'r_name',
    'r_floor',
    'r_type',
    'r_rent',
    'r_status',
    ];


    protected $primaryKey = 'r_id';

    public function rentals() {
        return $this->hasMany(Rental::class, 'rooms_r_id', 'r_id');
    }

    public function meters() {
        return $this->hasMany(Meter::class, 'rooms_r_id', 'r_id');
    }

    public function repairs() {
        return $this->hasMany(Repair::class, 'rooms_r_id', 'r_id');
    }
}
