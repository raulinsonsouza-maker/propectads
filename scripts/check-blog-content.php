<?php

require dirname(__DIR__) . '/api/bootstrap.php';

$pdo = get_pdo();
$rows = $pdo->query("SELECT slug, length(content_html) AS len, title FROM blog_posts WHERE status = 'published' ORDER BY id DESC LIMIT 5")->fetchAll();
foreach ($rows as $r) {
    echo $r['slug'] . ' | len=' . $r['len'] . ' | ' . $r['title'] . PHP_EOL;
}
