<?php
declare(strict_types=1);

define('DEBUG_MODE', true);

define('BASE_URL', 'https://seudominio.com');

define('DB_HOST', 'localhost');
define('DB_NAME', 'vsl_smart');
define('DB_USER', 'usuario_banco');
define('DB_PASS', 'senha_banco');
define('DB_CHARSET', 'utf8mb4');

define('ADMIN_USER', 'admin');
// Gere um novo hash em /install/install.php ou usando password_hash('sua-senha', PASSWORD_DEFAULT).
define('ADMIN_PASS_HASH', '$2y$10$5Yb6LdbYl0ZV.UzV6pgHcOuLcznsx3l4/mEKckCBeRXCmqwkCvg8K'); // admin123

define('SUPERFUNCIONARIO_WEBHOOK_URL', '');
define('SUPERFUNCIONARIO_TOKEN', '');

if (DEBUG_MODE) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

