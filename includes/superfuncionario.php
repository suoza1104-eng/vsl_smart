<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

const SF_EVENT_NEW_REGISTRATION = 'novo_cadastro';
const SF_EVENT_LEAD_CREATED = 'lead_created';
const SF_EVENT_PAYMENT_APPROVED = 'pagamento_aprovado';
const SF_EVENT_PAYMENT_PENDING = 'pagamento_pendente';
const SF_EVENT_PAYMENT_REFUSED = 'pagamento_recusado';
const SF_EVENT_SUBSCRIPTION_ACTIVE = 'assinatura_ativa';
const SF_EVENT_SUBSCRIPTION_CANCELLED = 'assinatura_cancelada';
const SF_EVENT_ACCESS_GRANTED = 'acesso_liberado';
const SF_EVENT_ACCESS_BLOCKED = 'acesso_bloqueado';
const SF_EVENT_PLAN_CHANGED = 'alteracao_plano_produto';

const SF_TAG_NEW_REGISTRATION = 'novo-cadastro';
const SF_TAG_LEAD = 'lead';
const SF_TAG_CUSTOMER = 'cliente';
const SF_TAG_SUBSCRIPTION_ACTIVE = 'assinatura-ativa';
const SF_TAG_SUBSCRIPTION_CANCELLED = 'assinatura-cancelada';
const SF_TAG_PAYMENT_APPROVED = 'pagamento-aprovado';
const SF_TAG_PAYMENT_PENDING = 'pagamento-pendente';
const SF_TAG_PAYMENT_REFUSED = 'pagamento-recusado';
const SF_TAG_ACCESS_GRANTED = 'acesso-liberado';
const SF_TAG_ACCESS_BLOCKED = 'acesso-bloqueado';

const SF_FIELD_USER_ID = 'user_id';
const SF_FIELD_CPF = 'cpf';
const SF_FIELD_PHONE = 'telefone';
const SF_FIELD_SUBSCRIPTION_STATUS = 'status_assinatura';
const SF_FIELD_PAYMENT_STATUS = 'status_pagamento';
const SF_FIELD_PLAN = 'plano';
const SF_FIELD_PRODUCT = 'produto';
const SF_FIELD_REGISTRATION_DATE = 'data_cadastro';
const SF_FIELD_PURCHASE_DATE = 'data_compra';
const SF_FIELD_EXPIRATION_DATE = 'data_expiracao';
const SF_FIELD_SOURCE = 'origem';
const SF_FIELD_LAST_EVENT = 'ultimo_evento';
const SF_FIELD_LAST_SYNC = 'ultima_sincronizacao';

function sf_config(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && trim((string)$value) !== '') {
        return trim((string)$value);
    }

    if (str_starts_with($key, 'SUPERFUNCIONARIO_')) {
        try {
            $settingValue = get_setting(strtolower($key));
            if (trim($settingValue) !== '') {
                return trim($settingValue);
            }
        } catch (Throwable $exception) {
            error_log('Falha ao ler configuração do SuperFuncionário: ' . $exception->getMessage());
        }
    }

    if (defined($key) && trim((string)constant($key)) !== '') {
        return trim((string)constant($key));
    }

    return $default;
}

function sf_is_enabled(): bool
{
    return sf_config('SUPERFUNCIONARIO_TOKEN') !== '';
}

