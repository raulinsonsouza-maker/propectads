<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$pdo = get_pdo();
ensure_schema($pdo);

$base = site_base_url();
$blogUrl = $base . blog_index_path();

$stmt = $pdo->query(
    "SELECT p.*, c.name AS category_name
     FROM blog_posts p
     LEFT JOIN blog_categories c ON c.id = p.category_id
     WHERE p.status = 'published'
     ORDER BY p.published_at DESC
     LIMIT 30"
);
$posts = $stmt->fetchAll();

$lastBuild = $posts[0]['published_at'] ?? gmdate('Y-m-d\TH:i:s\Z');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>Blog ProspectAds — E-commerce</title>
    <link><?= htmlspecialchars($blogUrl) ?></link>
    <description>Artigos sobre tráfego, conversão e crescimento para lojas online.</description>
    <language>pt-BR</language>
    <lastBuildDate><?= htmlspecialchars(gmdate('D, d M Y H:i:s', strtotime((string) $lastBuild)) . ' GMT') ?></lastBuildDate>
    <atom:link href="<?= htmlspecialchars($base . '/blog/feed.php') ?>" rel="self" type="application/rss+xml"/>
    <?php foreach ($posts as $post):
        $link = $base . blog_post_path((string) $post['slug']);
        $desc = meta_excerpt((string) ($post['excerpt'] ?: strip_tags((string) $post['content_html'])));
        $pub = gmdate('D, d M Y H:i:s', strtotime((string) $post['published_at'])) . ' GMT';
        ?>
    <item>
        <title><?= htmlspecialchars((string) $post['title']) ?></title>
        <link><?= htmlspecialchars($link) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($link) ?></guid>
        <pubDate><?= htmlspecialchars($pub) ?></pubDate>
        <description><?= htmlspecialchars($desc) ?></description>
        <?php if (!empty($post['category_name'])): ?>
        <category><?= htmlspecialchars((string) $post['category_name']) ?></category>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
