<?php

declare(strict_types=1);

function analise_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function analise_slug_valid(string $slug): bool
{
    return $slug !== '' && (bool) preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug);
}

function analise_client_path(string $slug): string
{
    return dirname(__DIR__) . '/' . $slug;
}

function analise_load_client(string $slug): ?array
{
    if (!analise_slug_valid($slug) || $slug === '_template') {
        return null;
    }

    $jsonPath = analise_client_path($slug) . '/cliente.json';
    if (!is_file($jsonPath)) {
        return null;
    }

    $raw = file_get_contents($jsonPath);
    if ($raw === false) {
        return null;
    }

    // Remove BOM (editores Windows costumam adicionar)
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function analise_expires_at(array $data): ?DateTimeImmutable
{
    $date = $data['dataAnalise'] ?? '';
    $days = (int) ($data['expiresInDays'] ?? 20);
    if ($date === '' || $days < 1) {
        return null;
    }

    try {
        $start = new DateTimeImmutable($date);
    } catch (Exception) {
        return null;
    }

    return $start->modify('+' . $days . ' days');
}

function analise_is_expired(array $data): bool
{
    $expires = analise_expires_at($data);
    if ($expires === null) {
        return false;
    }

    return new DateTimeImmutable('today') > $expires;
}

function analise_days_remaining(array $data): ?int
{
    $expires = analise_expires_at($data);
    if ($expires === null) {
        return null;
    }

    $today = new DateTimeImmutable('today');
    if ($today > $expires) {
        return 0;
    }

    return (int) $today->diff($expires)->days;
}

function analise_asset_url(string $slug, string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    return '/analises/' . rawurlencode($slug) . '/' . $relative;
}

function analise_asset_exists(string $slug, string $relative): bool
{
    $path = analise_client_path($slug) . '/' . ltrim(str_replace('\\', '/', $relative), '/');
    return is_file($path);
}

function analise_format_date(string $iso): string
{
    try {
        $d = new DateTimeImmutable($iso);
    } catch (Exception) {
        return $iso;
    }

    $months = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];

    $m = (int) $d->format('n');
    return $d->format('d') . ' de ' . ($months[$m] ?? $d->format('F')) . ' de ' . $d->format('Y');
}

function analise_stars(int $impacto, int $max = 5): string
{
    $impacto = max(0, min($max, $impacto));
    $html = '<span class="analise-stars" aria-label="Impacto ' . $impacto . ' de ' . $max . '">';
    for ($i = 1; $i <= $max; $i++) {
        $html .= '<span class="analise-stars__item' . ($i <= $impacto ? ' is-on' : '') . '">★</span>';
    }
    $html .= '</span>';
    return $html;
}

function analise_lower(string $value): string
{
    $value = trim($value);
    return function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
}

function analise_prioridade_class(string $prioridade): string
{
    $p = analise_lower($prioridade);
    if (str_contains($p, 'alta')) {
        return 'is-alta';
    }
    if (str_contains($p, 'méd') || str_contains($p, 'med')) {
        return 'is-media';
    }
    return 'is-baixa';
}

function analise_area_icon(string $area): string
{
    $a = analise_lower($area);
    $icons = [
        'aquisição' => '🎯', 'aquisicao' => '🎯', 'remarketing' => '🔄',
        'conversão' => '📈', 'conversao' => '📈', 'recuperação' => '💰',
        'recuperacao' => '💰', 'tracking' => '📊', 'catálogo' => '📦',
        'catalogo' => '📦', 'retenção' => '🔁', 'retencao' => '🔁', 'mobile' => '📱',
    ];
    foreach ($icons as $key => $icon) {
        if (str_contains($a, $key)) {
            return $icon;
        }
    }
    return '✦';
}

function analise_prioridade_label(string $prioridade): string
{
    $p = analise_lower($prioridade);
    if (str_contains($p, 'alta')) {
        return 'Prioridade alta';
    }
    if (str_contains($p, 'méd') || str_contains($p, 'med')) {
        return 'Prioridade média';
    }
    return 'Prioridade baixa';
}

