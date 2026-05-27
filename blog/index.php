<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';
require __DIR__ . '/includes/partials.php';
require __DIR__ . '/includes/seo.php';

$pdo = get_pdo();
ensure_schema($pdo);

$stmt = $pdo->query(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM blog_posts p
     LEFT JOIN blog_categories c ON c.id = p.category_id
     WHERE p.status = 'published'
     ORDER BY p.published_at DESC"
);
$posts = $stmt->fetchAll();

$featured = $posts[0] ?? null;
$gridPosts = $posts ? array_slice($posts, 1) : [];
$postCount = count($posts);
$heroTopics = blog_resolve_hero_topics($pdo);

$canonical = site_base_url() . blog_index_path();
$metaTitle = 'Blog E-commerce | Tráfego, ROAS e Conversão — ProspectAds';
$metaDesc = 'Guias para dono de loja online: tráfego, ROAS, conversão e operação. Conteúdo prático para escalar faturamento com método.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php blog_render_head_common($metaTitle, $metaDesc, $canonical); ?>
    <?php if (!empty($posts)): ?>
        <?php blog_render_index_schema($posts); ?>
    <?php endif; ?>
    <?php blog_render_gtag(); ?>
    <?php blog_head_styles(); ?>
</head>
<body class="blog-index">
    <header class="header">
        <nav class="nav container">
            <div class="nav__logo">
                <?php site_brand_link('/'); ?>
            </div>
            <ul class="nav__menu" id="nav-menu">
                <li><a href="/#servicos">Serviços</a></li>
                <li><a href="/blog/" aria-current="page">Blog</a></li>
                <li><a href="/ecommerce-analise/" class="btn btn--primary btn--header">Análise e-commerce</a></li>
            </ul>
        </nav>
    </header>

    <main class="blog-page">
        <section class="blog-hero">
            <div class="blog-hero__bg" aria-hidden="true">
                <div class="blog-hero__glow blog-hero__glow--1"></div>
                <div class="blog-hero__glow blog-hero__glow--2"></div>
                <div class="blog-hero__grid"></div>
            </div>
            <div class="container blog-hero__layout">
                <div class="blog-hero__copy">
                <p class="blog-eyebrow">Blog ProspectAds</p>
                <h1 class="blog-hero__title">Conteúdo para quem já vende — e quer <span class="blog-hero__accent">escalar com lucro</span></h1>
                <p class="blog-hero__subtitle">
                    Guias sobre tráfego, ROAS, conversão e operação para aplicar no seu e-commerce. Quando quiser ir além da leitura, nossa equipe faz a análise comercial da operação e aponta o que priorizar para crescer.
                </p>
                    <?php blog_render_hero_topics($heroTopics); ?>
                </div>
                <?php if ($postCount > 0): ?>
                <aside class="blog-hero__aside" aria-label="Resumo do blog">
                    <div class="blog-hero__stat-card">
                        <span class="blog-hero__stat-card__value">100%</span>
                        <span class="blog-hero__stat-card__label">Foco em e-commerce</span>
                    </div>
                    <div class="blog-hero__stat-card blog-hero__stat-card--highlight">
                        <span class="blog-hero__stat-card__icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M23 6l-9.5 9.5-5-5L1 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 6h6v6" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <span class="blog-hero__stat-card__label">Conteúdo aplicável na operação do seu e-commerce</span>
                    </div>
                </aside>
                <?php endif; ?>
            </div>
        </section>

        <section class="blog-page__content">
            <div class="container">
                <?php if (empty($posts)): ?>
                    <div class="blog-empty">
                        <p>Em breve, novos artigos sobre tráfego e conversão para o seu e-commerce.</p>
                    </div>
                <?php else: ?>
                    <?php if ($featured): ?>
                        <p class="blog-section-label">Em destaque</p>
                        <a href="<?= htmlspecialchars(blog_post_url((string) $featured['slug'])) ?>" class="blog-featured">
                            <?php blog_card_visual($featured, 'featured'); ?>
                            <div class="blog-featured__body">
                                <?php if (!empty($featured['category_name'])): ?>
                                    <span class="blog-featured__badge"><?= htmlspecialchars((string) $featured['category_name']) ?></span>
                                <?php endif; ?>
                                <h2 class="blog-featured__title"><?= htmlspecialchars((string) $featured['title']) ?></h2>
                                <?php if (!empty($featured['excerpt'])): ?>
                                    <p class="blog-featured__excerpt"><?= htmlspecialchars((string) $featured['excerpt']) ?></p>
                                <?php endif; ?>
                                <span class="blog-featured__cta">
                                    Ler artigo completo
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </div>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($gridPosts)): ?>
                        <p class="blog-section-label">Todos os artigos</p>
                        <div class="blog-grid">
                            <?php foreach ($gridPosts as $post): ?>
                                <a href="<?= htmlspecialchars(blog_post_url((string) $post['slug'])) ?>" class="blog-card">
                                    <?php blog_card_visual($post, 'card'); ?>
                                    <div class="blog-card__body">
                                        <?php blog_meta_row($post['category_name'] ?? null, $post['category_slug'] ?? null, false); ?>
                                        <h2 class="blog-card__title"><?= htmlspecialchars((string) $post['title']) ?></h2>
                                        <?php if (!empty($post['excerpt'])): ?>
                                            <p class="blog-card__excerpt"><?= htmlspecialchars((string) $post['excerpt']) ?></p>
                                        <?php endif; ?>
                                        <div class="blog-card__footer">
                                            <span class="blog-card__read">Ler artigo →</span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <aside class="blog-inline-cta">
                        <div class="blog-inline-cta__copy">
                            <h2>Quer aplicar isso no seu e-commerce?</h2>
                            <p>Fazemos o diagnóstico da sua operação — anúncios, site e oferta — e mostramos o próximo passo para vender mais.</p>
                        </div>
                        <div class="blog-inline-cta__actions">
                            <a href="/ecommerce-analise/" class="btn btn--large btn--primary">Solicitar análise gratuita</a>
                        </div>
                    </aside>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php blog_footer(); ?>
</body>
</html>
