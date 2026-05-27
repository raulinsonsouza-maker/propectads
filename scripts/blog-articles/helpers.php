<?php

declare(strict_types=1);

if (!function_exists('blog_published_slugs')) {
/**
 * Slugs publicados — gere com: php scripts/export-blog-slugs.php
 * Só use blog_link() com slugs desta lista para evitar link quebrado no site.
 */
function blog_published_slugs(): array
{
    static $slugs = null;
    if ($slugs !== null) {
        return $slugs;
    }
    $file = __DIR__ . '/published-slugs.php';
    if (!is_file($file)) {
        $slugs = [];

        return $slugs;
    }
    $loaded = require $file;
    $slugs = is_array($loaded) ? array_values(array_filter($loaded, 'is_string')) : [];

    return $slugs;
}
}

if (!function_exists('blog_link')) {
    /**
     * Link interno só se o artigo existir na lista publicada; senão retorna texto puro.
     */
    function blog_link(string $slug, string $label): string
    {
        $slug = slugify($slug);
        $label = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($slug === '' || !in_array($slug, blog_published_slugs(), true)) {
            return $label;
        }

        return '<a href="/blog/' . rawurlencode($slug) . '/">' . $label . '</a>';
    }
}

if (!function_exists('blog_cta')) {
function blog_cta(string $headline = 'Quer descobrir o gargalo do seu e-commerce?'): string
{
    $text = 'Solicite a análise comercial do seu e-commerce. Olhamos anúncios, site, oferta, WhatsApp e o que priorizar para escalar.';

    return <<<HTML
<blockquote>
<p><strong>{$headline}</strong> {$text}</p>
<p><a href="/ecommerce-analise/">Solicitar uma análise do meu e-commerce</a></p>
</blockquote>
HTML;
}
}
