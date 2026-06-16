<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/webhook.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método inválido.'], 405);
}

$visitorUuid = post_string('visitor_uuid', 36) ?: ($_COOKIE['vsl_visitor_uuid'] ?? '');
$name = post_string('name', 150);
$email = post_string('email', 190);
$phone = post_string('phone', 50);
$headlineId = (int)(post_string('headline_id', 20) ?: ($_COOKIE['vsl_headline_id'] ?? 0));
$offerId = (int)(post_string('offer_id', 20) ?: ($_COOKIE['vsl_offer_id'] ?? 0));

if ($name === '' || ($email === '' && $phone === '')) {
    json_response(['success' => false, 'message' => 'Informe nome e email ou telefone.'], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Email inválido.'], 422);
}
if (!preg_match('/^[a-f0-9-]{36}$/i', $visitorUuid)) {
    $visitorUuid = uuid_v4();
}

$pdo = db();
$headline = null;
if ($headlineId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM headlines WHERE id = ? LIMIT 1');
    $stmt->execute([$headlineId]);
    $headline = $stmt->fetch();
}
$offer = null;
if ($offerId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM offers WHERE id = ? LIMIT 1');
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();
}

$utm = utm_data();
$stmt = $pdo->prepare('INSERT INTO leads (visitor_uuid, name, email, phone, headline_id, offer_id, offer_name, offer_link, cash_price, installments_qty, installment_price, utm_source, utm_medium, utm_campaign, utm_content, utm_term, ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $visitorUuid,
    $name,
    $email,
    $phone,
    $headline['id'] ?? null,
    $offer['id'] ?? null,
    $offer['name'] ?? null,
    $offer['offer_link'] ?? null,
    $offer['cash_price'] ?? null,
    $offer['installments_qty'] ?? null,
    $offer['installment_price'] ?? null,
    $utm['utm_source'],
    $utm['utm_medium'],
    $utm['utm_campaign'],
    $utm['utm_content'],
    $utm['utm_term'],
    current_ip(),
    current_user_agent(),
]);
$leadId = (int)$pdo->lastInsertId();

$payload = [
    'event' => 'lead_created',
    'lead' => ['name' => $name, 'email' => $email, 'phone' => $phone],
    'visitor' => ['visitor_uuid' => $visitorUuid, 'ip' => current_ip(), 'user_agent' => current_user_agent()],
    'headline' => ['id' => $headline['id'] ?? null, 'title' => $headline['title'] ?? null],
    'offer' => [
        'id' => $offer['id'] ?? null,
        'name' => $offer['name'] ?? null,
        'offer_link' => $offer['offer_link'] ?? null,
        'cash_price' => $offer['cash_price'] ?? null,
        'installments_qty' => $offer['installments_qty'] ?? null,
        'installment_price' => $offer['installment_price'] ?? null,
    ],
    'utm' => $utm,
    'created_at' => date('Y-m-d H:i:s'),
];

send_lead_webhook($leadId, $payload);
json_response(['success' => true, 'lead_id' => $leadId]);

