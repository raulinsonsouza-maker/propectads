<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/site-brand.php';
require_once dirname(__DIR__, 2) . '/includes/site-footer.php';

function blog_footer(): void
{
    site_footer_render('/');
}

/** URLs absolutas a partir da raiz do site (evita 404 no /blog sem barra final). */
function blog_asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

/**
 * URL do artigo via post.php?slug= (funciona com e sem mod_rewrite).
 * Canonical SEO continua em /blog/slug/ via link rel="canonical".
 */
function blog_post_url(string $slug): string
{
    return blog_asset('blog/post.php?slug=' . rawurlencode($slug));
}

function blog_category_url(string $slug): string
{
    return blog_asset('blog/category.php?slug=' . rawurlencode($slug));
}

/** @param list<array{label: string, slug: string}> $topics */
function blog_render_hero_topics(array $topics, ?string $activeSlug = null): void
{
    if ($topics === []) {
        return;
    }
    ?>
    <ul class="blog-hero__topics" aria-label="Navegar por tema">
        <?php foreach ($topics as $topic): ?>
            <?php
            $slug = (string) $topic['slug'];
            $isActive = $activeSlug !== null && $activeSlug === $slug;
            ?>
            <li>
                <a
                    href="<?= htmlspecialchars(blog_category_url($slug)) ?>"
                    class="<?= $isActive ? 'is-active' : '' ?>"
                    <?= $isActive ? ' aria-current="page"' : '' ?>
                ><?= htmlspecialchars((string) $topic['label']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function blog_head_styles(): void
{
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(blog_asset('assets/design-system.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(blog_asset('styles.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(blog_asset('blog/blog.css')) ?>">
    <?php
}

function blog_category_class(?string $slug): string
{
    $slug = $slug ?: 'geral';
    $allowed = ['e-commerce', 'conversao', 'trafego-midia', 'conversao-vendas', 'estrategia-crescimento', 'geral'];
    if (!in_array($slug, $allowed, true)) {
        $slug = 'geral';
    }

    return 'blog-visual--' . preg_replace('/[^a-z0-9-]/', '', $slug);
}

function blog_card_visual(array $post, string $size = 'card'): void
{
    $hasImage = !empty($post['featured_image']);
    $catClass = blog_category_class($post['category_slug'] ?? null);
    $title = (string) ($post['title'] ?? '');
    $trimmed = trim($title);
    $initial = $trimmed !== ''
        ? utf8_strtoupper(utf8_substr($trimmed, 0, 1))
        : 'P';
    ?>
    <div class="blog-visual <?= $catClass ?> blog-visual--<?= htmlspecialchars($size) ?>">
        <?php if ($hasImage): ?>
            <img src="<?= htmlspecialchars((string) $post['featured_image']) ?>" alt="<?= htmlspecialchars(blog_featured_image_alt($post)) ?>" loading="lazy" decoding="async">
        <?php else: ?>
            <span class="blog-visual__pattern" aria-hidden="true"></span>
            <span class="blog-visual__initial" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
            <svg class="blog-visual__icon" width="48" height="48" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 19h16M6 16l3-8 3 4 3-6 3 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        <?php endif; ?>
    </div>
    <?php
}

/** Exibe só categoria (sem data nem tempo de leitura — evita “artigo velho” ou “muito longo”). */
function blog_meta_row(?string $categoryName = null, ?string $categorySlug = null, bool $linkCategory = true): void
{
    if ($categoryName === null || $categoryName === '') {
        return;
    }
    $canLink = $linkCategory && $categorySlug !== null && $categorySlug !== '';
    ?>
    <ul class="blog-meta">
        <li class="blog-meta__item blog-meta__item--category">
            <?php if ($canLink): ?>
                <a href="<?= htmlspecialchars(blog_category_url($categorySlug)) ?>"><?= htmlspecialchars($categoryName) ?></a>
            <?php else: ?>
                <?= htmlspecialchars($categoryName) ?>
            <?php endif; ?>
        </li>
    </ul>
    <?php
}
