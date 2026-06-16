<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método inválido.'], 405);
}

$visitorUuid = post_string('visitor_uuid', 36) ?: ($_COOKIE['vsl_visitor_uuid'] ?? '');
$leadId = (int)post_string('lead_id', 20);
$headlineId = (int)(post_string('headline_id', 20) ?: ($_COOKIE['vsl_headline_id'] ?? 0));
$offerId = (int)(post_string('offer_id', 20) ?: ($_COOKIE['vsl_offer_id'] ?? 0));
$buttonId = post_string('button_id', 100) ?: 'main_buy';

if (!preg_match('/^[a-f0-9-]{36}$/i', $visitorUuid)) {
    $visitorUuid = uuid_v4();
}

$stmt = db()->prepare('SELECT * FROM offers WHERE id = ? LIMIT 1');
$stmt->execute([$offerId]);
$offer = $stmt->fetch();
if (!$offer) {
    json_response(['success' => false, 'message' => 'Oferta não encontrada.'], 404);
}

$stmt = db()->prepare('INSERT INTO clicks (visitor_uuid, lead_id, headline_id, offer_id, offer_link, button_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $visitorUuid,
    $leadId > 0 ? $leadId : null,
    $headlineId > 0 ? $headlineId : null,
    $offerId,
    $offer['offer_link'],
    $buttonId,
]);

json_response(['success' => true, 'redirect_url' => $offer['offer_link']]);

