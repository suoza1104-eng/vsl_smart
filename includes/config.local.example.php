<?php
declare(strict_types=1);

// Copie este arquivo para config.local.php e preencha os dados da instalação.
// config.local.php é ignorado pelo Git e não será sobrescrito em atualizações.

define('DEBUG_MODE', false);
define('BASE_URL', 'https://seudominio.com');

define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_do_banco');
define('DB_USER', 'usuario_do_banco');
define('DB_PASS', 'senha_do_banco');

define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', 'cole_aqui_o_hash_gerado_em_install/install.php');

define('SUPERFUNCIONARIO_TOKEN', '');
