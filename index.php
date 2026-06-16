<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$visitor = get_or_create_visitor();
$headline = active_by_cookie_or_pick('headlines', 'vsl_headline_id');
$offer = active_by_cookie_or_pick('offers', 'vsl_offer_id');
$videoEmbed = get_setting('vturb_embed');

$currentUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://')
    . ($_SERVER['HTTP_HOST'] ?? '')
    . ($_SERVER['REQUEST_URI'] ?? '');
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

$stmt = db()->prepare('INSERT INTO visits (visitor_uuid, headline_id, offer_id, url, referrer, ip, user_agent, device_type, utm_source, utm_medium, utm_campaign, utm_content, utm_term, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $visitor['visitor_uuid'],
    $headline['id'] ?? null,
    $offer['id'] ?? null,
    mb_substr($currentUrl, 0, 800),
    mb_substr($referrer, 0, 800),
    current_ip(),
    current_user_agent(),
    device_type(),
    $visitor['utm_source'],
    $visitor['utm_medium'],
    $visitor['utm_campaign'],
    $visitor['utm_content'],
    $visitor['utm_term'],
]);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VSL Smart</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="vsl-page">
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">Apresentação exclusiva</p>
                <h1><?= e($headline['title'] ?? 'Assista ao vídeo completo') ?></h1>
                <?php if (!empty($headline['description'])): ?>
                    <p class="subheadline"><?= e($headline['description']) ?></p>
                <?php endif; ?>
            </div>

            <div class="video-shell">
                <?= render_video_embed($videoEmbed) ?>
            </div>
        </section>

        <section class="conversion-grid">
            <form id="leadForm" class="lead-form">
                <h2>Receba o acesso e a condição especial</h2>
                <input type="hidden" name="visitor_uuid" value="<?= e($visitor['visitor_uuid']) ?>">
                <input type="hidden" name="headline_id" value="<?= e((string)($headline['id'] ?? '')) ?>">
                <input type="hidden" name="offer_id" value="<?= e((string)($offer['id'] ?? '')) ?>">
                <label>Nome
                    <input type="text" name="name" autocomplete="name" required>
                </label>
                <label>Email
                    <input type="email" name="email" autocomplete="email">
                </label>
                <label>WhatsApp
                    <input type="tel" name="phone" autocomplete="tel">
                </label>
                <button type="submit">Quero receber</button>
                <p id="leadMessage" class="form-message"></p>
            </form>

            <aside class="offer-box">
                <span>Oferta liberada</span>
                <h2><?= e($offer['name'] ?? 'Oferta especial') ?></h2>
                <?php if (!empty($offer['description'])): ?>
                    <p><?= e($offer['description']) ?></p>
                <?php endif; ?>
                <div class="price">
                    <strong><?= e(money_br($offer['cash_price'] ?? 0)) ?></strong>
                    <small>ou <?= e((string)($offer['installments_qty'] ?? 1)) ?>x de <?= e(money_br($offer['installment_price'] ?? 0)) ?></small>
                </div>
                <button
                    id="buyButton"
                    class="buy-button"
                    data-button-id="main_buy"
                    data-visitor-uuid="<?= e($visitor['visitor_uuid']) ?>"
                    data-headline-id="<?= e((string)($headline['id'] ?? '')) ?>"
                    data-offer-id="<?= e((string)($offer['id'] ?? '')) ?>"
                >Comprar agora</button>
            </aside>
        </section>
    </main>

    <script>
        window.VSL_SMART = {
            visitorUuid: <?= json_encode($visitor['visitor_uuid']) ?>,
            headlineId: <?= json_encode($headline['id'] ?? null) ?>,
            offerId: <?= json_encode($offer['id'] ?? null) ?>
        };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>

