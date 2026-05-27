<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';

header('Location: ' . site_base_url() . '/sitemap.php', true, 301);
exit;
