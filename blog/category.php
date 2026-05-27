<?php



declare(strict_types=1);



require dirname(__DIR__) . '/api/bootstrap.php';

require __DIR__ . '/includes/partials.php';

require __DIR__ . '/includes/seo.php';



$slug = blog_resolve_category_slug();

if ($slug === '') {
    http_response_code(404);
    echo 'Categoria não encontrada.';
    exit;
}

$pdo = get_pdo();

ensure_schema($pdo);



$category = get_blog_category_by_slug($pdo, $slug);

if (!$category) {

    http_response_code(404);

    echo 'Categoria não encontrada.';

    exit;

}



$stmt = $pdo->prepare(

    "SELECT p.*, c.name AS category_name, c.slug AS category_slug

     FROM blog_posts p

     LEFT JOIN blog_categories c ON c.id = p.category_id

     WHERE p.status = 'published' AND p.category_id = :cat

     ORDER BY p.published_at DESC"

);

$stmt->execute([':cat' => (int) $category['id']]);

$posts = $stmt->fetchAll();



$heroTopics = blog_resolve_hero_topics($pdo);

$postCount = count($posts);

$catName = (string) $category['name'];

$catDesc = trim((string) ($category['description'] ?? ''));



$canonical = site_base_url() . blog_category_path($slug);

$metaTitle = $catName . ' | Blog E-commerce — ProspectAds';

$metaDesc = $catDesc !== ''

    ? meta_excerpt($catDesc, 160)

    : 'Artigos sobre ' . mb_strtolower($catName, 'UTF-8') . ' para donos de e-commerce que querem escalar com lucro.';

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

<body class="blog-index blog-category">

    <header class="header">

        <nav class="nav container">

            <div class="nav__logo">

                <?php site_brand_link('/'); ?>

            </div>

            <ul class="nav__menu" id="nav-menu">

                <li><a href="/#servicos">Serviços</a></li>

                <li><a href="/blog/">Blog</a></li>

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

                    <nav class="blog-breadcrumb" aria-label="Navegação">

                        <a href="/">Início</a>

                        <span class="blog-breadcrumb__sep">/</span>

                        <a href="/blog/">Blog</a>

                        <span class="blog-breadcrumb__sep">/</span>

                        <span class="blog-breadcrumb__current"><?= htmlspecialchars($catName) ?></span>

                    </nav>

                    <p class="blog-eyebrow">Categoria</p>

                    <h1 class="blog-hero__title"><?= htmlspecialchars($catName) ?></h1>

                    <?php if ($catDesc !== ''): ?>

                        <p class="blog-hero__subtitle"><?= htmlspecialchars($catDesc) ?></p>

                    <?php else: ?>

                        <p class="blog-hero__subtitle">

                            <?= $postCount > 0

                                ? $postCount . ($postCount === 1 ? ' artigo' : ' artigos') . ' publicados neste tema.'

                                : 'Em breve, novos artigos neste tema.' ?>

                        </p>

                    <?php endif; ?>

                    <?php blog_render_hero_topics($heroTopics, $slug); ?>

                    <a href="/blog/" class="btn btn--secondary blog-hero__back">Ver todos os artigos</a>

                </div>

            </div>

        </section>



        <section class="blog-page__content">

            <div class="container">

                <?php if (empty($posts)): ?>

                    <div class="blog-empty">

                        <p>Nenhum artigo publicado nesta categoria ainda.</p>

                        <p><a href="/blog/">Voltar ao blog</a></p>

                    </div>

                <?php else: ?>

                    <p class="blog-section-label">Artigos em <?= htmlspecialchars($catName) ?></p>

                    <div class="blog-grid">

                        <?php foreach ($posts as $post): ?>

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

