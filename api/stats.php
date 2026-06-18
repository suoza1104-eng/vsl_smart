<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_admin();

function stats_where(string $table): array
{
    $where = [];
    $params = [];
    if ($table === 'visits') {
        [$verified, $notNull, $notEmpty, $notBot, $botPattern] = human_visit_sql();
        $where[] = $verified;
        $where[] = $notNull;
        $where[] = $notEmpty;
        $where[] = $notBot;
        $params[] = $botPattern;
    }
    $dateStart = get_string('date_start', 20);
    $dateEnd = get_string('date_end', 20);
    if ($dateStart !== '') {
        $where[] = 'created_at >= ?';
        $params[] = $dateStart . ' 00:00:00';
    }
    if ($dateEnd !== '') {
        $where[] = 'created_at <= ?';
        $params[] = $dateEnd . ' 23:59:59';
    }
    foreach (['headline_id', 'offer_id'] as $key) {
        $value = get_string($key, 20);
        if ($value !== '') {
            $where[] = "{$key} = ?";
            $params[] = $value;
        }
    }
    foreach (['utm_source', 'utm_campaign'] as $key) {
        $value = get_string($key, 150);
        if ($value !== '' && in_array($table, ['visits', 'leads'], true)) {
            $where[] = "{$key} = ?";
            $params[] = $value;
        }
    }
    $device = get_string('device_type', 20);
    if ($device !== '' && $table === 'visits') {
        $where[] = 'device_type = ?';
        $params[] = $device;
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

$pdo = db();

[$sqlWhere, $params] = stats_where('visits');
$stmt = $pdo->prepare("SELECT DATE(created_at) label, COUNT(*) total FROM visits{$sqlWhere} GROUP BY DATE(created_at) ORDER BY label");
$stmt->execute($params);
$visitsByDay = $stmt->fetchAll();

[$sqlWhere, $params] = stats_where('leads');
$stmt = $pdo->prepare("SELECT DATE(created_at) label, COUNT(*) total FROM leads{$sqlWhere} GROUP BY DATE(created_at) ORDER BY label");
$stmt->execute($params);
$leadsByDay = $stmt->fetchAll();

[$sqlWhere, $params] = stats_where('clicks');
$stmt = $pdo->prepare("SELECT DATE(created_at) label, COUNT(*) total FROM clicks{$sqlWhere} GROUP BY DATE(created_at) ORDER BY label");
$stmt->execute($params);
$clicksByDay = $stmt->fetchAll();

json_response([
    'success' => true,
    'visits_by_day' => $visitsByDay,
    'leads_by_day' => $leadsByDay,
    'clicks_by_day' => $clicksByDay,
]);
