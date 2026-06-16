<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action', 50);

    if ($action === 'save_settings') {
        set_setting('vturb_embed', trim((string)($_POST['vturb_embed'] ?? '')));
        set_setting('superfuncionario_token', trim((string)($_POST['superfuncionario_token'] ?? '')));
        set_setting('superfuncionario_base_url', trim((string)($_POST['superfuncionario_base_url'] ?? '')));
        set_setting('superfuncionario_timeout', trim((string)($_POST['superfuncionario_timeout'] ?? '10')));
        set_setting('superfuncionario_connect_timeout', trim((string)($_POST['superfuncionario_connect_timeout'] ?? '4')));
        $message = 'Configurações salvas.';
    }

    if ($action === 'save_headline') {
        $id = (int)post_string('id', 20);
        $data = [
            post_string('title', 255),
            post_string('description', 2000),
            isset($_POST['is_active']) ? 1 : 0,
            max(1, (int)post_string('weight', 10)),
        ];
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE headlines SET title = ?, description = ?, is_active = ?, weight = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([...$data, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO headlines (title, description, is_active, weight, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute($data);
        }
        $message = 'Headline salva.';
    }

    if ($action === 'delete_headline') {
        $pdo->prepare('DELETE FROM headlines WHERE id = ?')->execute([(int)post_string('id', 20)]);
        $message = 'Headline excluída.';
    }

    if ($action === 'save_offer') {
        $id = (int)post_string('id', 20);
        $data = [
            post_string('name', 150),
            post_string('offer_link', 500),
            (float)str_replace(',', '.', post_string('cash_price', 20)),
            max(1, (int)post_string('installments_qty', 10)),
            (float)str_replace(',', '.', post_string('installment_price', 20)),
            post_string('description', 2000),
            isset($_POST['is_active']) ? 1 : 0,
            max(1, (int)post_string('weight', 10)),
        ];
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE offers SET name = ?, offer_link = ?, cash_price = ?, installments_qty = ?, installment_price = ?, description = ?, is_active = ?, weight = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([...$data, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO offers (name, offer_link, cash_price, installments_qty, installment_price, description, is_active, weight, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute($data);
        }
        $message = 'Oferta salva.';
    }

    if ($action === 'delete_offer') {
        $pdo->prepare('DELETE FROM offers WHERE id = ?')->execute([(int)post_string('id', 20)]);
        $message = 'Oferta excluída.';
    }
}

$filters = [
    'date_start' => get_string('date_start', 20),
    'date_end' => get_string('date_end', 20),
    'headline_id' => get_string('headline_id', 20),
    'offer_id' => get_string('offer_id', 20),
    'utm_source' => get_string('utm_source', 150),
    'utm_campaign' => get_string('utm_campaign', 150),
    'device_type' => get_string('device_type', 20),
    'button_id' => get_string('button_id', 100),
];

function table_where(array $filters, string $table): array
{
    $where = [];
    $params = [];
    if ($filters['date_start'] !== '') {
        $where[] = 'created_at >= ?';
        $params[] = $filters['date_start'] . ' 00:00:00';
    }
    if ($filters['date_end'] !== '') {
        $where[] = 'created_at <= ?';
        $params[] = $filters['date_end'] . ' 23:59:59';
    }
    foreach (['headline_id', 'offer_id'] as $key) {
        if ($filters[$key] !== '' && in_array($table, ['visits', 'leads', 'clicks'], true)) {
            $where[] = "{$key} = ?";
            $params[] = $filters[$key];
        }
    }
    foreach (['utm_source', 'utm_campaign'] as $key) {
        if ($filters[$key] !== '' && in_array($table, ['visits', 'leads'], true)) {
            $where[] = "{$key} = ?";
            $params[] = $filters[$key];
        }
    }
    if ($filters['device_type'] !== '' && $table === 'visits') {
        $where[] = 'device_type = ?';
        $params[] = $filters['device_type'];
    }
    if ($filters['button_id'] !== '' && $table === 'clicks') {
        $where[] = 'button_id = ?';
        $params[] = $filters['button_id'];
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function scalar_count(PDO $pdo, string $table, array $filters, string $expr = 'COUNT(*)'): int
{
    [$where, $params] = table_where($filters, $table);
    $stmt = $pdo->prepare("SELECT {$expr} FROM {$table}{$where}");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function percent(int $num, int $den): string
{
    return $den > 0 ? number_format(($num / $den) * 100, 2, ',', '.') . '%' : '0,00%';
}

function by_day(PDO $pdo, string $table, array $filters): array
{
    [$where, $params] = table_where($filters, $table);
    $stmt = $pdo->prepare("SELECT DATE(created_at) label, COUNT(*) total FROM {$table}{$where} GROUP BY DATE(created_at) ORDER BY label");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$headlines = $pdo->query('SELECT * FROM headlines ORDER BY id DESC')->fetchAll();
$offers = $pdo->query('SELECT * FROM offers ORDER BY id DESC')->fetchAll();
$buttonIds = $pdo->query("SELECT DISTINCT button_id FROM clicks WHERE button_id IS NOT NULL AND button_id <> '' ORDER BY button_id")->fetchAll(PDO::FETCH_COLUMN);

$totalVisits = scalar_count($pdo, 'visits', $filters);
$uniqueVisitors = scalar_count($pdo, 'visits', $filters, 'COUNT(DISTINCT visitor_uuid)');
$totalLeads = scalar_count($pdo, 'leads', $filters);
$totalClicks = scalar_count($pdo, 'clicks', $filters);

$visitsByDay = by_day($pdo, 'visits', $filters);
$leadsByDay = by_day($pdo, 'leads', $filters);
$clicksByDay = by_day($pdo, 'clicks', $filters);

[$visitWhere, $visitParams] = table_where($filters, 'visits');
$stmt = $pdo->prepare("SELECT h.id, h.title, h.description, h.is_active, h.weight, COUNT(DISTINCT v.id) visits, COUNT(DISTINCT l.id) leads, COUNT(DISTINCT c.id) clicks
    FROM headlines h
    LEFT JOIN visits v ON v.headline_id = h.id
    LEFT JOIN leads l ON l.headline_id = h.id
    LEFT JOIN clicks c ON c.headline_id = h.id
    GROUP BY h.id ORDER BY h.id DESC");
$stmt->execute();
$headlinePerf = $stmt->fetchAll();

$stmt = $pdo->query("SELECT o.id, o.name, o.offer_link, o.cash_price, o.installments_qty, o.installment_price, o.description, o.is_active, o.weight, COUNT(DISTINCT v.id) visits, COUNT(DISTINCT l.id) leads, COUNT(DISTINCT c.id) clicks
    FROM offers o
    LEFT JOIN visits v ON v.offer_id = o.id
    LEFT JOIN leads l ON l.offer_id = o.id
    LEFT JOIN clicks c ON c.offer_id = o.id
    GROUP BY o.id ORDER BY o.id DESC");
$offerPerf = $stmt->fetchAll();

$bestHeadline = '';
$bestHeadlineRate = -1;
foreach ($headlinePerf as $row) {
    $rate = (int)$row['visits'] > 0 ? (int)$row['leads'] / (int)$row['visits'] : 0;
    if ($rate > $bestHeadlineRate) {
        $bestHeadlineRate = $rate;
        $bestHeadline = $row['title'];
    }
}
$bestOfferCtr = '';
$bestOfferCtrRate = -1;
$bestOfferLead = '';
$bestOfferLeadRate = -1;
foreach ($offerPerf as $row) {
    $visits = (int)$row['visits'];
    $ctr = $visits > 0 ? (int)$row['clicks'] / $visits : 0;
    $leadRate = $visits > 0 ? (int)$row['leads'] / $visits : 0;
    if ($ctr > $bestOfferCtrRate) {
        $bestOfferCtrRate = $ctr;
        $bestOfferCtr = $row['name'];
    }
    if ($leadRate > $bestOfferLeadRate) {
        $bestOfferLeadRate = $leadRate;
        $bestOfferLead = $row['name'];
    }
}

$stmt = $pdo->query("SELECT l.*, h.title headline_title, c.created_at click_date
    FROM leads l
    LEFT JOIN headlines h ON h.id = l.headline_id
    LEFT JOIN clicks c ON c.visitor_uuid = l.visitor_uuid AND c.offer_id = l.offer_id
    GROUP BY l.id
    ORDER BY l.created_at DESC LIMIT 200");
$leads = $stmt->fetchAll();

$webhookLogs = $pdo->query('SELECT * FROM webhook_logs ORDER BY id DESC LIMIT 30')->fetchAll();
$vturbEmbed = get_setting('vturb_embed');
$superfuncionarioToken = get_setting('superfuncionario_token');
$superfuncionarioBaseUrl = get_setting('superfuncionario_base_url', 'https://app.superfuncionario.com.br/api');
$superfuncionarioTimeout = get_setting('superfuncionario_timeout', '10');
$superfuncionarioConnectTimeout = get_setting('superfuncionario_connect_timeout', '4');
$chartData = [
    'visitsByDay' => $visitsByDay,
    'leadsByDay' => $leadsByDay,
    'clicksByDay' => $clicksByDay,
    'headlinePerf' => $headlinePerf,
    'offerPerf' => $offerPerf,
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - VSL Smart</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <aside class="sidebar">
        <strong>VSL Smart</strong>
        <a href="#metricas">Métricas</a>
        <a href="#config">Configurações</a>
        <a href="#headlines">Headlines</a>
        <a href="#ofertas">Ofertas</a>
        <a href="#leads">Leads</a>
        <a href="logout.php">Sair</a>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1>Dashboard</h1>
                <p>Acompanhe visitas, leads, cliques e testes.</p>
            </div>
            <a class="public-link" href="../index.php" target="_blank">Ver VSL</a>
        </header>

        <?php if ($message): ?><div class="success"><?= e($message) ?></div><?php endif; ?>

        <section class="panel">
            <form class="filters" method="get">
                <label>Início<input type="date" name="date_start" value="<?= e($filters['date_start']) ?>"></label>
                <label>Fim<input type="date" name="date_end" value="<?= e($filters['date_end']) ?>"></label>
                <label>Headline<select name="headline_id"><option value="">Todas</option><?php foreach ($headlines as $h): ?><option value="<?= e((string)$h['id']) ?>" <?= $filters['headline_id'] === (string)$h['id'] ? 'selected' : '' ?>><?= e($h['title']) ?></option><?php endforeach; ?></select></label>
                <label>Oferta<select name="offer_id"><option value="">Todas</option><?php foreach ($offers as $o): ?><option value="<?= e((string)$o['id']) ?>" <?= $filters['offer_id'] === (string)$o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?></select></label>
                <label>UTM source<input type="text" name="utm_source" value="<?= e($filters['utm_source']) ?>"></label>
                <label>UTM campaign<input type="text" name="utm_campaign" value="<?= e($filters['utm_campaign']) ?>"></label>
                <label>Dispositivo<select name="device_type"><option value="">Todos</option><?php foreach (['desktop','mobile','tablet'] as $d): ?><option value="<?= $d ?>" <?= $filters['device_type'] === $d ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?></select></label>
                <label>Botão<select name="button_id"><option value="">Todos</option><?php foreach ($buttonIds as $button): ?><option value="<?= e($button) ?>" <?= $filters['button_id'] === $button ? 'selected' : '' ?>><?= e($button) ?></option><?php endforeach; ?></select></label>
                <button type="submit">Filtrar</button>
            </form>
        </section>

        <section id="metricas" class="cards">
            <div><span>Visitas</span><strong><?= $totalVisits ?></strong></div>
            <div><span>Visitantes únicos</span><strong><?= $uniqueVisitors ?></strong></div>
            <div><span>Leads</span><strong><?= $totalLeads ?></strong></div>
            <div><span>Cliques</span><strong><?= $totalClicks ?></strong></div>
            <div><span>Taxa de inscrição</span><strong><?= percent($totalLeads, $totalVisits) ?></strong></div>
            <div><span>CTR</span><strong><?= percent($totalClicks, $totalVisits) ?></strong></div>
            <div><span>Clique por lead</span><strong><?= percent($totalClicks, $totalLeads) ?></strong></div>
            <div><span>Inscrições por clique</span><strong><?= percent($totalLeads, $totalClicks) ?></strong></div>
            <div><span>Melhor headline</span><strong><?= e($bestHeadline ?: '-') ?></strong></div>
            <div><span>Melhor oferta CTR</span><strong><?= e($bestOfferCtr ?: '-') ?></strong></div>
            <div><span>Melhor oferta inscrição</span><strong><?= e($bestOfferLead ?: '-') ?></strong></div>
        </section>

        <section class="chart-grid">
            <div class="panel"><h2>Visitas por dia</h2><canvas id="visitsChart"></canvas></div>
            <div class="panel"><h2>Inscritos por dia</h2><canvas id="leadsChart"></canvas></div>
            <div class="panel"><h2>Cliques por dia</h2><canvas id="clicksChart"></canvas></div>
            <div class="panel"><h2>Visitas x Cliques</h2><canvas id="visitsClicksChart"></canvas></div>
            <div class="panel"><h2>Visitas x Inscrições</h2><canvas id="visitsLeadsChart"></canvas></div>
            <div class="panel"><h2>Performance por headline</h2><canvas id="headlineChart"></canvas></div>
            <div class="panel"><h2>Performance por oferta</h2><canvas id="offerChart"></canvas></div>
        </section>

        <section id="config" class="panel">
            <h2>Configurações</h2>
            <form method="post" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_settings">
                <label>Link ou código embed/script do vTurb
                    <textarea name="vturb_embed" rows="6" placeholder="Cole aqui o embed do vTurb ou uma URL"><?= e($vturbEmbed) ?></textarea>
                </label>
                <h3>SuperFuncionário</h3>
                <label>Token da API
                    <input type="password" name="superfuncionario_token" value="<?= e($superfuncionarioToken) ?>" placeholder="Cole o X-ACCESS-TOKEN do SuperFuncionário" autocomplete="off">
                </label>
                <label>Base URL da API
                    <input type="url" name="superfuncionario_base_url" value="<?= e($superfuncionarioBaseUrl) ?>" placeholder="https://app.superfuncionario.com.br/api">
                </label>
                <label>Timeout total em segundos
                    <input type="number" name="superfuncionario_timeout" value="<?= e($superfuncionarioTimeout) ?>" min="1" max="60">
                </label>
                <label>Timeout de conexão em segundos
                    <input type="number" name="superfuncionario_connect_timeout" value="<?= e($superfuncionarioConnectTimeout) ?>" min="1" max="30">
                </label>
                <button type="submit">Salvar configurações</button>
            </form>
        </section>

        <section id="headlines" class="panel">
            <h2>Headlines</h2>
            <form method="post" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_headline">
                <input type="text" name="title" placeholder="Headline" required>
                <input type="text" name="description" placeholder="Subheadline">
                <input type="number" name="weight" value="1" min="1">
                <label class="check"><input type="checkbox" name="is_active" checked> Ativa</label>
                <button type="submit">Cadastrar</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Headline</th><th>Status</th><th>Peso</th><th>Visitas</th><th>Leads</th><th>Cliques</th><th>Inscrição</th><th>CTR</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($headlinePerf as $row): ?>
                        <tr>
                            <td><?= e($row['title']) ?></td>
                            <td><?= $row['is_active'] ? 'Ativa' : 'Inativa' ?></td>
                            <td><?= e((string)$row['weight']) ?></td>
                            <td><?= e((string)$row['visits']) ?></td>
                            <td><?= e((string)$row['leads']) ?></td>
                            <td><?= e((string)$row['clicks']) ?></td>
                            <td><?= percent((int)$row['leads'], (int)$row['visits']) ?></td>
                            <td><?= percent((int)$row['clicks'], (int)$row['visits']) ?></td>
                            <td>
                                <form method="post" class="row-actions">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_headline">
                                    <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                    <button type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="edit-row">
                            <td colspan="9">
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="save_headline">
                                    <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                    <input type="text" name="title" value="<?= e($row['title']) ?>" required>
                                    <input type="text" name="description" value="<?= e($row['description'] ?? '') ?>">
                                    <input type="number" name="weight" value="<?= e((string)$row['weight']) ?>" min="1">
                                    <label class="check"><input type="checkbox" name="is_active" <?= $row['is_active'] ? 'checked' : '' ?>> Ativa</label>
                                    <button type="submit">Salvar edição</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="ofertas" class="panel">
            <h2>Ofertas</h2>
            <form method="post" class="inline-form offers-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_offer">
                <input type="text" name="name" placeholder="Nome" required>
                <input type="url" name="offer_link" placeholder="Link checkout" required>
                <input type="text" name="cash_price" placeholder="Preço à vista">
                <input type="number" name="installments_qty" value="12" min="1">
                <input type="text" name="installment_price" placeholder="Parcela">
                <input type="text" name="description" placeholder="Descrição">
                <input type="number" name="weight" value="1" min="1">
                <label class="check"><input type="checkbox" name="is_active" checked> Ativa</label>
                <button type="submit">Cadastrar</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Oferta</th><th>Status</th><th>Peso</th><th>Link</th><th>Preço</th><th>Parcelas</th><th>Visitas</th><th>Leads</th><th>Cliques</th><th>Inscrição</th><th>CTR</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($offerPerf as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= $row['is_active'] ? 'Ativa' : 'Inativa' ?></td>
                            <td><?= e((string)$row['weight']) ?></td>
                            <td><a href="<?= e($row['offer_link']) ?>" target="_blank">abrir</a></td>
                            <td><?= e(money_br($row['cash_price'])) ?></td>
                            <td><?= e((string)$row['installments_qty']) ?>x <?= e(money_br($row['installment_price'])) ?></td>
                            <td><?= e((string)$row['visits']) ?></td>
                            <td><?= e((string)$row['leads']) ?></td>
                            <td><?= e((string)$row['clicks']) ?></td>
                            <td><?= percent((int)$row['leads'], (int)$row['visits']) ?></td>
                            <td><?= percent((int)$row['clicks'], (int)$row['visits']) ?></td>
                            <td>
                                <form method="post" class="row-actions">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_offer">
                                    <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                    <button type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="edit-row">
                            <td colspan="12">
                                <form method="post" class="inline-form offers-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="save_offer">
                                    <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                    <input type="text" name="name" value="<?= e($row['name']) ?>" required>
                                    <input type="url" name="offer_link" value="<?= e($row['offer_link']) ?>" required>
                                    <input type="text" name="cash_price" value="<?= e((string)$row['cash_price']) ?>">
                                    <input type="number" name="installments_qty" value="<?= e((string)$row['installments_qty']) ?>" min="1">
                                    <input type="text" name="installment_price" value="<?= e((string)$row['installment_price']) ?>">
                                    <input type="text" name="description" value="<?= e($row['description'] ?? '') ?>">
                                    <input type="number" name="weight" value="<?= e((string)$row['weight']) ?>" min="1">
                                    <label class="check"><input type="checkbox" name="is_active" <?= $row['is_active'] ? 'checked' : '' ?>> Ativa</label>
                                    <button type="submit">Salvar edição</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="leads" class="panel">
            <h2>Leads</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Data</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Headline</th><th>Oferta</th><th>Preço</th><th>Parcelas</th><th>Link</th><th>UTM source</th><th>UTM campaign</th><th>Clicou?</th><th>Data clique</th></tr></thead>
                    <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?= e($lead['created_at']) ?></td>
                            <td><?= e($lead['name']) ?></td>
                            <td><?= e($lead['email']) ?></td>
                            <td><?= e($lead['phone']) ?></td>
                            <td><?= e($lead['headline_title']) ?></td>
                            <td><?= e($lead['offer_name']) ?></td>
                            <td><?= e(money_br($lead['cash_price'])) ?></td>
                            <td><?= e((string)$lead['installments_qty']) ?>x <?= e(money_br($lead['installment_price'])) ?></td>
                            <td><a href="<?= e($lead['offer_link']) ?>" target="_blank">abrir</a></td>
                            <td><?= e($lead['utm_source']) ?></td>
                            <td><?= e($lead['utm_campaign']) ?></td>
                            <td><?= $lead['click_date'] ? 'Sim' : 'Não' ?></td>
                            <td><?= e($lead['click_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Logs SuperFuncionário</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Data</th><th>Lead</th><th>Status</th><th>Sucesso</th><th>Resposta</th></tr></thead>
                    <tbody>
                    <?php foreach ($webhookLogs as $log): ?>
                        <tr>
                            <td><?= e($log['created_at']) ?></td>
                            <td><?= e((string)$log['lead_id']) ?></td>
                            <td><?= e((string)$log['http_status']) ?></td>
                            <td><?= $log['success'] ? 'Sim' : 'Não' ?></td>
                            <td><?= e(mb_substr((string)$log['response_body'], 0, 160)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        window.ADMIN_CHARTS = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
