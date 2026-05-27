<?php

declare(strict_types=1);

/**
 * Atualiza no banco os artigos 11–20 a partir dos arquivos PHP (conteúdo + meta).
 * Uso: php scripts/sync-blog-articles-11-20.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$pdo = get_pdo();
ensure_schema($pdo);

$catTrafego = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'trafego-midia'")->fetchColumn();
$catConversao = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'conversao-vendas'")->fetchColumn();
$catEstrategia = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'estrategia-crescimento'")->fetchColumn();

$files = [
    __DIR__ . '/blog-articles/articles-11-15.php',
    __DIR__ . '/blog-articles/articles-16-20.php',
];

$articles = [];
foreach ($files as $file) {
    $batch = require $file;
    $articles = array_merge($articles, $batch);
}

$updated = 0;
$missing = 0;

foreach ($articles as $article) {
    $categoryId = match ($article['category_key'] ?? '') {
        'trafego' => $catTrafego,
        'conversao' => $catConversao,
        'estrategia' => $catEstrategia,
        default => $catEstrategia,
    };

    $content = sanitize_post_html($article['content_html']);
    $reading = estimate_reading_time($content);
    $now = gmdate('c');

    $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug');
    $stmt->execute([':slug' => $article['slug']]);
    $row = $stmt->fetch();

    if (!$row) {
        echo "Ausente no banco (rode seed): {$article['slug']}\n";
        $missing++;
        continue;
    }

    $pdo->prepare(
        'UPDATE blog_posts SET
            title = :title,
            excerpt = :excerpt,
            content_html = :content,
            category_id = :category_id,
            meta_title = :meta_title,
            meta_description = :meta_description,
            reading_time_min = :reading,
            updated_at = :updated
        WHERE slug = :slug'
    )->execute([
        ':title' => $article['title'],
        ':excerpt' => $article['excerpt'],
        ':content' => $content,
        ':category_id' => $categoryId,
        ':meta_title' => $article['meta_title'],
        ':meta_description' => $article['meta_description'],
        ':reading' => $reading,
        ':updated' => $now,
        ':slug' => $article['slug'],
    ]);

    $chars = strlen(strip_tags($content));
    echo "Atualizado: {$article['slug']} (~{$chars} caracteres)\n";
    $updated++;
}

echo "\nSync concluído: {$updated} atualizados, {$missing} ausentes.\n";