function sf_default_tags_for_event(string $event, array $context = []): array
{
    $tagsByEvent = [
        SF_EVENT_NEW_REGISTRATION => [SF_TAG_NEW_REGISTRATION, SF_TAG_LEAD],
        SF_EVENT_LEAD_CREATED => [SF_TAG_NEW_REGISTRATION, SF_TAG_LEAD],
        SF_EVENT_PAYMENT_APPROVED => [SF_TAG_CUSTOMER, SF_TAG_PAYMENT_APPROVED, SF_TAG_ACCESS_GRANTED],
        SF_EVENT_PAYMENT_PENDING => [SF_TAG_LEAD, SF_TAG_PAYMENT_PENDING],
        SF_EVENT_PAYMENT_REFUSED => [SF_TAG_LEAD, SF_TAG_PAYMENT_REFUSED, SF_TAG_ACCESS_BLOCKED],
        SF_EVENT_SUBSCRIPTION_ACTIVE => [SF_TAG_CUSTOMER, SF_TAG_SUBSCRIPTION_ACTIVE, SF_TAG_ACCESS_GRANTED],
        SF_EVENT_SUBSCRIPTION_CANCELLED => [SF_TAG_SUBSCRIPTION_CANCELLED, SF_TAG_ACCESS_BLOCKED],
        SF_EVENT_ACCESS_GRANTED => [SF_TAG_ACCESS_GRANTED],
        SF_EVENT_ACCESS_BLOCKED => [SF_TAG_ACCESS_BLOCKED],
        SF_EVENT_PLAN_CHANGED => [],
    ];

    $tags = $tagsByEvent[$event] ?? [];
    foreach (['plano' => 'plano-', 'produto' => 'produto-'] as $key => $prefix) {
        if (!empty($context[$key])) {
            $tags[] = $prefix . sf_slug((string)$context[$key]);
        }
    }

    return array_values(array_unique(array_filter($tags)));
}

function sf_build_contact_payload(array $contact): array
{
    $name = trim((string)($contact['name'] ?? $contact['nome'] ?? ''));
    [$firstName, $lastName] = sf_split_name($name);

    return array_filter([
        'name' => $name,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => trim((string)($contact['email'] ?? '')),
        'phone' => sf_normalize_phone((string)($contact['phone'] ?? $contact['telefone'] ?? '')),
        'cpf' => trim((string)($contact['cpf'] ?? '')),
        'external_id' => (string)($contact['user_id'] ?? $contact['id'] ?? $contact['lead_id'] ?? ''),
    ], static fn($value): bool => $value !== '');
}

function sf_build_custom_fields(array $context): array
{
    $fields = [
        SF_FIELD_USER_ID => $context['user_id'] ?? $context['lead_id'] ?? null,
        SF_FIELD_CPF => $context['cpf'] ?? null,
        SF_FIELD_PHONE => sf_normalize_phone((string)($context['telefone'] ?? $context['phone'] ?? '')),
        SF_FIELD_SUBSCRIPTION_STATUS => $context['status_assinatura'] ?? null,
        SF_FIELD_PAYMENT_STATUS => $context['status_pagamento'] ?? null,
        SF_FIELD_PLAN => $context['plano'] ?? null,
        SF_FIELD_PRODUCT => $context['produto'] ?? $context['offer_name'] ?? null,
        SF_FIELD_REGISTRATION_DATE => $context['data_cadastro'] ?? $context['created_at'] ?? null,
        SF_FIELD_PURCHASE_DATE => $context['data_compra'] ?? null,
        SF_FIELD_EXPIRATION_DATE => $context['data_expiracao'] ?? null,
        SF_FIELD_SOURCE => $context['origem'] ?? $context['utm_source'] ?? null,
        SF_FIELD_LAST_EVENT => $context['ultimo_evento'] ?? $context['event'] ?? null,
        SF_FIELD_LAST_SYNC => date('Y-m-d H:i:s'),
    ];

    return array_filter($fields, static fn($value): bool => $value !== null && $value !== '');
}

function sf_validate_contact(array $contact, bool $requirePhoneForCreate = false): array
{
    $errors = [];
    if (($contact['name'] ?? '') === '') {
        $errors[] = 'Nome do contato ausente.';
    }
    if (($contact['email'] ?? '') === '' && ($contact['phone'] ?? '') === '') {
        $errors[] = 'Contato precisa ter email ou telefone.';
    }
    if ($requirePhoneForCreate && ($contact['phone'] ?? '') === '') {
        $errors[] = 'SuperFuncionário exige telefone para criar contato.';
    }
    if (($contact['email'] ?? '') !== '' && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email do contato inválido.';
    }

    return $errors;
}

