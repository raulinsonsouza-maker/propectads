<?php

declare(strict_types=1);

/**
 * Seed idempotente dos artigos do blog a partir dos HTML estáticos.
 * Uso: php scripts/seed-blog-posts.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

function extract_article_body(string $htmlPath): string
{
    $html = file_get_contents($htmlPath);
    if ($html === false) {
        throw new RuntimeException('Não foi possível ler: ' . $htmlPath);
    }
    if (!preg_match('#<div class="article__body">(.*?)</div>#s', $html, $m)) {
        throw new RuntimeException('article__body não encontrado em ' . $htmlPath);
    }
    return trim($m[1]);
}

function ensure_category(PDO $pdo, string $name, string $slug, string $description = ''): int
{
    $stmt = $pdo->prepare('SELECT id FROM blog_categories WHERE slug = :slug');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $now = gmdate('c');
    $pdo->prepare(
        'INSERT INTO blog_categories (name, slug, description, created_at) VALUES (:name, :slug, :desc, :created)'
    )->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':created' => $now]);

    return (int) $pdo->lastInsertId();
}

function seed_post(PDO $pdo, array $post): void
{
    $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug');
    $stmt->execute([':slug' => $post['slug']]);
    if ($stmt->fetch()) {
        echo "Skip (já existe): {$post['slug']}\n";
        return;
    }

    $content = sanitize_post_html($post['content_html']);
    $reading = estimate_reading_time($content);
    $now = gmdate('c');
    $publishedAt = $post['published_at'] ?? '2026-05-01T12:00:00+00:00';

    $pdo->prepare(
        'INSERT INTO blog_posts (
            title, slug, excerpt, content_html, status, category_id,
            featured_image, meta_title, meta_description, reading_time_min,
            published_at, created_at, updated_at
        ) VALUES (
            :title, :slug, :excerpt, :content, :status, :category_id,
            NULL, :meta_title, :meta_description, :reading,
            :published_at, :created, :updated
        )'
    )->execute([
        ':title' => $post['title'],
        ':slug' => $post['slug'],
        ':excerpt' => $post['excerpt'],
        ':content' => $content,
        ':status' => 'published',
        ':category_id' => $post['category_id'],
        ':meta_title' => $post['meta_title'],
        ':meta_description' => $post['meta_description'],
        ':reading' => $reading,
        ':published_at' => $publishedAt,
        ':created' => $now,
        ':updated' => $now,
    ]);

    echo "Criado: {$post['slug']}\n";
}

$pdo = get_pdo();
ensure_schema($pdo);

$catTrafego = ensure_category($pdo, 'Tráfego e mídia', 'trafego-midia', 'Anúncios, ROAS e canais de aquisição');
$catConversao = ensure_category($pdo, 'Conversão e vendas', 'conversao-vendas', 'Site, checkout, oferta e CRM');

$blogDir = $root . '/blog';

$posts = [
    [
        'slug' => 'trafego-pago-ecommerce',
        'title' => 'Tráfego pago para e-commerce: por onde começar',
        'excerpt' => 'Google Shopping, Meta Ads e remarketing: o que priorizar quando a loja ainda não escala de forma previsível.',
        'meta_title' => 'Tráfego pago para e-commerce',
        'meta_description' => 'Tráfego pago para e-commerce: Google Shopping, Meta Ads e remarketing. Guia prático da ProspectAds.',
        'category_id' => $catTrafego,
        'published_at' => '2026-05-10T12:00:00+00:00',
        'content_html' => extract_article_body($blogDir . '/trafego-pago-ecommerce.html'),
    ],
    [
        'slug' => 'melhorar-conversao-loja-online',
        'title' => '5 pontos para melhorar a conversão da sua loja online',
        'excerpt' => 'Antes de aumentar o orçamento de anúncios, vale revisar página de produto, checkout e prova social.',
        'meta_title' => 'Melhorar conversão da loja online',
        'meta_description' => '5 pontos para melhorar a conversão da sua loja online: velocidade, produto, prova social, checkout e remarketing.',
        'category_id' => $catConversao,
        'published_at' => '2026-05-12T12:00:00+00:00',
        'content_html' => extract_article_body($blogDir . '/melhorar-conversao-loja-online.html'),
    ],
];

foreach ($posts as $post) {
    seed_post($pdo, $post);
}

echo "Seed concluído.\n";
