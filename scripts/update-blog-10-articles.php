<?php

declare(strict_types=1);

/**
 * Atualiza os 10 artigos de SEO (conteúdo, meta, tempo de leitura).
 * Uso: php scripts/update-blog-10-articles.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$files = [
    __DIR__ . '/blog-articles/articles-01-05.php',
    __DIR__ . '/blog-articles/articles-06-10.php',
];

$articles = [];
foreach ($files as $file) {
    $articles = array_merge($articles, require $file);
}

$pdo = get_pdo();
ensure_schema($pdo);

foreach ($articles as $article) {
    $content = sanitize_post_html($article['content_html']);
    $reading = estimate_reading_time($content);
    $words = str_word_count(strip_tags($content));

    $stmt = $pdo->prepare(
        'UPDATE blog_posts SET
            title = :title,
            excerpt = :excerpt,
            content_html = :content,
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
        ':meta_title' => $article['meta_title'],
        ':meta_description' => $article['meta_description'],
        ':reading' => $reading,
        ':updated' => gmdate('c'),
        ':slug' => $article['slug'],
    ]);

    if ($stmt->rowCount() > 0) {
        echo "Atualizado: {$article['slug']} (~{$words} palavras)\n";
    } else {
        echo "Não encontrado (rode seed primeiro): {$article['slug']}\n";
    }
}

echo "Concluído.\n";