function sf_create_or_update_contact(array $contact, array $context = []): array
{
    $builtContact = sf_build_contact_payload($contact);
    $errors = sf_validate_contact($builtContact);
    if ($errors) {
        sf_log_attempt((int)($context['lead_id'] ?? 0), ['contact' => $builtContact], implode(' ', $errors), 0, false);
        return ['success' => false, 'status' => 0, 'body' => implode(' ', $errors)];
    }

    $contactId = sf_find_contact_id($builtContact, (int)($context['lead_id'] ?? 0));
    if ($contactId > 0) {
        sf_update_custom_fields_by_id($contactId, sf_build_custom_fields($context), (int)($context['lead_id'] ?? 0));
        return ['success' => true, 'status' => 200, 'body' => 'Contato atualizado.', 'contact_id' => $contactId];
    }

    return sf_create_contact($builtContact, [], sf_build_custom_fields($context), (int)($context['lead_id'] ?? 0));
}

function sf_apply_tags(array $contact, array $tags, array $context = []): array
{
    $contactId = sf_contact_id_or_create($contact, $context);
    if ($contactId <= 0) {
        return ['success' => false, 'status' => 0, 'body' => 'Contato não encontrado ou criado.'];
    }

    $success = true;
    foreach (array_values(array_unique(array_filter($tags))) as $tag) {
        $tagId = sf_tag_id((string)$tag, (int)($context['lead_id'] ?? 0));
        if ($tagId <= 0) {
            $success = false;
            continue;
        }
        $response = sf_request('POST', '/contacts/' . $contactId . '/tags/' . $tagId, null, 'none', (int)($context['lead_id'] ?? 0));
        $success = $success && $response['success'];
    }

    return ['success' => $success, 'status' => $success ? 200 : 0, 'body' => $success ? 'Tags aplicadas.' : 'Falha ao aplicar uma ou mais tags.'];
}

function sf_remove_tags(array $contact, array $tags, array $context = []): array
{
    $contactId = sf_find_contact_id(sf_build_contact_payload($contact), (int)($context['lead_id'] ?? 0));
    if ($contactId <= 0) {
        return ['success' => false, 'status' => 0, 'body' => 'Contato não encontrado.'];
    }

    $success = true;
    foreach (array_values(array_unique(array_filter($tags))) as $tag) {
        $tagId = sf_tag_id((string)$tag, (int)($context['lead_id'] ?? 0), false);
        if ($tagId <= 0) {
            continue;
        }
        $response = sf_request('DELETE', '/contacts/' . $contactId . '/tags/' . $tagId, null, 'none', (int)($context['lead_id'] ?? 0));
        $success = $success && $response['success'];
    }

    return ['success' => $success, 'status' => $success ? 200 : 0, 'body' => $success ? 'Tags removidas.' : 'Falha ao remover uma ou mais tags.'];
}

function sf_update_custom_fields(array $contact, array $fields, array $context = []): array
{
    $contactId = sf_contact_id_or_create($contact, $context);
    if ($contactId <= 0) {
        return ['success' => false, 'status' => 0, 'body' => 'Contato não encontrado ou criado.'];
    }

    return sf_update_custom_fields_by_id($contactId, $fields, (int)($context['lead_id'] ?? 0));
}

function sf_sync_contact_event(string $event, array $contact, array $context = [], array $extraTags = []): array
{
    $context['event'] = $event;
    $builtContact = sf_build_contact_payload($contact);
    $tags = array_values(array_unique(array_merge(sf_default_tags_for_event($event, $context), $extraTags)));
    $fields = sf_build_custom_fields($context);
    $leadId = (int)($context['lead_id'] ?? 0);

    $errors = sf_validate_contact($builtContact);
    if ($errors) {
        sf_log_attempt($leadId, ['event' => $event, 'contact' => $builtContact, 'tags' => $tags, 'custom_fields' => $fields], implode(' ', $errors), 0, false);
        return ['success' => false, 'status' => 0, 'body' => implode(' ', $errors)];
    }

    $contactId = sf_find_contact_id($builtContact, $leadId);
    if ($contactId > 0) {
        $fieldsResult = sf_update_custom_fields_by_id($contactId, $fields, $leadId);
        $tagsResult = sf_apply_tags(['id' => $contactId] + $builtContact, $tags, $context);
        return [
            'success' => $fieldsResult['success'] && $tagsResult['success'],
            'status' => 200,
            'body' => 'Contato sincronizado.',
            'contact_id' => $contactId,
        ];
    }

    return sf_create_contact($builtContact, $tags, $fields, $leadId);
}

