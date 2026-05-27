<?php

declare(strict_types=1);

/**
 * Exporta slugs publicados para scripts/blog-articles/published-slugs.php
 * Uso: php scripts/export-blog-slugs.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$pdo = get_pdo();
ensure_schema($pdo);

$stmt = $pdo->query("SELECT slug FROM blog_posts WHERE status = 'published' ORDER BY slug ASC");
$slugs = $stmt->fetchAll(PDO::FETCH_COLUMN);

$out = __DIR__ . '/blog-articles/published-slugs.php';
$php = "<?php\n\ndeclare(strict_types=1);\n\n/** Gerado em " . gmdate('c') . " — não edite à mão */\nreturn " . var_export($slugs, true) . ";\n";

file_put_contents($out, $php);

echo 'Exportados ' . count($slugs) . " slugs → {$out}\n";
