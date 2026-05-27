<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/api/bootstrap.php';

require_admin();

$pdo = get_pdo();
ensure_schema($pdo);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create') {
            $name = sanitize_string((string) ($_POST['name'] ?? ''), 120);
            $slug = sanitize_string((string) ($_POST['slug'] ?? ''), 120);
            $description = sanitize_string((string) ($_POST['description'] ?? ''), 500);
            if ($name === '') {
                $error = 'Nome obrigatório.';
            } else {
                if ($slug === '') {
                    $slug = slugify($name);
                } else {
                    $slug = slugify($slug);
                }
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO blog_categories (name, slug, description, created_at) VALUES (:n, :s, :d, :c)'
                    );
                    $stmt->execute([
                        ':n' => $name,
                        ':s' => $slug,
                        ':d' => $description !== '' ? $description : null,
                        ':c' => gmdate('c'),
                    ]);
                    $message = 'Categoria criada.';
                } catch (PDOException $e) {
                    $error = str_contains($e->getMessage(), 'UNIQUE') ? 'Slug já existe.' : 'Erro ao salvar.';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE blog_posts SET category_id = NULL WHERE category_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM blog_categories WHERE id = :id')->execute([':id' => $id]);
            $message = 'Categoria removida.';
        }
    }
}

$categories = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM blog_posts p WHERE p.category_id = c.id) AS post_count FROM blog_categories c ORDER BY name')->fetchAll();
$totalPostsInCategories = array_sum(array_map(static fn(array $c): int => (int) $c['post_count'], $categories));

ob_start();
?>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="admin-two-col">
    <section class="admin-panel">
        <h3>Nova categoria</h3>
        <form method="post" class="admin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label>Nome</label>
            <input type="text" name="name" required maxlength="120">
            <label>Slug (opcional)</label>
            <input type="text" name="slug" maxlength="120" placeholder="gerado automaticamente">
            <label>Descrição</label>
            <textarea name="description" rows="3" maxlength="500"></textarea>
            <button type="submit">Adicionar</button>
        </form>
    </section>
    <section class="admin-panel admin-panel--wide">
        <h3>Lista</h3>
        <?php if (empty($categories)): ?>
            <p class="empty-state">Nenhuma categoria.</p>
        <?php else: ?>
            <table class="leads-table">
                <thead>
                    <tr><th>Nome</th><th>Slug</th><th>Posts</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                            <td><?= (int) $cat['post_count'] ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Remover categoria?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                    <button type="submit" class="btn-secondary btn-sm">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="leads-table__total">
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong><?= $totalPostsInCategories ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </section>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Categorias';
$pageSubtitle = 'Organize os posts do blog por tema';
$activeNav = 'categories';
require dirname(__DIR__) . '/includes/layout.php';
