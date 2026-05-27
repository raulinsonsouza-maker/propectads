<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/api/bootstrap.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? null)) {
    header('Location: posts.php');
    exit;
}

$pdo = get_pdo();
ensure_schema($pdo);

$id = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? 'trash');

if ($id <= 0) {
    header('Location: posts.php');
    exit;
}

if ($action === 'purge') {
    $pdo->prepare('DELETE FROM blog_posts WHERE id = :id')->execute([':id' => $id]);
    header('Location: posts.php?status=trash&msg=purged');
    exit;
}

$pdo->prepare('UPDATE blog_posts SET status = :s, updated_at = :u WHERE id = :id')->execute([
    ':s' => 'trash',
    ':u' => gmdate('c'),
    ':id' => $id,
]);

header('Location: posts.php?msg=deleted');
exit;
