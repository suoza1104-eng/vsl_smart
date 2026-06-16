<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$rows = db()->query('SELECT id, title, description, is_active, weight, created_at, updated_at FROM headlines ORDER BY id DESC')->fetchAll();
json_response(['success' => true, 'headlines' => $rows]);

