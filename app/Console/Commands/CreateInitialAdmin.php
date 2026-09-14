<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateInitialAdmin extends Command
{
    protected $signature = 'app:create-initial-admin';

    protected $description = 'สร้างบัญชี Admin เริ่มต้นของระบบ';

    public function handle(): int
    {
        if (User::where('u_role', 'admin')->exists()) {
            $this->error('มีบัญชี Admin แล้ว ไม่สร้างบัญชีเริ่มต้นซ้ำ');

            return self::FAILURE;
        }

        $username = Str::lower(trim(
            (string) $this->ask('ชื่อผู้ใช้ Admin')
        ));

        $password = (string) $this->secret('รหัสผ่านชั่วคราว อย่างน้อย 12 ตัว');
        $confirmation = (string) $this->secret('ยืนยันรหัสผ่านชั่วคราว');

        $validator = Validator::make([
            'username' => $username,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:45',
                'regex:/\A[a-z0-9._-]+\z/',
                'unique:users,u_username',
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (strlen($password) > 72) {
            $this->error('รหัสผ่านต้องไม่เกิน 72 ไบต์');

            return self::FAILURE;
        }

        $user = new User();
        $user->u_username = $username;
        $user->u_password = $password;
        $user->u_role = 'admin';
        $user->tenants_t_id = null;
        $user->is_active = true;
        $user->must_change_password = true;
        $user->save();

        $this->info('สร้าง Admin สำเร็จ ต้องเปลี่ยนรหัสในการเข้าสู่ระบบครั้งแรก');

        return self::SUCCESS;
    }
}