function sf_contact_id_or_create(array $contact, array $context = []): int
{
    if (!empty($contact['id']) && is_numeric($contact['id'])) {
        return (int)$contact['id'];
    }

    $builtContact = sf_build_contact_payload($contact);
    $leadId = (int)($context['lead_id'] ?? 0);
    $contactId = sf_find_contact_id($builtContact, $leadId);
    if ($contactId > 0) {
        return $contactId;
    }

    $response = sf_create_contact($builtContact, [], sf_build_custom_fields($context), $leadId);
    return (int)($response['contact_id'] ?? 0);
}

function sf_find_contact_id(array $contact, int $leadId = 0): int
{
    foreach (['email', 'phone'] as $field) {
        if (empty($contact[$field])) {
            continue;
        }

        $response = sf_request('GET', '/contacts/find_by_custom_field?field_id=' . rawurlencode($field) . '&value=' . rawurlencode((string)$contact[$field]), null, 'none', $leadId);
        if (!$response['success']) {
            continue;
        }

        $body = json_decode($response['body'], true);
        $items = is_array($body['data'] ?? null) ? $body['data'] : [];
        if (!empty($items[0]['id'])) {
            return (int)$items[0]['id'];
        }
    }

    return 0;
}

function sf_create_contact(array $contact, array $tags = [], array $fields = [], int $leadId = 0): array
{
    $errors = sf_validate_contact($contact, true);
    if ($errors) {
        sf_log_attempt($leadId, ['contact' => $contact, 'tags' => $tags, 'custom_fields' => $fields], implode(' ', $errors), 0, false);
        return ['success' => false, 'status' => 0, 'body' => implode(' ', $errors)];
    }

    $actions = [];
    foreach ($tags as $tag) {
        $actions[] = ['action' => 'add_tag', 'tag_name' => (string)$tag];
    }
    foreach ($fields as $field => $value) {
        $actions[] = ['action' => 'set_field_value', 'field_name' => (string)$field, 'value' => (string)$value];
    }

    $payload = array_filter([
        'phone' => $contact['phone'] ?? '',
        'email' => $contact['email'] ?? '',
        'first_name' => $contact['first_name'] ?? '',
        'last_name' => $contact['last_name'] ?? '',
        'actions' => $actions,
    ], static fn($value): bool => $value !== '' && $value !== []);

    $response = sf_request('POST', '/contacts', $payload, 'json', $leadId);
    if ($response['success']) {
        $body = json_decode($response['body'], true);
        $response['contact_id'] = (int)($body['id'] ?? $body['contact_id'] ?? $body['user_id'] ?? 0);
    }

    return $response;
}

function sf_update_custom_fields_by_id(int $contactId, array $fields, int $leadId = 0): array
{
    $success = true;
    foreach (array_filter($fields, static fn($value): bool => $value !== null && $value !== '') as $field => $value) {
        $fieldId = sf_custom_field_id((string)$field, $leadId);
        if ($fieldId <= 0) {
            $success = false;
            continue;
        }
        $response = sf_request('POST', '/contacts/' . $contactId . '/custom_fields/' . $fieldId, ['value' => (string)$value], 'form', $leadId);
        $success = $success && $response['success'];
    }

    return ['success' => $success, 'status' => $success ? 200 : 0, 'body' => $success ? 'Campos atualizados.' : 'Falha ao atualizar um ou mais campos.'];
}

