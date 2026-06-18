<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    ensure_analytics_schema($pdo);

    return $pdo;
}

function ensure_analytics_schema(PDO $pdo): void
{
    $column = $pdo->query("SHOW COLUMNS FROM visits LIKE 'is_verified'")->fetch();
    if ($column) {
        return;
    }

    try {
        $pdo->exec('ALTER TABLE visits ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER device_type');
    } catch (PDOException $exception) {
        if ((int)($exception->errorInfo[1] ?? 0) !== 1060) {
            throw $exception;
        }
    }
}
