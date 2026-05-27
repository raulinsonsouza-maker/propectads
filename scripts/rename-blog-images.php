<?php

declare(strict_types=1);

/**
 * Renomeia imagens do blog para nomes SEO (slug + contexto).
 *
 * Uso:
 *   php scripts/rename-blog-images.php           # aplica alterações
 *   php scripts/rename-blog-images.php --dry-run # só mostra o que faria
 */

require dirname(__DIR__) . '/api/bootstrap.php';

$dryRun = in_array('--dry-run', $argv, true) || in_array('-n', $argv, true);
$uploadDir = blog_upload_dir();

$pdo = get_pdo();
ensure_schema($pdo);

$stmt = $pdo->query('SELECT id, title, slug, featured_image FROM blog_posts WHERE featured_image IS NOT NULL AND featured_image != ""');
$posts = $stmt->fetchAll();

$renamed = 0;
$skipped = 0;
$errors = 0;

echo $dryRun ? "Modo simulação (--dry-run)\n\n" : "Renomeando imagens do blog…\n\n";

foreach ($posts as $post) {
    $url = (string) $post['featured_image'];
    if (!preg_match('#^/uploads/blog/([^/]+)$#', $url, $m)) {
        echo "[skip] Post #{$post['id']}: URL fora de uploads/blog — {$url}\n";
        $skipped++;
        continue;
    }

    $oldName = $m[1];
    $oldPath = $uploadDir . '/' . $oldName;

    if (!is_file($oldPath)) {
        echo "[erro] Post #{$post['id']}: arquivo não encontrado — {$oldName}\n";
        $errors++;
        continue;
    }

    $ext = strtolower(pathinfo($oldName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        echo "[skip] Post #{$post['id']}: extensão não suportada — {$oldName}\n";
        $skipped++;
        continue;
    }
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }

    $basename = blog_seo_image_basename((string) $post['slug'], 'destaque');
    $newName = blog_unique_upload_filename($uploadDir, $basename, $ext);

    if ($newName === $oldName) {
        echo "[ok] Post #{$post['id']}: já está com nome SEO — {$oldName}\n";
        $skipped++;
        continue;
    }

  // Evita renomear se o destino já é outro post
    $newPath = $uploadDir . '/' . $newName;
    $newUrl = '/uploads/blog/' . $newName;

    echo "[{$post['slug']}] {$oldName} → {$newName}\n";

    if ($dryRun) {
        $renamed++;
        continue;
    }

    if (!rename($oldPath, $newPath)) {
        echo "  ERRO ao renomear arquivo.\n";
        $errors++;
        continue;
    }

    $upd = $pdo->prepare('UPDATE blog_posts SET featured_image = :url WHERE id = :id');
    $upd->execute([':url' => $newUrl, ':id' => (int) $post['id']]);
    $renamed++;
}

echo "\nConcluído: {$renamed} renomeado(s), {$skipped} ignorado(s), {$errors} erro(s).\n";

if ($dryRun && $renamed > 0) {
    echo "Execute sem --dry-run para aplicar.\n";
}
