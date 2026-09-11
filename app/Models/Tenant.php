<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Rental;
use App\Models\Repair;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{

    use SoftDeletes;

    protected $fillable = [
    't_Fname',
    't_Lname',
    't_tel',
    't_mail',
    't_address',
];

    protected $primaryKey = 't_id';

    public function user() {
        return $this->hasOne(User::class, 'tenants_t_id', 't_id');
    }

    public function rentals() {
        return $this->hasMany(Rental::class, 'tenants_t_id', 't_id');
    }

    public function repairs() {
        return $this->hasMany(Repair::class, 'tenants_t_id', 't_id');
    }
}
