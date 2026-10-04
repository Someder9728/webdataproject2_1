<?php

namespace App\Support;

use PDO;
use PDOStatement;
use RuntimeException;

final class SqliteQuery
{
    public static function run(PDO $connection, string $sql): PDOStatement
    {
        $statement = $connection->query($sql);

        if ($statement === false) {
            throw new RuntimeException('SQLite query failed.');
        }

        return $statement;
    }
}
