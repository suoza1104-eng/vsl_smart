<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método inválido.'], 405);
}

if (!should_track_visit()) {
    json_response(['success' => true, 'tracked' => false]);
}

$visitor = get_or_create_visitor();
$headlineId = (int)($_COOKIE['vsl_headline_id'] ?? 0);
$offerId = (int)($_COOKIE['vsl_offer_id'] ?? 0);

$stmt = db()->prepare('INSERT INTO visits (visitor_uuid, headline_id, offer_id, url, referrer, ip, user_agent, device_type, is_verified, utm_source, utm_medium, utm_campaign, utm_content, utm_term, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $visitor['visitor_uuid'],
    $headlineId ?: null,
    $offerId ?: null,
    post_string('url', 800),
    post_string('referrer', 800),
    current_ip(),
    current_user_agent(),
    device_type(),
    $visitor['utm_source'],
    $visitor['utm_medium'],
    $visitor['utm_campaign'],
    $visitor['utm_content'],
    $visitor['utm_term'],
]);

json_response(['success' => true, 'tracked' => true]);
