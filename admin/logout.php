<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
start_admin_session();
session_destroy();
header('Location: login.php');
exit;

