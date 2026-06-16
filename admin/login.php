<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
start_admin_session();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = post_string('user', 100);
    $pass = post_string('password', 200);
    if ($user === ADMIN_USER && password_verify($pass, ADMIN_PASS_HASH)) {
        $_SESSION['admin_logged'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Usuário ou senha inválidos.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - VSL Smart</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="login-page">
    <main class="login-box">
        <h1>VSL Smart</h1>
        <p>Acesse o painel administrativo.</p>
        <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <label>Usuário
                <input type="text" name="user" required autofocus>
            </label>
            <label>Senha
                <input type="password" name="password" required>
            </label>
            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>

