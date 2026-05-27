<?php

declare(strict_types=1);

function site_favicon_href(): string
{
    return '/favicon.svg?v=2';
}

function site_render_favicon(): void
{
    $href = site_favicon_href();
    ?>
    <link rel="icon" href="<?= htmlspecialchars($href) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($href) ?>">
    <?php
}
