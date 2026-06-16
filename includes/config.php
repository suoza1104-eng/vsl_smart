<?php
declare(strict_types=1);

define('DEBUG_MODE', true);

define('BASE_URL', getenv('BASE_URL') ?: 'https://seudominio.com');

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'vsl_smart');
define('DB_USER', getenv('DB_USER') ?: 'usuario_banco');
define('DB_PASS', getenv('DB_PASS') ?: 'senha_banco');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

define('ADMIN_USER', getenv('ADMIN_USER') ?: 'admin');
// Gere um novo hash em /install/install.php ou usando password_hash('sua-senha', PASSWORD_DEFAULT).
define('ADMIN_PASS_HASH', getenv('ADMIN_PASS_HASH') ?: '$2y$10$5Yb6LdbYl0ZV.UzV6pgHcOuLcznsx3l4/mEKckCBeRXCmqwkCvg8K'); // admin123

define('SUPERFUNCIONARIO_BASE_URL', getenv('SUPERFUNCIONARIO_BASE_URL') ?: 'https://app.superfuncionario.com.br/api');
define('SUPERFUNCIONARIO_TOKEN', getenv('SUPERFUNCIONARIO_TOKEN') ?: '');
define('SUPERFUNCIONARIO_TIMEOUT', getenv('SUPERFUNCIONARIO_TIMEOUT') ?: '10');
define('SUPERFUNCIONARIO_CONNECT_TIMEOUT', getenv('SUPERFUNCIONARIO_CONNECT_TIMEOUT') ?: '4');

if (DEBUG_MODE) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
