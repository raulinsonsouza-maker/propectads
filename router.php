<?php

declare(strict_types=1);

/**
 * Roteador para o servidor embutido do PHP: php -S 127.0.0.1:8080 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri = rawurldecode($uri);

// LPs locais removidas
foreach ([
    '/agencia-marketing-digital-americana',
    '/agencia-marketing-digital-limeira',
    '/agencia-marketing-digital-piracicaba',
] as $legacyLp) {
    if ($uri === $legacyLp || $uri === $legacyLp . '/') {
        header('Location: /', true, 301);
        exit;
    }
}

// Blog: categoria
if (preg_match('#^/blog/categoria/([a-z0-9][a-z0-9-]*)/?$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/blog/category.php';
    return true;
}

if (preg_match('#^/blog/category\.php$#', $uri)) {
    require __DIR__ . '/blog/category.php';
    return true;
}

// Blog: artigo (antes de is_file — /blog/slug/ não é arquivo)
if (preg_match('#^/blog/([a-z0-9][a-z0-9-]*)/?$#', $uri, $m)
    && !in_array($m[1], ['index', 'post', 'sitemap', 'feed', 'categoria', 'category'], true)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/blog/post.php';
    return true;
}

if (preg_match('#^/blog/post\.php$#', $uri)) {
    $slug = isset($_GET['slug']) ? slugify((string) $_GET['slug']) : '';
    if ($slug !== '') {
        header('Location: /blog/' . rawurlencode($slug) . '/', true, 301);
        exit;
    }
    require __DIR__ . '/blog/post.php';
    return true;
}

if ($uri === '/blog' || $uri === '/blog/') {
    require __DIR__ . '/blog/index.php';
    return true;
}

if ($uri === '/blog/sitemap.php' || $uri === '/blog/sitemap') {
    require __DIR__ . '/blog/sitemap.php';
    return true;
}

if ($uri === '/blog/feed.php' || $uri === '/blog/feed') {
    require __DIR__ . '/blog/feed.php';
    return true;
}

// Análises de crescimento (privadas)
if (preg_match('#^/analises/([a-z0-9][a-z0-9-]*)/?$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/analises/_template/index.php';
    return true;
}

if ($uri === '/sitemap.php' || $uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}

$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}

return false;
