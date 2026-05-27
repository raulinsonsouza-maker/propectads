<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var string $activeNav leads|posts|categories
 * @var string $content
 * @var string|null $pageSubtitle
 */

if (!isset($pageTitle, $activeNav, $content)) {
    throw new RuntimeException('layout.php requer $pageTitle, $activeNav e $content');
}

$pageSubtitle = $pageSubtitle ?? '';

$navItems = [
    'leads' => ['label' => 'Leads', 'href' => admin_url('leads.php')],
    'posts' => ['label' => 'Posts', 'href' => admin_url('blog/posts.php')],
    'categories' => ['label' => 'Categorias', 'href' => admin_url('blog/categories.php')],
];

$siteRoot = admin_in_blog_subdir() ? '../../' : '../';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> | ProspectAds</title>
    <?php require dirname(__DIR__, 2) . '/includes/site-favicon.php'; site_render_favicon(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(admin_url('admin.css')) ?>">
</head>
<body class="admin-body">
    <div class="admin-bg" aria-hidden="true"></div>

    <header class="admin-header">
        <div class="admin-header__inner">
            <?php require __DIR__ . '/brand-logo.php'; ?>
            <nav class="admin-nav" aria-label="Menu do painel">
                <?php foreach ($navItems as $key => $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="admin-nav__link <?= $activeNav === $key ? 'is-active' : '' ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="admin-header__actions">
                <a href="<?= htmlspecialchars($siteRoot ?: '/') ?>" class="admin-header__site" target="_blank" rel="noopener">Ver site ↗</a>
                <a href="<?= htmlspecialchars(admin_url('logout.php')) ?>" class="admin-header__logout">Sair</a>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-container">
            <header class="admin-page-head">
                <div>
                    <p class="admin-page-head__eyebrow">Painel ProspectAds</p>
                    <h1 class="admin-page-head__title"><?= htmlspecialchars($pageTitle) ?></h1>
                    <?php if ($pageSubtitle !== ''): ?>
                        <p class="admin-page-head__subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                    <?php endif; ?>
                </div>
            </header>
            <?= $content ?>
        </div>
    </main>

    <footer class="admin-footer">
        <div class="admin-container admin-footer__inner">
            <span>&copy; <?= date('Y') ?> ProspectAds</span>
            <a href="<?= htmlspecialchars($siteRoot ?: '/') ?>" target="_blank" rel="noopener">prospectads.com.br</a>
        </div>
    </footer>
</body>
</html>
