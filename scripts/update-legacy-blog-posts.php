<?php

declare(strict_types=1);

/**
 * Atualiza os 2 artigos legados no padrão long-form dos demais.
 * Uso: php scripts/update-legacy-blog-posts.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$articles = require __DIR__ . '/blog-articles/articles-legacy-2.php';

$pdo = get_pdo();
ensure_schema($pdo);

$catTrafego = null;
$catConversao = null;

foreach (['trafego-midia' => 'trafego', 'conversao-vendas' => 'conversao', 'e-commerce' => 'trafego'] as $slug => $key) {
    $stmt = $pdo->prepare('SELECT id FROM blog_categories WHERE slug = :slug');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    if ($row) {
        if ($key === 'trafego') {
            $catTrafego = (int) $row['id'];
        }
        if ($key === 'conversao') {
            $catConversao = (int) $row['id'];
        }
    }
}

if (!$catTrafego) {
    $pdo->exec("INSERT OR IGNORE INTO blog_categories (name, slug, description, created_at) VALUES ('Tráfego e mídia', 'trafego-midia', '', '" . gmdate('c') . "')");
    $catTrafego = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'trafego-midia'")->fetchColumn();
}
if (!$catConversao) {
    $pdo->exec("INSERT OR IGNORE INTO blog_categories (name, slug, description, created_at) VALUES ('Conversão e vendas', 'conversao-vendas', '', '" . gmdate('c') . "')");
    $catConversao = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'conversao-vendas'")->fetchColumn();
}

foreach ($articles as $article) {
    $categoryId = $article['category_key'] === 'conversao' ? $catConversao : $catTrafego;
    $content = sanitize_post_html($article['content_html']);
    $reading = estimate_reading_time($content);
    $words = str_word_count(strip_tags($content));

    $stmt = $pdo->prepare(
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
    );
    $stmt->execute([
        ':title' => $article['title'],
        ':excerpt' => $article['excerpt'],
        ':content' => $content,
        ':category_id' => $categoryId,
        ':meta_title' => $article['meta_title'],
        ':meta_description' => $article['meta_description'],
        ':reading' => $reading,
        ':updated' => gmdate('c'),
        ':slug' => $article['slug'],
    ]);

    if ($stmt->rowCount() > 0) {
        echo "Atualizado: {$article['slug']} (~{$words} palavras, {$reading} min)\n";
    } else {
        echo "Post não encontrado (rode seed-blog-posts.php antes): {$article['slug']}\n";
    }
}

echo "Concluído.\n";
