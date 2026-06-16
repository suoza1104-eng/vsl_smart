<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function send_lead_webhook(int $leadId, array $payload): void
{
    if (SUPERFUNCIONARIO_WEBHOOK_URL === '') {
        db()->prepare('INSERT INTO webhook_logs (lead_id, payload, response_body, http_status, success, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
            ->execute([$leadId, json_encode($payload, JSON_UNESCAPED_UNICODE), 'Webhook não configurado.', 0, 0]);
        return;
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = ['Content-Type: application/json'];
    if (SUPERFUNCIONARIO_TOKEN !== '') {
        $headers[] = 'Authorization: Bearer ' . SUPERFUNCIONARIO_TOKEN;
    }

    $ch = curl_init(SUPERFUNCIONARIO_WEBHOOK_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
    ]);

    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $success = $status >= 200 && $status < 300;
    db()->prepare('INSERT INTO webhook_logs (lead_id, payload, response_body, http_status, success, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
        ->execute([$leadId, $body, $response !== false ? $response : $error, $status, $success ? 1 : 0]);
}