function analise_score_maturidade(array $score): int
{
    if (isset($score['maturidade'])) {
        return max(0, min(100, (int) $score['maturidade']));
    }

    $nota = (int) ($score['nota'] ?? 0);
    return max(0, min(100, $nota * 10));
}

/**
 * @return array{label: string, class: string}
 */
function analise_score_estruturacao(int $maturidade): array
{
    if ($maturidade <= 30) {
        return ['label' => 'Pouco estruturado', 'class' => 'is-baixo'];
    }
    if ($maturidade <= 50) {
        return ['label' => 'Parcial', 'class' => 'is-parcial'];
    }
    if ($maturidade <= 70) {
        return ['label' => 'Em desenvolvimento', 'class' => 'is-medio'];
    }

    return ['label' => 'Estruturado', 'class' => 'is-alto'];
}

/**
 * @return array{label: string, class: string}
 */
function analise_score_oportunidade(int $maturidade): array
{
    if ($maturidade <= 35) {
        return ['label' => 'Alta', 'class' => 'is-alta'];
    }
    if ($maturidade <= 55) {
        return ['label' => 'Média', 'class' => 'is-media'];
    }

    return ['label' => 'Baixa', 'class' => 'is-baixa'];
}

/**
 * @param array<int, array<string, mixed>> $scores
 * @return array<int, array<string, mixed>>
 */
function analise_scores_hero_mini(array $scores, int $limit = 3): array
{
    if ($scores === []) {
        return [];
    }

    $flagged = array_values(array_filter($scores, static function (array $score): bool {
        return !empty($score['destaqueHero']);
    }));

    if ($flagged !== []) {
        usort($flagged, static function (array $a, array $b): int {
            return analise_score_maturidade($a) <=> analise_score_maturidade($b);
        });
        return array_slice($flagged, 0, $limit);
    }

    $sorted = $scores;
    usort($sorted, static function (array $a, array $b): int {
        return analise_score_maturidade($a) <=> analise_score_maturidade($b);
    });

    return array_slice($sorted, 0, $limit);
}

function analise_format_percent(float $value): string
{
    return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',') . '%';
}

function analise_format_ticket(int $value): string
{
    return 'R$ ' . number_format($value, 0, ',', '.');
}

function analise_format_faixa_ticket(int $min, int $max): string
{
    return analise_format_ticket($min) . ' a ' . analise_format_ticket($max);
}

function analise_format_moeda_compact(int $value): string
{
    if ($value >= 1000000) {
        $millions = $value / 1000000;
        if ($millions >= 10 || abs($millions - round($millions)) < 0.05) {
            return 'R$ ' . number_format((int) round($millions), 0, ',', '.') . 'M';
        }

        return 'R$ ' . number_format($millions, 1, ',', '.') . 'M';
    }

    if ($value >= 1000) {
        $thousands = (int) round($value / 1000);

        return 'R$ ' . number_format($thousands, 0, ',', '.') . 'k';
    }

    return 'R$ ' . number_format($value, 0, ',', '.');
}

function analise_format_faixa_receita(int $min, int $max): string
{
    return analise_format_moeda_compact($min) . ' a ' . analise_format_moeda_compact($max);
}

function analise_format_faixa_incremento(int $min, int $max): string
{
    return '+' . analise_format_moeda_compact($min) . ' a ' . analise_format_moeda_compact($max);
}

function analise_render_expired(string $empresa): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Análise expirada | ProspectAds</title>
        <link rel="stylesheet" href="/assets/design-system.css">
        <link rel="stylesheet" href="/analises/_template/analise.css">
    </head>
    <body class="analise-page">
        <main class="analise-expired">
            <div class="analise-expired__card">
                <p class="analise-expired__brand">ProspectAds</p>
                <h1>Análise expirada</h1>
                <p>O diagnóstico de crescimento<?= $empresa !== '' ? ' de <strong>' . analise_h($empresa) . '</strong>' : '' ?> não está mais disponível.</p>
                <a class="analise-btn analise-btn--primary" href="https://prospectads.com.br/">Visitar ProspectAds</a>
            </div>
        </main>
    </body>
    </html>
    <?php
}
