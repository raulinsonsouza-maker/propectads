<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/site-favicon.php';

function blog_default_og_image(): string
{
    return site_base_url() . '/assets/og-default.svg';
}

function blog_organization_schema(): array
{
    $base = site_base_url();

    return [
        '@type' => 'Organization',
        '@id' => $base . '/#organization',
        'name' => 'ProspectAds',
        'url' => $base . '/',
    ];
}

function blog_website_schema(): array
{
    $base = site_base_url();

    return [
        '@type' => 'WebSite',
        '@id' => $base . '/#website',
        'url' => $base . '/',
        'name' => 'ProspectAds',
        'publisher' => ['@id' => $base . '/#organization'],
        'inLanguage' => 'pt-BR',
    ];
}

function blog_author_schema(): array
{
    $base = site_base_url();

    return [
        '@type' => 'Organization',
        'name' => 'Equipe ProspectAds',
        'url' => $base . '/',
    ];
}

function blog_render_gtag(): void
{
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-6Y2YB8KS0F"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-6Y2YB8KS0F');
    </script>
    <?php
}

function blog_render_head_common(string $title, string $description, string $canonical, string $type = 'website'): void
{
    $description = meta_excerpt($description, 160);
    $ogImage = blog_default_og_image();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php site_render_favicon(); ?>
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <link rel="alternate" type="application/rss+xml" title="Blog ProspectAds" href="<?= htmlspecialchars(site_base_url() . '/blog/feed.php') ?>">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="ProspectAds">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:type" content="<?= htmlspecialchars($type) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
    <?php
}

function blog_render_index_schema(array $posts): void
{
    $base = site_base_url();
    $items = array_map(static function ($p) use ($base) {
        return [
            '@type' => 'BlogPosting',
            '@id' => $base . blog_post_path((string) $p['slug']) . '#article',
            'headline' => $p['title'],
            'url' => $base . blog_post_path((string) $p['slug']),
            'datePublished' => $p['published_at'],
            'dateModified' => $p['updated_at'] ?? $p['published_at'],
            'author' => blog_author_schema(),
            'image' => !empty($p['featured_image']) ? $base . $p['featured_image'] : blog_default_og_image(),
        ];
    }, array_slice($posts, 0, 20));

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            blog_organization_schema(),
            blog_website_schema(),
            [
                '@type' => 'Blog',
                '@id' => $base . blog_index_path() . '#blog',
                'url' => $base . blog_index_path(),
                'name' => 'Blog ProspectAds',
                'description' => 'Artigos sobre tráfego, conversão e crescimento para e-commerce.',
                'publisher' => ['@id' => $base . '/#organization'],
                'inLanguage' => 'pt-BR',
                'blogPost' => $items,
            ],
        ],
    ];
    ?>
    <script type="application/ld+json">
    <?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
    <?php
}

function blog_schema_text(string $text): string
{
    $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $normalized = preg_replace('/\s+/u', ' ', trim($decoded));

    return $normalized === null ? trim($decoded) : $normalized;
}

function blog_schema_node_text(DOMNode $node): string
{
    return blog_schema_text($node->textContent ?? '');
}

function blog_schema_is_faq_heading(DOMElement $heading): bool
{
    $text = utf8_strtolower(blog_schema_node_text($heading));

    return str_contains($text, 'perguntas frequentes') || str_contains($text, 'faq');
}

function blog_extract_faq_schema_items(string $html): array
{
    if (trim($html) === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) {
        return [];
    }

    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) {
        return [];
    }

    $items = [];
    $inFaq = false;
    $question = null;
    $answerParts = [];
    $flush = static function () use (&$items, &$question, &$answerParts): void {
        if ($question === null) {
            return;
        }

        $answer = blog_schema_text(implode("\n\n", $answerParts));
        if ($question !== '' && $answer !== '') {
            $items[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        $question = null;
        $answerParts = [];
    };

    foreach ($root->childNodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'h2') {
            $flush();
            $inFaq = blog_schema_is_faq_heading($node);
            continue;
        }

        if (!$inFaq) {
            continue;
        }

        if ($tag === 'h3') {
            $flush();
            $question = blog_schema_node_text($node);
            continue;
        }

        if ($question !== null) {
            $answer = blog_schema_node_text($node);
            if ($answer !== '') {
                $answerParts[] = $answer;
            }
        }
    }

    $flush();

    return $items;
}

function blog_render_article_schema(array $post, string $canonical, string $metaDesc): void
{
    $base = site_base_url();
    $wordCount = str_word_count(strip_tags((string) $post['content_html']));

    $article = array_filter([
        '@type' => 'BlogPosting',
        '@id' => $canonical . '#article',
        'headline' => $post['title'],
        'description' => $metaDesc,
        'datePublished' => $post['published_at'],
        'dateModified' => $post['updated_at'] ?? $post['published_at'],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
        'wordCount' => $wordCount > 0 ? $wordCount : null,
        'inLanguage' => 'pt-BR',
        'author' => blog_author_schema(),
        'publisher' => blog_organization_schema(),
        'articleSection' => $post['category_name'] ?? null,
        'image' => !empty($post['featured_image']) ? $base . $post['featured_image'] : blog_default_og_image(),
    ]);

    $breadcrumbs = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Início',
                'item' => $base . '/',
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Blog',
                'item' => $base . blog_index_path(),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $post['title'],
                'item' => $canonical,
            ],
        ],
    ];

    $graph = [
        blog_organization_schema(),
        blog_website_schema(),
        $article,
        $breadcrumbs,
    ];
    $faqItems = blog_extract_faq_schema_items((string) $post['content_html']);
    if (!empty($faqItems)) {
        $graph[] = [
            '@type' => 'FAQPage',
            '@id' => $canonical . '#faq',
            'mainEntity' => $faqItems,
            'inLanguage' => 'pt-BR',
            'isPartOf' => ['@id' => $canonical . '#article'],
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];
    ?>
    <script type="application/ld+json">
    <?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
    <?php
}

function blog_render_related_posts(array $related): void
{
    if (empty($related)) {
        return;
    }
    ?>
    <section class="blog-related" aria-labelledby="blog-related-title">
        <h2 id="blog-related-title" class="blog-related__title">Continue lendo</h2>
        <div class="blog-related__grid">
            <?php foreach ($related as $rel): ?>
                <a href="<?= htmlspecialchars(blog_post_url((string) $rel['slug'])) ?>" class="blog-related__card">
                    <?php if (!empty($rel['category_name'])): ?>
                    <span class="blog-related__meta"><?= htmlspecialchars((string) $rel['category_name']) ?></span>
                    <?php endif; ?>
                    <span class="blog-related__heading"><?= htmlspecialchars((string) $rel['title']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
