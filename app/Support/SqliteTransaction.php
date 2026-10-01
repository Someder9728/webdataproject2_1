<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use PDOException;

class SqliteTransaction
{
    public function run(Closure $callback): mixed
    {
        $connection = DB::connection();

        // ถ้ามี transaction ชั้นนอก ให้ชั้นนอกจัดการการลองใหม่
        if (
            $connection->getDriverName() !== 'sqlite' ||
            $connection->transactionLevel() > 0
        ) {
            return $connection->transaction($callback, 1);
        }

        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $connection->transaction($callback, 1);
            } catch (PDOException $exception) {
                $nativeCode = (int) ($exception->errorInfo[1] ?? 0);
                $primaryCode = $nativeCode & 0xff;

                // SQLITE_BUSY = 5, SQLITE_LOCKED = 6
                $isLockError = in_array($primaryCode, [5, 6], true);

                if (
                    ! $isLockError ||
                    $connection->transactionLevel() !== 0
                ) {
                    throw $exception;
                }

                if ($attempt === $maxAttempts) {
                    abort(
                        503,
                        'ฐานข้อมูลกำลังใช้งาน กรุณาลองใหม่อีกครั้ง'
                    );
                }

                // รอ 100, 200, 400, 800 milliseconds
                usleep(100_000 * (2 ** ($attempt - 1)));
            }
        }

        throw new \LogicException('Unexpected transaction state.');
    }
}