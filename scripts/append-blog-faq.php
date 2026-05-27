<?php

declare(strict_types=1);

/**
 * Anexa blocos FAQ a artigos abaixo da meta de palavras.
 * Uso: php scripts/append-blog-faq.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$expansions = require __DIR__ . '/blog-articles/faq-expansions.php';
$pdo = get_pdo();
ensure_schema($pdo);

foreach ($expansions as $slug => $html) {
    $stmt = $pdo->prepare('SELECT id, content_html FROM blog_posts WHERE slug = :slug AND status = \'published\'');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    if (!$row) {
        echo "Skip (não encontrado): {$slug}\n";
        continue;
    }

    $current = (string) $row['content_html'];
    if (str_contains($current, 'Perguntas frequentes')) {
        echo "Skip (FAQ já existe): {$slug}\n";
        continue;
    }

    $marker = '<blockquote>';
    $pos = strpos($current, $marker);
    if ($pos === false) {
        $combined = $current . $html;
    } else {
        $combined = substr($current, 0, $pos) . $html . substr($current, $pos);
    }

    $content = sanitize_post_html($combined);
    $reading = estimate_reading_time($content);
    $words = str_word_count(strip_tags($content));

    $pdo->prepare(
        'UPDATE blog_posts SET content_html = :c, reading_time_min = :r, updated_at = :u WHERE id = :id'
    )->execute([
        ':c' => $content,
        ':r' => $reading,
        ':u' => gmdate('c'),
        ':id' => $row['id'],
    ]);

    echo "FAQ anexado: {$slug} (~{$words} palavras, {$reading} min)\n";
}

echo "Concluído.\n";
