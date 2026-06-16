<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$rows = db()->query('SELECT id, name, offer_link, cash_price, installments_qty, installment_price, description, is_active, weight, created_at, updated_at FROM offers ORDER BY id DESC')->fetchAll();
json_response(['success' => true, 'offers' => $rows]);

