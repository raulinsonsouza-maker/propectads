<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/api/bootstrap.php';

require_admin();

$pdo = get_pdo();
ensure_schema($pdo);

$id = (int) ($_GET['id'] ?? 0);
$post = null;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $post = $stmt->fetch();
    if (!$post) {
        header('Location: posts.php');
        exit;
    }
}

$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();

$defaults = [
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'content_html' => '',
    'status' => 'draft',
    'category_id' => '',
    'featured_image' => '',
    'meta_title' => '',
    'meta_description' => '',
];

$data = $post ? array_merge($defaults, $post) : $defaults;
$error = match ((string) ($_GET['error'] ?? '')) {
    'title' => 'Informe o titulo do post.',
    default => '',
};

ob_start();
?>
<div class="admin-toolbar">
    <a href="posts.php">&larr; Voltar à lista</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($id && ($data['status'] ?? '') === 'trash'): ?>
<form method="post" action="post-delete.php" class="purge-form" onsubmit="return confirm('Excluir permanentemente?');">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="action" value="purge">
    <button type="submit" class="btn-link-danger">Excluir permanentemente</button>
</form>
<?php endif; ?>

<form method="post" action="post-save.php" class="post-editor-form" id="post-form">
    <?= csrf_field() ?>
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <div class="post-editor-grid">
        <div class="post-editor-main admin-panel">
            <label>Titulo</label>
            <input type="text" name="title" id="post-title" required maxlength="200" value="<?= htmlspecialchars((string) $data['title']) ?>">

            <label>Slug</label>
            <input type="text" name="slug" id="post-slug" maxlength="200" value="<?= htmlspecialchars((string) $data['slug']) ?>" placeholder="gerado do titulo">

            <label>Resumo (excerpt)</label>
            <textarea name="excerpt" rows="3" maxlength="500"><?= htmlspecialchars((string) $data['excerpt']) ?></textarea>

            <label>Conteudo</label>
            <textarea name="content_html" id="content-html" rows="16"><?= htmlspecialchars((string) $data['content_html']) ?></textarea>
        </div>

        <aside class="post-editor-sidebar">
            <div class="admin-panel">
                <h3>Publicar</h3>
                <label>Status</label>
                <select name="status" id="post-status">
                    <?php foreach (BLOG_POST_STATUSES as $st): ?>
                        <?php if ($st === 'trash' && ($data['status'] ?? '') !== 'trash') {
                            continue;
                        } ?>
                        <option value="<?= $st ?>" <?= ($data['status'] ?? '') === $st ? 'selected' : '' ?>><?= post_status_label($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="post-editor-actions">
                    <button type="submit" class="btn-secondary" id="btn-draft">Salvar rascunho</button>
                    <button type="submit" class="btn-primary" id="btn-publish">Publicar</button>
                </div>
            </div>

            <?php if ($id > 0): ?>
            <div class="admin-panel">
                <h3>Visualizações</h3>
                <p class="admin-stat"><?= htmlspecialchars(blog_format_view_count((int) ($data['view_count'] ?? 0))) ?></p>
                <p class="admin-hint">Contagem interna (1 visita por navegador a cada 24h).</p>
            </div>
            <?php endif; ?>

            <div class="admin-panel">
                <h3>Categoria</h3>
                <select name="category_id">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($data['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="admin-panel">
                <h3>Imagem destacada</h3>
                <input type="hidden" name="featured_image" id="featured-image-url" value="<?= htmlspecialchars((string) $data['featured_image']) ?>">
                <div id="featured-preview" class="featured-preview">
                    <?php if (!empty($data['featured_image'])): ?>
                        <img src="<?= htmlspecialchars((string) $data['featured_image']) ?>" alt="<?= htmlspecialchars(blog_featured_image_alt($data)) ?>">
                    <?php endif; ?>
                </div>
                <input type="file" id="featured-upload" accept="image/jpeg,image/png,image/webp">
                <p class="admin-hint">O arquivo será salvo com nome SEO (slug do artigo + imagem-destaque) para indexação no Google Imagens.</p>
                <label class="tag-check"><input type="checkbox" name="remove_featured_image" value="1"> Remover imagem</label>
            </div>

            <div class="admin-panel">
                <h3>SEO</h3>
                <label>Meta title</label>
                <input type="text" name="meta_title" maxlength="70" value="<?= htmlspecialchars((string) $data['meta_title']) ?>">
                <label>Meta description</label>
                <textarea name="meta_description" rows="3" maxlength="160"><?= htmlspecialchars((string) $data['meta_description']) ?></textarea>
            </div>
        </aside>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
(function () {
    const uploadUrl = <?= json_encode(api_url('api/blog-upload.php')) ?>;
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    const statusEl = document.getElementById('post-status');

    document.getElementById('btn-draft').addEventListener('click', () => { statusEl.value = 'draft'; });
    document.getElementById('btn-publish').addEventListener('click', () => { statusEl.value = 'published'; });

    tinymce.init({
        selector: '#content-html',
        height: 420,
        menubar: false,
        plugins: 'lists link',
        toolbar: 'undo redo | blocks | bold | bullist numlist | link | removeformat',
        block_formats: 'Paragraph=p;Heading 2=h2;Heading 3=h3',
        license_key: 'gpl',
    });

    const titleEl = document.getElementById('post-title');
    const slugEl = document.getElementById('post-slug');
    let slugTouched = slugEl.value.length > 0;
    slugEl.addEventListener('input', () => { slugTouched = true; });
    titleEl.addEventListener('blur', () => {
        if (!slugTouched && titleEl.value) {
            slugEl.value = titleEl.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        }
    });

    document.getElementById('post-form').addEventListener('submit', () => {
        tinymce.triggerSave();
    });

    const fileInput = document.getElementById('featured-upload');
    const urlInput = document.getElementById('featured-image-url');
    const preview = document.getElementById('featured-preview');

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;

        const slug = slugEl.value.trim() || titleEl.value.trim();
        if (!slug) {
            alert('Preencha o título (ou o slug) antes de enviar a imagem, para gerar um nome amigável ao SEO.');
            fileInput.value = '';
            return;
        }

        const fd = new FormData();
        fd.append('image', file);
        fd.append('csrf_token', csrfToken);
        fd.append('slug', slugEl.value.trim());
        fd.append('title', titleEl.value.trim());
        fd.append('context', 'destaque');

        try {
            const res = await fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            const raw = await res.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (parseErr) {
                console.error('Upload response:', res.status, raw);
                let hint = 'Resposta inválida do servidor (HTTP ' + res.status + ').';
                if (res.status === 413) {
                    hint += '\n\nArquivo grande demais: no Nginx, use client_max_body_size 10M; no PHP, upload_max_filesize e post_max_size.';
                } else if (raw.trim()) {
                    hint += '\n\n' + raw.trim().slice(0, 400);
                } else {
                    hint += '\n\nVerifique: pasta uploads/blog com permissão para www-data; PHP ativo em /api/blog-upload.php; logs do Apache.';
                }
                alert(hint);
                fileInput.value = '';
                return;
            }
            if (data.success && data.url) {
                urlInput.value = data.url;
                const alt = data.alt || titleEl.value || 'Imagem destacada';
                preview.innerHTML = '<img src="' + data.url + '" alt="' + alt.replace(/"/g, '&quot;') + '">';
                const rm = document.querySelector('[name=remove_featured_image]');
                if (rm) rm.checked = false;
                if (data.filename) {
                    console.info('Imagem salva como:', data.filename);
                }
            } else {
                alert(data.error || 'Falha no upload.');
            }
        } catch (e) {
            console.error(e);
            alert('Erro ao enviar imagem. Confirme que está logado e que o servidor PHP está ativo.');
        }
        fileInput.value = '';
    });
})();
</script>
<?php
$content = ob_get_clean();

$pageTitle = $id ? 'Editar post' : 'Novo post';
$pageSubtitle = $id ? 'Atualize conteúdo, SEO e publicação' : 'Crie um novo artigo para o blog';
$activeNav = 'posts';
require dirname(__DIR__) . '/includes/layout.php';
