<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'r_number',
    'r_floor',
    'r_type',
    'r_price',
    'r_status',
])]
class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rooms';

    protected $primaryKey = 'r_id';

    protected $fillable = [
        'r_number',
        'r_floor',
        'r_type',
        'r_price',
        'r_status',
    ];

    protected function casts(): array
    {
        return [
            'r_floor' => 'integer',
            'r_price' => 'decimal:2',
        ];
    }
}