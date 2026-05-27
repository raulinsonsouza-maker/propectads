<?php

declare(strict_types=1);

require __DIR__ . '/helpers.php';

header('X-Robots-Tag: noindex, nofollow');

$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$slug = strtolower(trim($slug));

if (!analise_slug_valid($slug)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Não encontrado</title></head><body><p>Análise não encontrada.</p></body></html>';
    exit;
}

$data = analise_load_client($slug);
if ($data === null) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Não encontrado</title></head><body><p>Análise não encontrada.</p></body></html>';
    exit;
}

if (analise_is_expired($data)) {
    analise_render_expired((string) ($data['empresa'] ?? ''));
    exit;
}

$empresa = (string) ($data['empresa'] ?? 'Cliente');
$site = (string) ($data['site'] ?? '');
$analista = (string) ($data['analista'] ?? 'ProspectAds');
$dataAnalise = (string) ($data['dataAnalise'] ?? '');
$growthScore = (int) ($data['growthScore'] ?? 0);
$potencialCrescimento = trim((string) ($data['potencialCrescimento'] ?? ''));
$resumo = (string) ($data['resumoExecutivo'] ?? '');
$gapOportunidade = trim((string) ($data['gapOportunidade'] ?? ''));
$scores = $data['scores'] ?? [];
$evidencias = $data['evidencias'] ?? [];
$oportunidades = $data['oportunidades'] ?? [];
$vazamento = $data['vazamento'] ?? [];
$operacao = $data['operacaoHoje'] ?? [];
$cenario = $data['cenario'] ?? [];
$benchmark = $data['benchmark'] ?? [];
$roadmap = $data['roadmap'] ?? [];
$perguntas = $data['perguntasCall'] ?? [];
$cta = $data['cta'] ?? [];
$compacto = !empty($data['compacto']);
$daysLeft = analise_days_remaining($data);

$logoPath = (string) ($data['logo'] ?? '');
$hasLogo = $logoPath !== '' && analise_asset_exists($slug, $logoPath);

$waNumber = (string) ($cta['whatsapp'] ?? '5519982459427');
$waMsg = (string) ($cta['mensagem'] ?? 'Olá! Gostaria de conversar sobre a análise de crescimento com a ProspectAds.');
$waUrl = 'https://wa.me/' . preg_replace('/\D/', '', $waNumber) . '?text=' . rawurlencode($waMsg);

$visitantes = (int) ($cenario['visitantesMes'] ?? 0);
$convAtual = (float) ($cenario['conversaoAtual'] ?? 0);
$convAlvo = (float) ($cenario['conversaoAlvo'] ?? 0);
$pedidosAtual = $visitantes > 0 ? (int) round($visitantes * ($convAtual / 100)) : 0;
$pedidosAlvo = $visitantes > 0 ? (int) round($visitantes * ($convAlvo / 100)) : 0;
$pedidosIncremental = max(0, $pedidosAlvo - $pedidosAtual);
$growthPct = $pedidosAtual > 0 ? (int) round((($pedidosAlvo - $pedidosAtual) / $pedidosAtual) * 100) : 0;

$ticketMin = (int) ($cenario['ticketMedioMin'] ?? 0);
$ticketMax = (int) ($cenario['ticketMedioMax'] ?? 0);
$hasFinanceiro = $ticketMin > 0 && $ticketMax > 0 && $pedidosAtual > 0;
$receitaAtualMin = $pedidosAtual * $ticketMin;
$receitaAtualMax = $pedidosAtual * $ticketMax;
$receitaAlvoMin = $pedidosAlvo * $ticketMin;
$receitaAlvoMax = $pedidosAlvo * $ticketMax;
$receitaIncMin = $pedidosIncremental * $ticketMin;
$receitaIncMax = $pedidosIncremental * $ticketMax;
$simulacaoLabel = trim((string) ($cenario['simulacaoLabel'] ?? ''));
$disclaimerSimulacao = trim((string) ($cenario['disclaimerSimulacao'] ?? 'Cenário projetado para mostrar o potencial. Ajustamos visitas e conversão com os dados reais da loja.'));
$disclaimerFinanceiro = trim((string) ($cenario['disclaimerFinanceiro'] ?? 'Valores projetados com base no cenário acima. Ajustamos ao ticket e volume reais da operação de vocês.'));

