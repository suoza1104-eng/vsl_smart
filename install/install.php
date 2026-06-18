<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

$message = '';
$hash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = post_string('password', 200);
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalação VSL Smart</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="install-page">
    <main class="login-box">
        <h1>VSL Smart</h1>
        <p>Importe o arquivo <code>install/database.sql</code> pelo phpMyAdmin. Use este gerador para criar o hash da senha admin.</p>
        <form method="post">
            <label>Nova senha admin
                <input type="password" name="password" required>
            </label>
            <button type="submit">Gerar hash</button>
        </form>
        <?php if ($hash): ?>
            <p>Copie este valor para <code>ADMIN_PASS_HASH</code> em <code>includes/config.local.php</code>:</p>
            <textarea rows="4" readonly><?= e($hash) ?></textarea>
        <?php endif; ?>
    </main>
</body>
</html>