function sf_tag_id(string $tagName, int $leadId = 0, bool $createIfMissing = true): int
{
    $response = sf_request('GET', '/accounts/tags/name/' . rawurlencode($tagName), null, 'none', $leadId);
    if ($response['success']) {
        $body = json_decode($response['body'], true);
        if (!empty($body['id'])) {
            return (int)$body['id'];
        }
    }

    if (!$createIfMissing) {
        return 0;
    }

    $response = sf_request('POST', '/accounts/tags', ['name' => $tagName], 'form', $leadId);
    $body = json_decode($response['body'], true);
    return (int)($body['id'] ?? 0);
}

function sf_custom_field_id(string $fieldName, int $leadId = 0): int
{
    $response = sf_request('GET', '/accounts/custom_fields/name/' . rawurlencode($fieldName), null, 'none', $leadId);
    if ($response['success']) {
        $body = json_decode($response['body'], true);
        if (!empty($body['id'])) {
            return (int)$body['id'];
        }
    }

    $response = sf_request('POST', '/accounts/custom_fields', ['name' => $fieldName, 'type' => 0], 'json', $leadId);
    $body = json_decode($response['body'], true);
    return (int)($body['id'] ?? 0);
}

function sf_request(string $method, string $path, ?array $payload = null, string $bodyType = 'json', int $leadId = 0): array
{
    $logPayload = ['method' => $method, 'path' => $path, 'payload' => $payload];

    if (!sf_is_enabled()) {
        sf_log_attempt($leadId, $logPayload, 'SuperFuncionário não configurado: SUPERFUNCIONARIO_TOKEN ausente.', 0, false);
        return ['success' => false, 'status' => 0, 'body' => 'SuperFuncionário não configurado: SUPERFUNCIONARIO_TOKEN ausente.'];
    }

    if (!function_exists('curl_init')) {
        sf_log_attempt($leadId, $logPayload, 'Extensão cURL não disponível.', 0, false);
        return ['success' => false, 'status' => 0, 'body' => 'Extensão cURL não disponível.'];
    }

    $baseUrl = rtrim(sf_config('SUPERFUNCIONARIO_BASE_URL', 'https://app.superfuncionario.com.br/api'), '/');
    $url = $baseUrl . '/' . ltrim($path, '/');
    $headers = ['Accept: application/json', 'X-ACCESS-TOKEN: ' . sf_config('SUPERFUNCIONARIO_TOKEN')];
    $body = null;

    if ($payload !== null && $bodyType === 'json') {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers[] = 'Content-Type: application/json';
    } elseif ($payload !== null && $bodyType === 'form') {
        $body = http_build_query($payload);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => (int)sf_config('SUPERFUNCIONARIO_CONNECT_TIMEOUT', '4'),
        CURLOPT_TIMEOUT => (int)sf_config('SUPERFUNCIONARIO_TIMEOUT', '10'),
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $responseBody = $response !== false ? (string)$response : $error;
    $success = $status >= 200 && $status < 300;
    sf_log_attempt($leadId, $logPayload, $responseBody, $status, $success);

    if (!$success) {
        error_log(sprintf('SuperFuncionário falhou: method=%s path=%s status=%d response=%s', $method, $path, $status, mb_substr($responseBody, 0, 500)));
    }

    return ['success' => $success, 'status' => $status, 'body' => $responseBody];
}

function sf_log_attempt(int $leadId, array $payload, string $responseBody, int $status, bool $success): void
{
    try {
        db()->prepare('INSERT INTO webhook_logs (lead_id, payload, response_body, http_status, success, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
            ->execute([
                $leadId,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                mb_substr($responseBody, 0, 60000),
                $status,
                $success ? 1 : 0,
            ]);
    } catch (Throwable $exception) {
        error_log('Falha ao registrar log do SuperFuncionário: ' . $exception->getMessage());
    }
}

function sf_split_name(string $name): array
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $firstName = (string)array_shift($parts);
    return [$firstName, implode(' ', $parts)];
}

function sf_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '55')) {
        return '+' . $digits;
    }
    return '+55' . $digits;
}

function sf_slug(string $value): string
{
    if (function_exists('iconv')) {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    }
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}
