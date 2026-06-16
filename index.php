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

$cashPrice = money_br($offer['cash_price'] ?? 197);
$installmentsQty = (int)($offer['installments_qty'] ?? 12);
$installmentPrice = money_br($offer['installment_price'] ?? 19.70);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Montagem de Quadros Elétricos</title>
    <meta name="description" content="Aprenda montagem de quadros elétricos do zero com método visual e simplificado.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=461ccf8">
    <style>
        <?php readfile(__DIR__ . '/assets/css/style.css'); ?>
    </style>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body>
    <div class="page-grid" aria-hidden="true"></div>

    <div class="top-alert">
        <i data-lucide="flame"></i>
        <span>OFERTA RELÂMPAGO ATIVA - preço promocional pode subir a qualquer momento</span>
    </div>

    <header class="site-header">
        <a class="brand" href="#topo" aria-label="Montagem de Quadros Elétricos">
            <span><i data-lucide="zap"></i></span>
            Montagem de <strong>Quadros Elétricos</strong>
        </a>
        <div class="secure-note"><i data-lucide="shield-check"></i> Compra 100% segura</div>
    </header>

    <main id="topo">
        <section class="hero-section section-pad">
            <div class="container narrow center">
                <div class="pills">
                    <span><i data-lucide="users"></i> +150 mil alunos</span>
                    <span><i data-lucide="sparkles"></i> Método simplificado</span>
                    <span><i data-lucide="zap"></i> Acesso imediato</span>
                </div>

                <h1>Aprenda Montagem de <mark>Quadros Elétricos</mark> e Comece a Fazer Serviços Profissionais <mark>Mesmo Sem Experiência</mark></h1>
                <p class="hero-sub">Descubra o método visual e simplificado que já ajudou milhares de brasileiros a entrar na área elétrica com mais segurança e confiança.</p>

                <div class="video-frame">
                    <div class="video-glow"></div>
                    <div class="video-shell">
                        <span class="live-badge"><b></b> AO VIVO</span>
                        <?= render_video_embed($videoEmbed) ?>
                    </div>
                </div>

                <p class="watch-note"><i data-lucide="play-circle"></i> Assista ao vídeo até o final para liberar a oferta completa</p>

                <button class="cta js-buy" data-button-id="hero_cta"><i data-lucide="zap"></i> Quero entrar pra área elétrica agora</button>
                <p class="microcopy">Pagamento único · Acesso imediato · Garantia de 7 dias</p>
            </div>
        </section>

        <section class="section-pad band">
            <div class="container">
                <div class="section-title center">
                    <span class="tag"><i data-lucide="zap"></i> Habilidade que vira renda</span>
                    <h2>Uma habilidade que pode virar <mark>renda rapidamente</mark></h2>
                    <p>A elétrica é uma das profissões mais valorizadas do Brasil. Quem sabe montar quadros tem demanda o ano inteiro, com liberdade para cobrar pelo serviço.</p>
                </div>
                <div class="feature-grid four">
                    <article><i data-lucide="home"></i><h3>Instalações residenciais</h3><p>Atue em casas, apartamentos e reformas com confiança.</p></article>
                    <article><i data-lucide="hammer"></i><h3>Montagem de quadros</h3><p>Serviço de alto valor agregado e muito procurado.</p></article>
                    <article><i data-lucide="wrench"></i><h3>Manutenção elétrica</h3><p>Conquiste clientes fixos e receita recorrente.</p></article>
                    <article><i data-lucide="briefcase"></i><h3>Serviços autônomos</h3><p>Trabalhe por conta própria com mais liberdade.</p></article>
                </div>
                <div class="mini-benefits">
                    <span><i data-lucide="trending-up"></i> Renda extra</span>
                    <span><i data-lucide="briefcase"></i> Profissão valorizada</span>
                    <span><i data-lucide="sparkles"></i> Independência financeira</span>
                </div>
            </div>
        </section>

        <section class="section-pad">
            <div class="container compact-panel center">
                <span class="tag"><i data-lucide="zap"></i> Talvez você se identifique</span>
                <h2>Talvez hoje você ainda tenha <mark>medo da elétrica...</mark></h2>
                <p>Medo de errar uma conexão e queimar tudo.</p>
                <p>Insegurança de pegar um serviço e não dar conta.</p>
                <p>A sensação de ver outros profissionais ganhando bem enquanto você fica parado.</p>
                <p>A vontade enorme de aprender algo valorizado, mas sem saber por onde começar.</p>
                <hr>
                <h3>Agora imagine montar quadros com <mark>confiança total</mark> e ainda poder <mark>cobrar pelos seus serviços.</mark></h3>
                <button class="cta js-buy" data-button-id="reality_cta"><i data-lucide="zap"></i> Quero mudar minha realidade</button>
            </div>
        </section>

        <section class="section-pad band">
            <div class="container">
                <div class="section-title">
                    <h2>Você sente <mark>insegurança</mark> quando o assunto é montagem de quadros elétricos?</h2>
                    <p>Muita gente trava na elétrica por medo de errar conexões, dimensionar errado ou não seguir a norma. Essa insegurança faz oportunidades passarem.</p>
                </div>
                <div class="feature-grid three">
                    <article><i data-lucide="alert-triangle"></i><h3>Medo de errar conexões</h3><p>Receio de causar curto-circuito ou danos.</p></article>
                    <article><i data-lucide="wrench"></i><h3>Dimensionamento incorreto</h3><p>Não saber escolher cabos e disjuntores.</p></article>
                    <article><i data-lucide="file-text"></i><h3>Normas desconhecidas</h3><p>Insegurança com a norma NBR 5410 e exigências técnicas.</p></article>
                    <article><i data-lucide="zap"></i><h3>Falhas na instalação</h3><p>Risco de retrabalho e prejuízo financeiro.</p></article>
                    <article><i data-lucide="book-open"></i><h3>Diagrama unifilar</h3><p>Dificuldade para interpretar projetos elétricos.</p></article>
                    <article><i data-lucide="users"></i><h3>Perda de oportunidades</h3><p>Trabalhos que você poderia executar passam.</p></article>
                </div>
            </div>
        </section>

        <section class="section-pad split">
            <div class="container split-grid">
                <div>
                    <span class="tag"><i data-lucide="zap"></i> A transformação</span>
                    <h2>Imagine ter <mark>segurança total</mark> para montar um quadro elétrico corretamente...</h2>
                    <p>Imagine entender com clareza:</p>
                    <ul class="check-list">
                        <li>Os componentes do QDC</li>
                        <li>O diagrama unifilar</li>
                        <li>O dimensionamento básico</li>
                        <li>As normas aplicadas</li>
                        <li>A montagem prática passo a passo</li>
                    </ul>
                    <p class="italic">E finalmente sentir confiança para executar serviços elétricos e cobrar por eles.</p>
                </div>
                <figure class="image-card">
                    <img src="https://images.unsplash.com/photo-1621905252507-b35492cc74b4?auto=format&fit=crop&w=1100&q=85" alt="Quadro elétrico com disjuntores e cabos organizados">
                </figure>
            </div>
        </section>

        <section class="section-pad band">
            <div class="container">
                <div class="section-title center">
                    <span class="tag"><i data-lucide="zap"></i> Conteúdo programático</span>
                    <h2>O Que Você Vai <mark>Dominar</mark></h2>
                </div>
                <div class="module-grid">
                    <?php
                    $modules = [
                        'Identifique cada componente do quadro como um profissional',
                        'Aprenda a interpretar esquemas elétricos sem complicação',
                        'Dimensione cabos e disjuntores com segurança',
                        'Aplique as normas corretamente, sem dor de cabeça',
                        'Monte quadros elétricos do zero, passo a passo',
                        'Conquiste a confiança que falta para pegar seus próprios serviços',
                    ];
                    foreach ($modules as $index => $module): ?>
                        <article>
                            <span><?= e(str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                            <i data-lucide="check-circle-2"></i>
                            <h3><?= e($module) ?></h3>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section-pad">
            <div class="container certificate-panel">
                <div>
                    <span class="tag"><i data-lucide="award"></i> Certificado de conclusão</span>
                    <h2>Receba seu <mark>Certificado</mark> de Conclusão</h2>
                    <p>Ao concluir o curso, você recebe um certificado que comprova sua participação, a carga horária estudada e o conteúdo desenvolvido.</p>
                    <div class="legal-box"><i data-lucide="badge-check"></i> Certificado emitido como curso livre, com base na Lei nº 9.394/96.</div>
                </div>
                <figure class="certificate-img">
                    <img src="https://images.unsplash.com/photo-1606326608606-aa0b62935f2b?auto=format&fit=crop&w=900&q=85" alt="Certificado de conclusão sobre mesa">
                </figure>
            </div>
        </section>

        <section class="section-pad band">
            <div class="container instructor">
                <span class="tag"><i data-lucide="zap"></i> Quem será seu guia</span>
                <h2>Conheça <mark>Emerson Leite</mark></h2>
                <div class="pills left">
                    <span>+16 anos de experiência</span>
                    <span>+150 mil alunos formados</span>
                    <span>Engenheiro eletricista</span>
                </div>
                <p>Mais de <strong>16 anos atuando na prática</strong> no setor elétrico residencial e industrial. Já formei milhares de alunos que hoje executam seus próprios serviços com segurança.</p>
                <p>Minha missão é transformar quem nunca encostou em um quadro elétrico em alguém capaz de montar com confiança e cobrar pelo serviço.</p>
                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=600&q=85" alt="Instrutor Emerson Leite">
            </div>
        </section>

        <section class="section-pad">
            <div class="container">
                <div class="section-title center">
                    <span class="tag"><i data-lucide="message-circle"></i> Mensagens reais de alunos</span>
                    <h2>Mais de <mark>150 mil alunos</mark> já deram o primeiro passo</h2>
                    <p>Pessoas comuns, sem experiência prévia, que hoje montam quadros com confiança.</p>
                </div>
                <div class="testimonial-grid">
                    <article><b>A</b><h3>Antônio R.</h3><small>São Paulo - SP</small><p>Finalmente entendi diagrama unifilar. Montei o quadro da minha casa esse fim de semana.</p><span>★★★★★</span></article>
                    <article><b>J</b><h3>José Carlos M.</h3><small>Belo Horizonte - MG</small><p>Já fiz 3 serviços esse mês usando o que aprendi aqui. Estou cobrando por quadro.</p><span>★★★★★</span></article>
                    <article><b>R</b><h3>Roberto A.</h3><small>Curitiba - PR</small><p>Eu tinha medo de elétrica. Hoje monto quadro sem tremer na base.</p><span>★★★★★</span></article>
                    <article><b>P</b><h3>Paulo Sérgio L.</h3><small>Salvador - BA</small><p>A aula de dimensionamento abriu minha cabeça. Paguei o curso com um serviço.</p><span>★★★★★</span></article>
                    <article><b>M</b><h3>Marcos V.</h3><small>Fortaleza - CE</small><p>Nunca pensei que aprenderia tão rápido. Já estou com clientes fixos.</p><span>★★★★★</span></article>
                    <article><b>E</b><h3>Edson F.</h3><small>Porto Alegre - RS</small><p>Larguei meu antigo emprego e agora trabalho por conta.</p><span>★★★★★</span></article>
                </div>
            </div>
        </section>

        <section class="section-pad band">
            <div class="container">
                <div class="section-title center">
                    <span class="tag"><i data-lucide="gift"></i> Bônus exclusivos</span>
                    <h2>Bônus <mark>GRÁTIS</mark> que você recebe HOJE</h2>
                    <p>Mais de <strong>R$ 1.182</strong> em bônus inclusos sem custo adicional para os primeiros alunos.</p>
                </div>
                <div class="bonus-grid">
                    <article><em>Grátis hoje</em><h3>Aula Ao Vivo - Dimensionamento</h3><p>Aprenda ao vivo como dimensionar corretamente cabos e disjuntores.</p><span>De R$ 297 · Incluso</span></article>
                    <article><em>Grátis hoje</em><h3>Modelo de Orçamento Profissional</h3><p>Apresente serviços de forma profissional e feche mais clientes.</p><span>De R$ 147 · Incluso</span></article>
                    <article><em>Grátis hoje</em><h3>Lista Completa de Materiais para QDC</h3><p>Saiba exatamente quais materiais utilizar em cada montagem.</p><span>De R$ 97 · Incluso</span></article>
                    <article><em>Grátis hoje</em><h3>E-book - Montagem de Quadros Elétricos</h3><p>Material complementar para reforçar seu aprendizado.</p><span>De R$ 197 · Incluso</span></article>
                    <article><em>Grátis hoje</em><h3>Grupo Exclusivo de Alunos</h3><p>Troque experiências, tire dúvidas e evolua junto com outros alunos.</p><span>De R$ 247 · Incluso</span></article>
                    <article><em>Grátis hoje</em><h3>Suporte Especializado</h3><p>Receba suporte direto para esclarecer dúvidas técnicas.</p><span>De R$ 197 · Incluso</span></article>
                </div>
            </div>
        </section>

        <section class="section-pad">
            <div class="container urgency-panel center">
                <span class="tag"><i data-lucide="clock"></i> Atenção: oferta encerra em</span>
                <div id="countdown" class="countdown" aria-live="polite"></div>
                <p>Após o tempo expirar, o valor promocional pode subir a qualquer momento e os bônus podem ser removidos. <strong>Garanta agora seu acesso.</strong></p>
                <div class="spots">
                    <div><span>Vagas com desconto</span><strong>Restam 23 de 200</strong></div>
                    <progress value="88" max="100"></progress>
                    <small>88% das vagas promocionais já foram preenchidas.</small>
                </div>
                <button class="cta js-buy" data-button-id="urgency_cta"><i data-lucide="zap"></i> Garantir minha vaga agora</button>
            </div>
        </section>

        <section class="section-pad">
            <div class="container guarantee-panel center">
                <div class="shield"><i data-lucide="shield-check"></i></div>
                <span class="tag"><i data-lucide="zap"></i> Sua compra está protegida</span>
                <h2>Garantia Incondicional de <mark>7 dias</mark></h2>
                <p>Você pode assistir às aulas e conhecer o método sem risco. Se não gostar do curso, devolvemos 100% do seu dinheiro. <strong>O risco é todo nosso.</strong></p>
            </div>
        </section>

        <section class="section-pad final-offer">
            <div class="container">
                <div class="section-title center">
                    <span class="tag"><i data-lucide="zap"></i> Oferta final</span>
                    <h2>Comece <mark>Hoje</mark> a Sua Jornada na Elétrica</h2>
                    <p><strong>Comece hoje por menos que uma pizza.</strong></p>
                </div>
                <div class="offer-grid">
                    <div class="receive-card">
                        <h3><i data-lucide="sparkles"></i> Tudo que você recebe ao entrar hoje</h3>
                        <ul class="check-list">
                            <li>Curso completo do zero à montagem prática de quadros</li>
                            <li>Certificado de conclusão</li>
                            <li>Aula ao vivo e material complementar</li>
                            <li>Grupo de alunos e suporte especializado</li>
                            <li>Garantia de 7 dias</li>
                        </ul>
                    </div>
                    <aside class="price-card">
                        <span class="ribbon">Oferta por tempo limitado</span>
                        <p class="old-price">De R$ 1.679,00</p>
                        <p class="today">Hoje, por apenas</p>
                        <div class="installments"><small><?= e((string)$installmentsQty) ?>x</small><strong><?= e($installmentPrice) ?></strong></div>
                        <p>ou <strong><?= e($cashPrice) ?></strong> à vista</p>
                        <div class="safe-box"><i data-lucide="shield-check"></i> Garantia de 7 dias. Não gostou? Devolvemos 100% do seu dinheiro.</div>
                        <button class="cta js-buy" data-button-id="final_cta"><i data-lucide="zap"></i> Liberar meu acesso imediato</button>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">© <?= e(date('Y')) ?> Montagem de Quadros Elétricos - Todos os direitos reservados.</div>
    </footer>

    <div class="mobile-cta">
        <button class="cta js-buy" data-button-id="mobile_sticky"><i data-lucide="zap"></i> Garantir acesso agora</button>
    </div>

    <div id="leadModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal">
            <button class="modal-close" type="button" aria-label="Fechar modal">×</button>
            <div class="modal-icon"><i data-lucide="zap"></i></div>
            <h2 id="modalTitle">Falta pouco para seu acesso</h2>
            <p>Preencha seus dados para liberar o checkout seguro.</p>
            <form id="leadForm">
                <input type="hidden" name="visitor_uuid" value="<?= e($visitor['visitor_uuid']) ?>">
                <input type="hidden" name="headline_id" value="<?= e((string)($headline['id'] ?? '')) ?>">
                <input type="hidden" name="offer_id" value="<?= e((string)($offer['id'] ?? '')) ?>">
                <label>Nome completo<input type="text" name="name" autocomplete="name" required></label>
                <label>Email<input type="email" name="email" autocomplete="email" required></label>
                <label>WhatsApp<input type="tel" name="phone" autocomplete="tel" required></label>
                <button type="submit" class="cta"><i data-lucide="zap"></i> Liberar meu acesso imediato</button>
                <p id="leadMessage" class="form-message"></p>
            </form>
        </div>
    </div>

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
