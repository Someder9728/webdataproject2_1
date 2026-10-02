<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rooms';

    protected $primaryKey = 'r_id';

    protected $fillable = [
        'r_name',
        'r_floor',
        'r_type',
        'r_rent',
        'r_status',
    ];

    protected function casts(): array
    {
        return [
            'r_floor' => 'integer',
            'r_rent' => 'decimal:2',
        ];
    }
}