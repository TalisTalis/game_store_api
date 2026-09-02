<?php
namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $db   = getenv('DB_NAME') ?: 'gamestore';
            $user = getenv('DB_USER') ?: 'app';
            $pass = getenv('DB_PASS') ?: 'secret';

            $dsn = "pgsql:host=$host;port=5432;dbname=$db";
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$instance;
    }
}