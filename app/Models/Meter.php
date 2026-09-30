<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meter extends Model
{

    use SoftDeletes;

    protected $fillable = [
    'm_date',
    'm_water',
    'm_elec',
    'rooms_r_id',
];

    protected $primaryKey = 'm_id';

    protected function casts(): array
    {
        return [
            'm_date' => 'date:Y-m-d',
            'm_water' => 'decimal:2',
            'm_elec' => 'decimal:2',
        ];
    }

    public function room() {
        return $this->belongsTo(Room::class, 'rooms_r_id', 'r_id');
    }
}
