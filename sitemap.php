<?php

declare(strict_types=1);

require __DIR__ . '/api/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

function sitemap_lastmod(?string $date): ?string
{
    if ($date === null || $date === '') {
        return null;
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return null;
    }

    return gmdate('Y-m-d', $timestamp);
}

function sitemap_file_lastmod(string $path): ?string
{
    if (!is_file($path)) {
        return null;
    }

    $timestamp = filemtime($path);
    if ($timestamp === false) {
        return null;
    }

    return gmdate('Y-m-d', $timestamp);
}

function sitemap_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$pdo = get_pdo();
ensure_schema($pdo);

$base = site_base_url();

$urls = [
    [
        'loc' => $base . '/',
        'lastmod' => sitemap_file_lastmod(__DIR__ . '/index.html'),
        'priority' => '1.0',
        'changefreq' => 'weekly',
    ],
    [
        'loc' => $base . '/ecommerce-analise/',
        'lastmod' => sitemap_file_lastmod(__DIR__ . '/ecommerce-analise/index.html'),
        'priority' => '0.95',
        'changefreq' => 'weekly',
    ],
    [
        'loc' => $base . blog_index_path(),
        'lastmod' => sitemap_file_lastmod(__DIR__ . '/blog/index.php'),
        'priority' => '0.9',
        'changefreq' => 'weekly',
    ],
];

$stmt = $pdo->query(
    "SELECT slug, updated_at, published_at FROM blog_posts
     WHERE status = 'published' ORDER BY published_at DESC"
);

while ($row = $stmt->fetch()) {
    $lastmod = $row['updated_at'] ?? $row['published_at'];
    $urls[] = [
        'loc' => $base . blog_post_path((string) $row['slug']),
        'lastmod' => sitemap_lastmod((string) $lastmod),
        'priority' => '0.8',
        'changefreq' => 'monthly',
    ];
}

$catStmt = $pdo->query(
    "SELECT c.slug FROM blog_categories c
     INNER JOIN blog_posts p ON p.category_id = c.id AND p.status = 'published'
     GROUP BY c.id"
);
while ($cat = $catStmt->fetch()) {
    $urls[] = [
        'loc' => $base . blog_category_path((string) $cat['slug']),
        'priority' => '0.75',
        'changefreq' => 'weekly',
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . sitemap_xml_escape((string) $u['loc']) . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . sitemap_xml_escape((string) $u['lastmod']) . "</lastmod>\n";
    }
    if (!empty($u['changefreq'])) {
        echo '    <changefreq>' . sitemap_xml_escape((string) $u['changefreq']) . "</changefreq>\n";
    }
    if (!empty($u['priority'])) {
        echo '    <priority>' . sitemap_xml_escape((string) $u['priority']) . "</priority>\n";
    }
    echo "  </url>\n";
}

echo "</urlset>\n";
