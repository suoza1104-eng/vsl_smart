<?php
declare(strict_types=1);

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

function define_config(string $name, mixed $default): void
{
    if (!defined($name)) {
        define($name, $default);
    }
}

define_config('DEBUG_MODE', true);

define_config('BASE_URL', 'https://seudominio.com');

define_config('DB_HOST', 'localhost');
define_config('DB_NAME', 'vsl_smart');
define_config('DB_USER', 'usuario_banco');
define_config('DB_PASS', 'senha_banco');
define_config('DB_CHARSET', 'utf8mb4');

define_config('ADMIN_USER', 'admin');
// Gere um novo hash em /install/install.php ou usando password_hash('sua-senha', PASSWORD_DEFAULT).
define_config('ADMIN_PASS_HASH', '$2y$10$y1JvF415eXBTVUl1ccIre.Rsh0qtMBIA7IQ7I6xJYD54KSDNDur7S'); // admin123

define_config('SUPERFUNCIONARIO_WEBHOOK_URL', '');
define_config('SUPERFUNCIONARIO_TOKEN', '');

if (DEBUG_MODE) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
