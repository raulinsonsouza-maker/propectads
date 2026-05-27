<?php

declare(strict_types=1);

/**
 * Cadastra os artigos 21–30 (operação, conversão profunda, métricas, retenção).
 * Uso: php scripts/seed-blog-articles-21-30.php
 *
 * Após editar conteúdo: php scripts/sync-blog-articles-from-php.php 21-30
 * Validação: php scripts/validate-blog-links.php (sem links /blog/ quebrados ou em excesso)
 * Links internos: preferir blog_link() em helpers.php (máx. 2 por artigo, só slugs publicados)
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

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
        ':published_at' => $post['published_at'],
        ':created' => $now,
        ':updated' => $now,
    ]);

    $words = str_word_count(strip_tags($content));
    $chars = strlen(strip_tags($content));
    echo "Criado: {$post['slug']} (~{$words} palavras, {$chars} chars, {$reading} min)\n";
}

$pdo = get_pdo();
ensure_schema($pdo);

$catTrafego = ensure_category($pdo, 'Tráfego e mídia', 'trafego-midia', 'Anúncios, ROAS e canais de aquisição');
$catConversao = ensure_category($pdo, 'Conversão e vendas', 'conversao-vendas', 'Site, checkout, oferta e CRM');
$catEstrategia = ensure_category($pdo, 'Estratégia e crescimento', 'estrategia-crescimento', 'Escala, margem e operação do e-commerce');
$catEcommerce = ensure_category($pdo, 'E-commerce', 'e-commerce', 'Métricas, SEO, pricing e operação da loja');

$files = [
    __DIR__ . '/blog-articles/articles-21-25.php',
    __DIR__ . '/blog-articles/articles-26-30.php',
];

$articles = [];
foreach ($files as $file) {
    if (!is_file($file)) {
        throw new RuntimeException('Arquivo não encontrado: ' . $file);
    }
    $batch = require $file;
    if (!is_array($batch)) {
        throw new RuntimeException('Batch inválido: ' . $file);
    }
    $articles = array_merge($articles, $batch);
}

foreach ($articles as &$article) {
    if (!isset($article['category_key'])) {
        throw new RuntimeException('category_key ausente em ' . ($article['slug'] ?? '?'));
    }
    $key = $article['category_key'];
    $article['category_id'] = match ($key) {
        'trafego', 'tráfego' => $catTrafego,
        'conversao', 'conversão' => $catConversao,
        'ecommerce', 'e-commerce' => $catEcommerce,
        'estrategia', 'estratégia' => $catEstrategia,
        default => throw new RuntimeException("category_key inválida: {$key} em " . ($article['slug'] ?? '?')),
    };
    unset($article['category_key']);
}
unset($article);

foreach ($articles as $article) {
    seed_post($pdo, $article);
}

echo "\nSeed dos artigos 21–30 concluído (" . count($articles) . " artigos processados).\n";
