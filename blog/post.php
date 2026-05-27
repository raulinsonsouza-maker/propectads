<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';
require __DIR__ . '/includes/partials.php';
require __DIR__ . '/includes/seo.php';

$slug = blog_resolve_post_slug();
if ($slug === '') {
    http_response_code(404);
    echo 'Artigo não encontrado.';
    exit;
}

$pdo = get_pdo();
ensure_schema($pdo);

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM blog_posts p
     LEFT JOIN blog_categories c ON c.id = p.category_id
     WHERE p.slug = :slug AND p.status = 'published'"
);
$stmt->execute([':slug' => $slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo 'Artigo não encontrado.';
    exit;
}

blog_record_post_view($pdo, (int) $post['id']);

$related = get_related_blog_posts($pdo, $post, 4);

$base = site_base_url();
$canonical = $base . blog_post_path((string) $post['slug']);
$metaTitle = ($post['meta_title'] ?: $post['title']) . ' | Blog ProspectAds';
$metaDesc = meta_excerpt((string) ($post['meta_description'] ?: $post['excerpt'] ?: strip_tags((string) $post['content_html'])));
$ogImage = !empty($post['featured_image']) ? $base . $post['featured_image'] : blog_default_og_image();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php blog_render_head_common($metaTitle, $metaDesc, $canonical, 'article'); ?>
    <?php if (!empty($post['featured_image'])): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>
    <meta property="article:published_time" content="<?= htmlspecialchars((string) $post['published_at']) ?>">
    <meta property="article:modified_time" content="<?= htmlspecialchars((string) ($post['updated_at'] ?? $post['published_at'])) ?>">
    <?php if (!empty($post['category_name'])): ?>
    <meta property="article:section" content="<?= htmlspecialchars((string) $post['category_name']) ?>">
    <?php endif; ?>
    <?php blog_render_article_schema($post, $canonical, $metaDesc); ?>
    <?php blog_render_gtag(); ?>
    <?php blog_head_styles(); ?>
</head>
<body class="blog-article">
    <header class="header">
        <nav class="nav container">
            <div class="nav__logo">
                <?php site_brand_link('/'); ?>
            </div>
            <ul class="nav__menu">
                <li><a href="/#servicos">Serviços</a></li>
                <li><a href="/blog/">Blog</a></li>
                <li><a href="/ecommerce-analise/" class="btn btn--primary btn--header">Análise e-commerce</a></li>
            </ul>
        </nav>
    </header>

    <main class="article-page">
        <header class="blog-hero">
            <div class="blog-hero__bg" aria-hidden="true">
                <div class="blog-hero__grid"></div>
            </div>
            <div class="container">
                <nav class="blog-breadcrumb" aria-label="Navegação">
                    <a href="/">Início</a>
                    <span class="blog-breadcrumb__sep">/</span>
                    <a href="/blog/">Blog</a>
                    <?php if (!empty($post['category_name']) && !empty($post['category_slug'])): ?>
                        <span class="blog-breadcrumb__sep">/</span>
                        <a href="<?= htmlspecialchars(blog_category_url((string) $post['category_slug'])) ?>"><?= htmlspecialchars((string) $post['category_name']) ?></a>
                    <?php elseif (!empty($post['category_name'])): ?>
                        <span class="blog-breadcrumb__sep">/</span>
                        <span class="blog-breadcrumb__current"><?= htmlspecialchars((string) $post['category_name']) ?></span>
                    <?php endif; ?>
                </nav>

                <?php if (!empty($post['category_name']) && !empty($post['category_slug'])): ?>
                    <p class="blog-eyebrow">
                        <a href="<?= htmlspecialchars(blog_category_url((string) $post['category_slug'])) ?>"><?= htmlspecialchars((string) $post['category_name']) ?></a>
                    </p>
                <?php elseif (!empty($post['category_name'])): ?>
                    <p class="blog-eyebrow"><?= htmlspecialchars((string) $post['category_name']) ?></p>
                <?php else: ?>
                    <p class="blog-eyebrow">Artigo</p>
                <?php endif; ?>

                <h1 class="blog-hero__title blog-hero__title--article"><?= htmlspecialchars((string) $post['title']) ?></h1>

                <?php if (!empty($post['excerpt'])): ?>
                    <p class="blog-hero__subtitle blog-hero__subtitle--article"><?= htmlspecialchars((string) $post['excerpt']) ?></p>
                <?php endif; ?>

                <?php if (empty($post['featured_image'])): ?>
                    <?php blog_card_visual($post, 'hero'); ?>
                <?php endif; ?>
            </div>
        </header>

        <div class="article-layout">
            <?php if (!empty($post['featured_image'])): ?>
                <figure class="article__cover">
                    <img
                        src="<?= htmlspecialchars((string) $post['featured_image']) ?>"
                        alt="<?= htmlspecialchars(blog_featured_image_alt($post)) ?>"
                        width="720"
                        height="405"
                        loading="eager"
                        decoding="async"
                    >
                </figure>
            <?php endif; ?>
            <article class="article-main">
                <div class="article__body">
                    <?= $post['content_html'] ?>
                </div>

                <div class="article__cta">
                    <h2>Seu e-commerce vende. O próximo passo é escalar com clareza.</h2>
                    <p>Converse com a ProspectAds: mapeamos o que limita o crescimento da sua operação e o que priorizar em anúncios, site e atendimento.</p>
                    <a href="/ecommerce-analise/" class="btn btn--large btn--primary">Solicitar uma análise do meu e-commerce</a>
                </div>

                <?php blog_render_related_posts($related); ?>
            </article>

            <aside class="article-sidebar" aria-label="Complementos do artigo">
                <div class="article-sidebar__card article-sidebar__card--accent">
                    <h3>Análise comercial do e-commerce</h3>
                    <p>Anúncios, site, oferta, WhatsApp e prioridades para fazer o negócio crescer.</p>
                    <a href="/ecommerce-analise/" class="btn btn--primary">Solicitar uma análise do meu e-commerce</a>
                </div>
                <?php if (!empty($related)): ?>
                <div class="article-sidebar__card">
                    <h3>Artigos relacionados</h3>
                    <ul class="article-sidebar__list">
                        <?php foreach (array_slice($related, 0, 3) as $rel): ?>
                            <li><a href="<?= htmlspecialchars(blog_post_url((string) $rel['slug'])) ?>"><?= htmlspecialchars((string) $rel['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <?php blog_footer(); ?>
</body>
</html>
