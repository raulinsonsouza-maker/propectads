<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: leads.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$notas = sanitize_string((string) ($_POST['notas'] ?? ''), 2000);

if ($id <= 0 || !in_array($status, STATUS_OPTIONS, true)) {
    header('Location: leads.php?error=1');
    exit;
}

try {
    $pdo = get_pdo();
    ensure_schema($pdo);
    $stmt = $pdo->prepare('UPDATE leads SET status = :status, notas = :notas WHERE id = :id');
    $stmt->execute([
        ':status' => $status,
        ':notas' => $notas !== '' ? $notas : null,
        ':id' => $id,
    ]);
} catch (Throwable $e) {
    header('Location: leads.php?error=1');
    exit;
}

header('Location: leads.php?updated=1');
exit;