$heroScoresMini = analise_scores_hero_mini($scores, 3);
$hasMaturidade = $scores !== [];

$heroHighlights = [];
foreach (array_slice($oportunidades, 0, 3) as $op) {
    $titulo = trim((string) ($op['titulo'] ?? ''));
    if ($titulo !== '') {
        $heroHighlights[] = $titulo;
    }
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Análise de Crescimento | <?= analise_h($empresa) ?> | ProspectAds</title>
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/design-system.css">
    <link rel="stylesheet" href="/analises/_template/analise.css?v=15">
</head>
<body class="analise-page<?= $compacto ? ' analise-page--compacto' : '' ?>">
    <header class="analise-header">
        <div class="analise-container analise-header__inner">
            <a href="https://prospectads.com.br/" class="analise-header__brand">ProspectAds</a>
            <nav class="analise-header__nav" aria-label="Seções da análise">
                <?php if ($hasMaturidade): ?><a href="#maturidade">Mapa</a><?php endif; ?>
                <?php if ($evidencias !== []): ?><a href="#evidencias">Análise</a><?php endif; ?>
                <?php if ($oportunidades !== []): ?><a href="#oportunidades">Oportunidades</a><?php endif; ?>
                <?php if ($cenario !== []): ?><a href="#projecao">Projeção</a><?php endif; ?>
                <?php if ($roadmap !== []): ?><a href="#roadmap">Plano</a><?php endif; ?>
                <a href="#proximos-passos">Próximos passos</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="analise-hero" id="inicio">
            <div class="analise-container">
                <div class="analise-hero__panel">
                    <div class="analise-hero__top">
                        <p class="analise-hero__prepared">Oportunidades de crescimento digital</p>
                        <div class="analise-hero__top-meta">
                            <span class="analise-hero__pill">Análise exclusiva</span>
                            <?php if ($daysLeft !== null): ?>
                                <span class="analise-hero__pill analise-hero__pill--muted">Válida por <?= (int) $daysLeft ?> dias</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="analise-hero__identity">
                        <?php if ($hasLogo): ?>
                            <div class="analise-hero__brand-logo">
                                <img src="<?= analise_h(analise_asset_url($slug, $logoPath)) ?>" alt="Logo <?= analise_h($empresa) ?>">
                            </div>
                        <?php endif; ?>
                        <div class="analise-hero__brand-text">
                            <p class="analise-hero__brand-for">Preparado para</p>
                            <h1><?= analise_h($empresa) ?></h1>
                            <?php if ($site !== ''): ?>
                                <p class="analise-hero__site">
                                    <a href="<?= analise_h($site) ?>" target="_blank" rel="noopener noreferrer"><?= analise_h(parse_url($site, PHP_URL_HOST) ?: $site) ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="analise-hero__main">
                        <div class="analise-hero__content">
                            <p class="analise-hero__resumo"><?= analise_h($resumo) ?></p>

                            <?php if ($gapOportunidade !== ''): ?>
                                <blockquote class="analise-hero__gap">
                                    <p><?= analise_h($gapOportunidade) ?></p>
                                </blockquote>
                            <?php endif; ?>

                            <?php if ($heroHighlights !== []): ?>
                                <div class="analise-hero__highlights">
                                    <p class="analise-hero__highlights-label">Por onde começamos</p>
                                    <ul class="analise-hero__highlights-list">
                                        <?php foreach ($heroHighlights as $highlight): ?>
                                            <li><?= analise_h($highlight) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <aside class="analise-hero__aside" aria-label="Potencial de crescimento">
                            <?php if ($hasMaturidade): ?>
                            <div class="analise-hero__maturidade-card">
                                <p class="analise-hero__maturidade-label">Potencial de crescimento</p>
                                <?php if ($potencialCrescimento !== ''): ?>
                                    <p class="analise-hero__potencial-badge"><?= analise_h($potencialCrescimento) ?></p>
                                <?php endif; ?>
                                <p class="analise-hero__maturidade-hint">Catálogo no Instagram e Facebook, remarketing e medição de resultados ainda têm muito espaço para crescer.</p>

                                <?php if ($heroScoresMini !== []): ?>
                                <div class="analise-hero__maturidade-mini">
                                    <?php foreach ($heroScoresMini as $mini): ?>
                                        <?php
                                        $miniPct = analise_score_maturidade($mini);
                                        $miniOportunidade = analise_score_oportunidade($miniPct);
                                        ?>
                                        <div class="analise-hero__mini-row">
                                            <div class="analise-hero__mini-head">
                                                <span class="analise-hero__mini-name"><?= analise_h((string) ($mini['pilar'] ?? '')) ?></span>
                                                <span class="analise-score-impacto analise-score-impacto--<?= analise_h($miniOportunidade['class']) ?>"><?= analise_h($miniOportunidade['label']) ?></span>
                                            </div>
                                            <div class="analise-hero__mini-bar" role="presentation" aria-hidden="true">
                                                <span style="width: <?= 100 - $miniPct ?>%"></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <a class="analise-hero__score-link" href="#maturidade">Ver mapa por frente ↓</a>
                            </div>
                            <?php elseif ($growthScore > 0): ?>
                            <div class="analise-hero__score-card">
                                <p class="analise-hero__score-label">Potencial de crescimento</p>
                                <div class="analise-score-ring analise-score-ring--hero" data-score="<?= $growthScore ?>">
                                    <svg viewBox="0 0 120 120" aria-hidden="true">
                                        <circle class="analise-score-ring__bg" cx="60" cy="60" r="52"></circle>
                                        <circle class="analise-score-ring__fg" cx="60" cy="60" r="52" data-circ="326.73"></circle>
                                    </svg>
                                    <div class="analise-score-ring__value">
                                        <span class="analise-score-ring__num" data-target="<?= $growthScore ?>">0</span>
                                        <span class="analise-score-ring__of">/100</span>
                                    </div>
                                </div>
                                <p class="analise-hero__score-hint">Quanto maior, mais estruturado está o crescimento digital de vocês.</p>
                                <a class="analise-hero__score-link" href="#oportunidades">Ver oportunidades ↓</a>
                            </div>
                            <?php endif; ?>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($operacao !== []): ?>
        <section class="analise-section analise-section--compact" id="operacao">
            <div class="analise-container">
                <h2 class="analise-section__title">Como vocês parecem vender hoje</h2>
                <div class="analise-operacao">
                    <?php foreach ($operacao as $item): ?>
                        <div class="analise-operacao__item">
                            <span class="analise-operacao__canal"><?= analise_h((string) ($item['canal'] ?? '')) ?></span>
                            <span class="analise-operacao__status is-<?= analise_h((string) ($item['status'] ?? 'ativo')) ?>"><?= analise_h((string) ($item['status'] ?? '')) ?></span>
                            <p><?= analise_h((string) ($item['nota'] ?? '')) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($hasMaturidade): ?>
        <section class="analise-section analise-section--alt" id="maturidade">
            <div class="analise-container">
                <h2 class="analise-section__title">Onde há mais oportunidade</h2>
                <p class="analise-section__lead">Cada frente mostra o nível de estruturação hoje e o potencial de ganho. Foco nas áreas com oportunidade alta: é onde o retorno tende a ser mais rápido.</p>
                <div class="analise-scores-legend" aria-hidden="true">
                    <span><strong>Potencial de ganho</strong></span>
                    <span class="analise-score-impacto analise-score-impacto--is-alta">Alta</span>
                    <span class="analise-score-impacto analise-score-impacto--is-media">Média</span>
                    <span class="analise-score-impacto analise-score-impacto--is-baixa">Baixa</span>
                </div>
                <div class="analise-scores">
                    <?php foreach ($scores as $score): ?>
                        <?php
                        $maturidade = analise_score_maturidade($score);
                        $estruturacao = analise_score_estruturacao($maturidade);
                        $oportunidade = analise_score_oportunidade($maturidade);
                        ?>
                        <article class="analise-score-card analise-score-card--impacto-<?= analise_h($oportunidade['class']) ?>" data-animate-bar>
                            <div class="analise-score-card__head">
                                <h3><?= analise_h((string) ($score['pilar'] ?? '')) ?></h3>
                                <span class="analise-score-impacto analise-score-impacto--<?= analise_h($oportunidade['class']) ?>">
                                    <?= analise_h($oportunidade['label']) ?>
                                </span>
                            </div>
                            <p class="analise-score-card__meta">
                                <span class="analise-score-card__meta-label">Estruturação hoje</span>
                                <span class="analise-score-status analise-score-status--<?= analise_h($estruturacao['class']) ?>"><?= analise_h($estruturacao['label']) ?></span>
                            </p>
                            <div class="analise-score-card__bar-wrap">
                                <div class="analise-score-card__bar" role="presentation" aria-label="Potencial de ganho">
                                    <span style="width: <?= 100 - $maturidade ?>%"></span>
                                </div>
                                <p class="analise-score-card__bar-label">Potencial de ganho</p>
                            </div>
                            <?php if (!empty($score['insight'])): ?>
                                <p class="analise-score-card__insight"><?= analise_h((string) $score['insight']) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($vazamento !== []): ?>
        <section class="analise-section analise-section--alt" id="vazamento">
            <div class="analise-container">
                <h2 class="analise-section__title">Onde o crescimento pode estar escapando</h2>
                <div class="analise-table-wrap">
                    <table class="analise-table">
                        <thead>
                            <tr>
                                <th>Área</th>
                                <th>Oportunidade</th>
                                <th>O que isso representa</th>
                                <th>Prioridade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vazamento as $row): ?>
                                <tr>
                                    <td><?= analise_h((string) ($row['area'] ?? '')) ?></td>
                                    <td><?= analise_h((string) ($row['oportunidade'] ?? '')) ?></td>
                                    <td><?= analise_h((string) ($row['impacto'] ?? '')) ?></td>
                                    <td><span class="analise-badge analise-badge--<?= analise_prioridade_class((string) ($row['prioridade'] ?? '')) ?>"><?= analise_h((string) ($row['prioridade'] ?? '')) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="analise-section analise-section--evidencias" id="evidencias">
            <div class="analise-container">
                <div class="analise-section__head">
                    <h2 class="analise-section__title">O que encontramos na operação de vocês</h2>
                    <p class="analise-section__lead">Três achados que mais impactam o crescimento de <?= analise_h($empresa) ?>.</p>
                </div>
                <div class="analise-evidencias">
                    <?php foreach ($evidencias as $ev): ?>
                        <?php
                        $shot = (string) ($ev['screenshot'] ?? '');
                        $hasShot = $shot !== '' && analise_asset_exists($slug, $shot);
                        $shotUrl = $hasShot ? analise_asset_url($slug, $shot) : '';
                        $prioClass = analise_prioridade_class((string) ($ev['prioridade'] ?? ''));
                        ?>
                        <article class="analise-evidencia" id="evidencia-<?= (int) ($ev['id'] ?? 0) ?>">
                            <header class="analise-evidencia__head">
                                <div class="analise-evidencia__head-text">
                                    <p class="analise-evidencia__num">Ponto #<?= str_pad((string) ((int) ($ev['id'] ?? 0)), 2, '0', STR_PAD_LEFT) ?></p>
                                    <h3><?= analise_h((string) ($ev['titulo'] ?? '')) ?></h3>
                                </div>
                                <span class="analise-evidencia__prio analise-evidencia__prio--<?= $prioClass ?>">
                                    <span class="analise-evidencia__prio-dot" aria-hidden="true"></span>
                                    <?= analise_h(analise_prioridade_label((string) ($ev['prioridade'] ?? ''))) ?>
                                </span>
                            </header>

                            <div class="analise-evidencia__split">
                                <div class="analise-evidencia__media">
                                    <?php if ($hasShot): ?>
                                        <div class="analise-evidencia__frame">
                                            <div class="analise-evidencia__frame-bar" aria-hidden="true">
                                                <span></span><span></span><span></span>
                                            </div>
                                            <button type="button" class="analise-evidencia__shot" data-lightbox="<?= analise_h($shotUrl) ?>" data-caption="<?= analise_h((string) ($ev['titulo'] ?? '')) ?>">
                                                <img src="<?= analise_h($shotUrl) ?>" alt="Print: <?= analise_h((string) ($ev['titulo'] ?? '')) ?>" loading="lazy">
                                                <span class="analise-evidencia__zoom">Ampliar</span>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="analise-evidencia__placeholder">
                                            <span>Ponto #<?= str_pad((string) ((int) ($ev['id'] ?? 0)), 2, '0', STR_PAD_LEFT) ?></span>
                                            <small>Adicione <?= analise_h(basename($shot ?: 'screenshot.png')) ?> em assets/</small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="analise-evidencia__body">
                                <?php if (!empty($ev['observacao'])): ?>
                                <div class="analise-evidencia__analise">
                                    <p class="analise-evidencia__block-label">Situação hoje</p>
                                    <p><?= analise_h((string) $ev['observacao']) ?></p>
                                </div>
                                <?php endif; ?>

                                <div class="analise-evidencia__growth">
                                    <p class="analise-evidencia__block-label analise-evidencia__block-label--gold">
                                        <span class="analise-evidencia__growth-icon" aria-hidden="true">↗</span>
                                        Oportunidade
                                    </p>
                                    <p class="analise-evidencia__growth-text"><?= analise_h((string) ($ev['oportunidade'] ?? '')) ?></p>
                                    <div class="analise-evidencia__growth-impact">
                                        <span class="analise-evidencia__impacto-label">Impacto esperado</span>
                                        <p><?= analise_h((string) ($ev['impacto'] ?? '')) ?></p>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="analise-section analise-section--oportunidades" id="oportunidades">
            <div class="analise-container">
                <div class="analise-section__head analise-section__head--center">
                    <h2 class="analise-section__title">Por onde começar</h2>
                    <p class="analise-section__lead">Três frentes, nesta ordem de execução.</p>
                </div>
                <div class="analise-oportunidades analise-oportunidades--compacto">
                    <?php foreach ($oportunidades as $i => $op): ?>
                        <article class="analise-op-card">
                            <span class="analise-op-card__rank"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <?php if (!$compacto): ?>
                            <div class="analise-op-card__icon" aria-hidden="true"><?= analise_area_icon((string) ($op['area'] ?? '')) ?></div>
                            <?php endif; ?>
                            <p class="analise-op-card__area"><?= analise_h((string) ($op['area'] ?? '')) ?></p>
                            <h3><?= analise_h((string) ($op['titulo'] ?? '')) ?></h3>
                            <p class="analise-op-card__desc"><?= analise_h((string) ($op['descricao'] ?? '')) ?></p>
                            <?php if (!$compacto): ?>
                            <div class="analise-op-card__meta">
                                <div class="analise-op-card__impacto">
                                    <span class="analise-op-card__impacto-label">Potencial de impacto nas vendas</span>
                                    <?= analise_stars((int) ($op['impacto'] ?? 0)) ?>
                                </div>
                                <span class="analise-op-card__prazo">Prazo estimado: <?= analise_h((string) ($op['prazo'] ?? '')) ?></span>
                            </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="analise-section analise-section--projecao" id="projecao">
            <div class="analise-container">
                <h2 class="analise-section__title">Quanto dá para crescer?</h2>
                <?php if (!empty($cenario['nota'])): ?>
                    <p class="analise-section__lead"><?= analise_h((string) $cenario['nota']) ?></p>
                <?php endif; ?>

                <div class="analise-projecao">
                    <div class="analise-projecao__premissas">
                        <h3 class="analise-projecao__subtitle">1. Premissas usadas na projeção</h3>
                        <ul class="analise-projecao__premissas-list">
                            <li><strong><?= number_format($visitantes, 0, ',', '.') ?></strong> visitas ao site por mês</li>
                            <li><strong><?= analise_h(analise_format_percent($convAtual)) ?></strong> de conversão hoje</li>
                            <li><strong><?= analise_h(analise_format_percent($convAlvo)) ?></strong> de conversão alvo <span class="analise-projecao__muted">(mesmo tráfego)</span></li>
                            <?php if ($ticketMin > 0 && $ticketMax > 0): ?>
                            <li>Ticket médio de <strong><?= analise_h(analise_format_faixa_ticket($ticketMin, $ticketMax)) ?></strong> por pedido</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="analise-projecao__comparativo">
                        <h3 class="analise-projecao__subtitle">2. O que isso representa</h3>
                        <div class="analise-table-wrap">
                            <table class="analise-table analise-projecao__table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Hoje</th>
                                        <th>Com otimização</th>
                                        <th>Ganho</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Pedidos por mês</td>
                                        <td><?= $pedidosAtual ?></td>
                                        <td><?= $pedidosAlvo ?></td>
                                        <td class="analise-projecao__gain">+<?= $pedidosIncremental ?></td>
                                    </tr>
                                    <?php if ($hasFinanceiro): ?>
                                    <tr>
                                        <td>Faturamento por mês</td>
                                        <td><?= analise_h(analise_format_faixa_receita($receitaAtualMin, $receitaAtualMax)) ?></td>
                                        <td><?= analise_h(analise_format_faixa_receita($receitaAlvoMin, $receitaAlvoMax)) ?></td>
                                        <td class="analise-projecao__gain"><?= analise_h(analise_format_faixa_incremento($receitaIncMin, $receitaIncMax)) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="analise-projecao__destaque">
                        <p class="analise-projecao__destaque-label">Resumo</p>
                        <p class="analise-projecao__destaque-text">
                            Com as <strong>mesmas <?= number_format($visitantes, 0, ',', '.') ?> visitas</strong>, subir a conversão de
                            <strong><?= analise_h(analise_format_percent($convAtual)) ?></strong> para
                            <strong><?= analise_h(analise_format_percent($convAlvo)) ?></strong> significa
                            <strong>+<?= $pedidosIncremental ?> pedidos por mês</strong><?php if ($hasFinanceiro): ?>
                            e até <strong><?= analise_h(analise_format_faixa_incremento($receitaIncMin, $receitaIncMax)) ?></strong> a mais em faturamento<?php endif; ?>.
                        </p>
                    </div>
                </div>

                <p class="analise-disclaimer"><?= analise_h($disclaimerSimulacao) ?></p>
            </div>
        </section>

        <?php if ($benchmark !== []): ?>
        <section class="analise-section analise-section--compact analise-section--alt" id="benchmark">
            <div class="analise-container">
                <h2 class="analise-section__title">O que lojas do segmento costumam fazer</h2>
                <p class="analise-section__lead">Referências de operações que já cresceram de forma consistente no e-commerce de colchões e móveis:</p>
                <ul class="analise-benchmark">
                    <?php foreach ($benchmark as $item): ?>
                        <li><?= analise_h((string) $item) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="analise-benchmark__close">Vocês já têm base sólida. Com esses passos, dá para se aproximar desse modelo e acelerar o crescimento.</p>
            </div>
        </section>
        <?php endif; ?>

        <section class="analise-section" id="roadmap">
            <div class="analise-container">
                <h2 class="analise-section__title">Plano de 90 dias</h2>
                <div class="analise-roadmap">
                    <?php foreach ($roadmap as $phase): ?>
                        <article class="analise-roadmap__phase">
                            <p class="analise-roadmap__mes">Mês <?= (int) ($phase['mes'] ?? 0) ?></p>
                            <h3><?= analise_h((string) ($phase['titulo'] ?? '')) ?></h3>
                            <?php if (!empty($phase['objetivo'])): ?>
                                <p class="analise-roadmap__obj"><?= analise_h((string) $phase['objetivo']) ?></p>
                            <?php endif; ?>
                            <ul>
                                <?php foreach (($phase['itens'] ?? []) as $item): ?>
                                    <li><?= analise_h((string) $item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="analise-roadmap__meta"><strong>Objetivo do mês:</strong> <?= analise_h((string) ($phase['meta'] ?? '')) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($perguntas !== []): ?>
        <section class="analise-section analise-section--alt" id="perguntas">
            <div class="analise-container">
                <h2 class="analise-section__title">Para entendermos melhor a operação de vocês</h2>
                <ol class="analise-perguntas">
                    <?php foreach ($perguntas as $pergunta): ?>
                        <li><?= analise_h((string) $pergunta) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>
        <?php endif; ?>

        <section class="analise-section analise-cta" id="proximos-passos">
            <div class="analise-container analise-cta__inner">
                <h2>Próximo passo juntos</h2>
                <p>A <?= analise_h($empresa) ?> tem caminho claro para crescer. A ProspectAds cuida da aquisição, remarketing, otimização do site e crescimento previsível. Vocês focam no operacional e no atendimento.</p>
                <a class="analise-btn analise-btn--primary analise-btn--large" href="<?= analise_h($waUrl) ?>" target="_blank" rel="noopener noreferrer">Quero avançar com esse plano</a>
                <p class="analise-cta__fine">ProspectAds · Material exclusivo para <?= analise_h($empresa) ?></p>
            </div>
        </section>
    </main>

    <div class="analise-lightbox" hidden aria-hidden="true">
        <button type="button" class="analise-lightbox__close" aria-label="Fechar">&times;</button>
        <figure>
            <img src="" alt="">
            <figcaption></figcaption>
        </figure>
    </div>

    <script src="/analises/_template/analise.js?v=3" defer></script>
</body>
</html>
