<?php

declare(strict_types=1);

/**
 * Marca no header: somente texto "ProspectAds".
 *
 * @param string $href URL do link (ex.: /, ../, #)
 */
function site_brand_link(string $href = '/'): void
{
    ?>
    <a href="<?= htmlspecialchars($href) ?>" class="site-brand">
        <span class="site-brand__name">ProspectAds</span>
    </a>
    <?php
}
