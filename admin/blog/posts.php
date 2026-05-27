<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/api/bootstrap.php';

require_admin();

$pdo = get_pdo();
ensure_schema($pdo);

$statusFilter = (string) ($_GET['status'] ?? '');
$search = sanitize_string((string) ($_GET['q'] ?? ''), 100);

$sql = 'SELECT p.*, c.name AS category_name FROM blog_posts p
        LEFT JOIN blog_categories c ON c.id = p.category_id WHERE 1=1';
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, BLOG_POST_STATUSES, true)) {
    $sql .= ' AND p.status = :status';
    $params[':status'] = $statusFilter;
} else {
    $sql .= ' AND p.status != :trash';
    $params[':trash'] = 'trash';
}

if ($search !== '') {
    $sql .= ' AND p.title LIKE :q';
    $params[':q'] = '%' . $search . '%';
}

$sql .= ' ORDER BY COALESCE(p.published_at, p.updated_at) DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$flash = (string) ($_GET['msg'] ?? '');

ob_start();
?>
<div class="admin-toolbar">
    <a href="post-edit.php" class="btn-primary-link">+ Adicionar novo post</a>
</div>

<?php if ($flash === 'saved'): ?>
    <div class="alert alert-success">Post salvo.</div>
<?php elseif ($flash === 'deleted'): ?>
    <div class="alert alert-success">Post movido para a lixeira.</div>
<?php elseif ($flash === 'purged'): ?>
    <div class="alert alert-success">Post excluído permanentemente.</div>
<?php endif; ?>

<form class="filters" method="get">
    <input type="search" name="q" placeholder="Buscar por título" value="<?= htmlspecialchars($search) ?>">
    <select name="status">
        <option value="">Publicados e rascunhos</option>
        <?php foreach (BLOG_POST_STATUSES as $st): ?>
            <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= post_status_label($st) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
        <a href="posts.php" class="filter-clear">Limpar</a>
    <?php endif; ?>
</form>

<?php if (empty($posts)): ?>
    <p class="empty-state">Nenhum post encontrado.</p>
<?php else: ?>
    <table class="leads-table posts-table">
        <thead>
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Status</th>
                <th>Visualizações</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($post['title']) ?></strong>
                        <br><small>/blog/<?= htmlspecialchars($post['slug']) ?>/</small>
                    </td>
                    <td><?= htmlspecialchars($post['category_name'] ?? '—') ?></td>
                    <td>
                        <span class="status-badge status-post-<?= htmlspecialchars($post['status']) ?>">
                            <?= post_status_label($post['status']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars(blog_format_view_count((int) ($post['view_count'] ?? 0))) ?></td>
                    <td>
                        <?php
                        $date = $post['published_at'] ?? $post['updated_at'];
                        echo htmlspecialchars(date('d/m/Y H:i', strtotime((string) $date)));
                        ?>
                    </td>
                    <td class="post-actions">
                        <a href="post-edit.php?id=<?= (int) $post['id'] ?>">Editar</a>
                        <?php if ($post['status'] === 'published'): ?>
                            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" target="_blank" rel="noopener">Ver</a>
                        <?php endif; ?>
                        <form method="post" action="post-delete.php" class="inline-form" onsubmit="return confirm('Mover para lixeira?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                            <input type="hidden" name="action" value="trash">
                            <button type="submit" class="btn-link-danger">Lixeira</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Posts';
$pageSubtitle = 'Gerencie artigos do blog público';
$activeNav = 'posts';
require dirname(__DIR__) . '/includes/layout.php';
