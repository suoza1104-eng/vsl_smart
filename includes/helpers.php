<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function post_string(string $key, int $max = 255): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    return mb_substr($value, 0, $max);
}

function get_string(string $key, int $max = 255): string
{
    $value = trim((string)($_GET[$key] ?? ''));
    return mb_substr($value, 0, $max);
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function current_ip(): string
{
    return mb_substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
}

function current_user_agent(): string
{
    return mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
}

function bot_user_agent_pattern(): string
{
    return implode('|', [
        'googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'duckduckbot', 'applebot',
        'facebookexternalhit', 'facebot', 'twitterbot', 'linkedinbot', 'slackbot',
        'discordbot', 'telegrambot', 'whatsapp', 'semrushbot', 'ahrefsbot', 'mj12bot',
        'dotbot', 'petalbot', 'bytespider', 'amazonbot', 'claudebot', 'gptbot',
        'chatgpt-user', 'oai-searchbot', 'perplexitybot', 'ccbot', 'ia_archiver',
        'archive.org_bot', 'crawler', 'spider', 'scrapy', 'headlesschrome', 'phantomjs',
        'selenium', 'playwright', 'puppeteer', 'lighthouse', 'pagespeed', 'gtmetrix',
        'pingdom', 'uptimerobot', 'statuscake', 'site24x7', 'curl', 'wget',
        'python-requests', 'python-urllib', 'aiohttp', 'go-http-client', 'libwww-perl',
        'postmanruntime', 'insomnia', 'nikto', 'sqlmap', 'masscan', 'zgrab', 'censys',
        'shodan', 'feedfetcher',
    ]);
}

function is_probable_bot(?string $userAgent = null): bool
{
    $userAgent = trim($userAgent ?? current_user_agent());
    if ($userAgent === '') {
        return true;
    }

    return preg_match('~' . bot_user_agent_pattern() . '~i', $userAgent) === 1;
}

function should_track_visit(): bool
{
    return !isset($_GET['preview']) && !is_probable_bot();
}

function human_visit_sql(string $alias = ''): array
{
    $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';

    return [
        "{$prefix}is_verified = 1",
        "{$prefix}user_agent IS NOT NULL",
        "{$prefix}user_agent <> ''",
        "LOWER({$prefix}user_agent) NOT REGEXP ?",
        bot_user_agent_pattern(),
    ];
}

function device_type(?string $ua = null): string
{
    $ua = strtolower($ua ?? current_user_agent());
    if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
        return 'tablet';
    }
    if (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
        return 'mobile';
    }
    return 'desktop';
}

function utm_data(): array
{
    $keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    $data = [];
    foreach ($keys as $key) {
        $data[$key] = get_string($key, 150);
    }
    return $data;
}

function cookie_options(int $days = 30): array
{
    return [
        'expires' => time() + ($days * 86400),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ];
}

function get_or_create_visitor(): array
{
    $uuid = $_COOKIE['vsl_visitor_uuid'] ?? '';
    if (!preg_match('/^[a-f0-9-]{36}$/i', $uuid)) {
        $uuid = uuid_v4();
        setcookie('vsl_visitor_uuid', $uuid, cookie_options());
        $_COOKIE['vsl_visitor_uuid'] = $uuid;
    }

    $utm = utm_data();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM visitors WHERE visitor_uuid = ? LIMIT 1');
    $stmt->execute([$uuid]);
    $visitor = $stmt->fetch();

    if ($visitor) {
        $pdo->prepare('UPDATE visitors SET last_seen_at = NOW(), ip = ?, user_agent = ?, device_type = ? WHERE visitor_uuid = ?')
            ->execute([current_ip(), current_user_agent(), device_type(), $uuid]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO visitors (visitor_uuid, first_seen_at, last_seen_at, ip, user_agent, device_type, utm_source, utm_medium, utm_campaign, utm_content, utm_term) VALUES (?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $uuid,
            current_ip(),
            current_user_agent(),
            device_type(),
            $utm['utm_source'],
            $utm['utm_medium'],
            $utm['utm_campaign'],
            $utm['utm_content'],
            $utm['utm_term'],
        ]);
    }

    return ['visitor_uuid' => $uuid] + $utm;
}

function weighted_pick(string $table): ?array
{
    if (!in_array($table, ['headlines', 'offers'], true)) {
        return null;
    }

    $rows = db()->query("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY id ASC")->fetchAll();
    if (!$rows) {
        return null;
    }

    $total = 0;
    foreach ($rows as $row) {
        $total += max(1, (int)$row['weight']);
    }

    $pick = random_int(1, $total);
    foreach ($rows as $row) {
        $pick -= max(1, (int)$row['weight']);
        if ($pick <= 0) {
            return $row;
        }
    }

    return $rows[0];
}

function active_by_cookie_or_pick(string $table, string $cookie): ?array
{
    $pdo = db();
    $id = (int)($_COOKIE[$cookie] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    $row = weighted_pick($table);
    if ($row) {
        setcookie($cookie, (string)$row['id'], cookie_options());
        $_COOKIE[$cookie] = (string)$row['id'];
    }
    return $row;
}

function get_setting(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
    $stmt->execute([$key, $value]);
}

function render_video_embed(string $videoValue): string
{
    $videoValue = trim($videoValue);
    if ($videoValue === '') {
        return '<div class="video-placeholder">Configure o vídeo VSL no painel admin.</div>';
    }

    if (str_contains($videoValue, '<iframe') || str_contains($videoValue, '<script') || str_contains($videoValue, '<vturb-smartplayer')) {
        return $videoValue;
    }

    $url = filter_var($videoValue, FILTER_VALIDATE_URL) ? $videoValue : '';
    if ($url === '') {
        return '<div class="video-placeholder">Embed de vídeo inválido.</div>';
    }

    return '<iframe src="' . e($url) . '" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
}

function money_br($value): string
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}
