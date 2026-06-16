<?php
declare(strict_types=1);

require_once __DIR__ . '/superfuncionario.php';

function send_lead_webhook(int $leadId, array $payload): void
{
    $lead = $payload['lead'] ?? [];
    $offer = $payload['offer'] ?? [];
    $utm = $payload['utm'] ?? [];

    $contact = [
        'lead_id' => $leadId,
        'name' => $lead['name'] ?? '',
        'email' => $lead['email'] ?? '',
        'phone' => $lead['phone'] ?? '',
    ];

    $context = [
        'lead_id' => $leadId,
        'user_id' => $leadId,
        'produto' => $offer['name'] ?? null,
        'offer_name' => $offer['name'] ?? null,
        'data_cadastro' => $payload['created_at'] ?? date('Y-m-d H:i:s'),
        'origem' => $utm['utm_source'] ?? null,
        'utm_source' => $utm['utm_source'] ?? null,
        'utm_medium' => $utm['utm_medium'] ?? null,
        'utm_campaign' => $utm['utm_campaign'] ?? null,
        'utm_content' => $utm['utm_content'] ?? null,
        'utm_term' => $utm['utm_term'] ?? null,
        'ultimo_evento' => SF_EVENT_LEAD_CREATED,
        'raw_payload' => $payload,
    ];

    sf_sync_contact_event(SF_EVENT_LEAD_CREATED, $contact, $context);
}
