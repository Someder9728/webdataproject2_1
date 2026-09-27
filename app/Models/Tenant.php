<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    't_Fname',
    't_Lname',
    't_tel',
    't_mail',
    't_address',
])]
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /* ชื่อตาราง */
    protected $table = 'tenants';

    /* Primary Key */
    protected $primaryKey = 't_id';

    /* Fields ที่สามารถบันทึกได้ */
    protected $fillable = [
        't_Fname',
        't_Lname',
        't_tel',
        't_mail',
        't_address',
    ];

    /*
      User account ของผู้เช่า
      Tenant 1 คน มี User account ได้ 1 account
     */
    public function user()
    {
        return $this->hasOne(
            User::class,
            'tenants_t_id',
            't_id'
        );
    }
}