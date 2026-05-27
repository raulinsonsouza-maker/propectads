<?php

declare(strict_types=1);

/**
 * Sincroniza artigos PHP → SQLite (update se slug existir).
 * Uso: php scripts/sync-blog-articles-from-php.php [21-30|all|slug-unico]
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$arg = $argv[1] ?? '21-30';

$map = [
    '21-30' => [
        __DIR__ . '/blog-articles/articles-21-25.php',
        __DIR__ . '/blog-articles/articles-26-30.php',
    ],
    'all' => glob(__DIR__ . '/blog-articles/articles-*.php') ?: [],
];

$files = $map[$arg] ?? [];
if ($files === [] && str_contains($arg, '.php')) {
    $files = [$arg];
} elseif ($files === [] && preg_match('/^[a-z0-9-]+$/', $arg)) {
    $files = $map['21-30'];
    $onlySlug = $arg;
} else {
    $onlySlug = null;
}

if ($arg !== '21-30' && $arg !== 'all' && !isset($onlySlug)) {
    $onlySlug = preg_match('/^[a-z0-9-]+$/', $arg) ? $arg : null;
    if ($onlySlug === null && !is_file($arg)) {
        fwrite(STDERR, "Uso: php scripts/sync-blog-articles-from-php.php [21-30|all|slug]\n");
        exit(1);
    }
}

$pdo = get_pdo();
ensure_schema($pdo);
$updated = 0;

foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Arquivo não encontrado: {$file}\n");
        continue;
    }
    static $helpersLoaded = false;
    if (!$helpersLoaded) {
        require_once __DIR__ . '/blog-articles/helpers.php';
        $helpersLoaded = true;
    }
    $batch = require $file;
    if (!is_array($batch)) {
        continue;
    }
    foreach ($batch as $article) {
        $slug = (string) ($article['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        if (isset($onlySlug) && $onlySlug !== null && $onlySlug !== $slug) {
            continue;
        }

        $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            echo "Skip (não no banco): {$slug}\n";
            continue;
        }

        $content = sanitize_post_html((string) $article['content_html']);
        $reading = estimate_reading_time($content);
        $now = gmdate('c');

        $pdo->prepare(
            'UPDATE blog_posts SET
                title = :title,
                excerpt = :excerpt,
                content_html = :content,
                meta_title = :meta_title,
                meta_description = :meta_description,
                published_at = :published_at,
                reading_time_min = :reading,
                updated_at = :updated
             WHERE id = :id'
        )->execute([
            ':title' => $article['title'],
            ':excerpt' => $article['excerpt'],
            ':content' => $content,
            ':meta_title' => $article['meta_title'],
            ':meta_description' => $article['meta_description'],
            ':published_at' => $article['published_at'],
            ':reading' => $reading,
            ':updated' => $now,
            ':id' => $row['id'],
        ]);

        $words = str_word_count(strip_tags($content));
        echo "Atualizado: {$slug} (~{$words} palavras, {$reading} min)\n";
        $updated++;
    }
}

echo "\nSync concluído ({$updated} posts).\n";
