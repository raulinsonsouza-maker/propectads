<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

start_admin_session();
if (empty($_SESSION['admin_logged_in'])) {
    json_response(['success' => false, 'error' => 'Sessão expirada. Faça login novamente e tente enviar a imagem.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Método não permitido.'], 405);
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    json_response(['success' => false, 'error' => 'Sessão expirada. Recarregue a página e tente de novo.'], 403);
}

if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'] ?? '')) {
    json_response(['success' => false, 'error' => 'Nenhuma imagem enviada.'], 400);
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'error' => blog_upload_error_message((int) $file['error'])], 400);
}

if ((int) $file['size'] > blog_upload_max_bytes()) {
    $mb = (int) round(blog_upload_max_bytes() / 1024 / 1024);
    json_response(['success' => false, 'error' => 'Arquivo muito grande (máx. ' . $mb . ' MB).'], 400);
}

$mime = detect_upload_image_mime($file['tmp_name']);
if ($mime === null) {
    json_response(['success' => false, 'error' => 'Não foi possível ler o arquivo. Envie JPEG, PNG ou WebP válido.'], 400);
}

$ext = blog_extension_from_mime($mime, (string) ($file['name'] ?? ''));
if ($ext === null) {
    json_response(['success' => false, 'error' => 'Formato não permitido. Use JPEG, PNG ou WebP.'], 400);
}

$slugInput = sanitize_string((string) ($_POST['slug'] ?? ''), 200);
$titleInput = sanitize_string((string) ($_POST['title'] ?? ''), 200);
$context = sanitize_string((string) ($_POST['context'] ?? 'destaque'), 40);
$articleKey = $slugInput !== '' ? $slugInput : ($titleInput !== '' ? $titleInput : 'artigo-blog');

$basename = blog_seo_image_basename($articleKey, $context);
try {
    $dir = blog_upload_dir();
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'error' => 'Não foi possível criar a pasta uploads/blog no servidor.',
    ], 500);
}

if (!is_writable($dir)) {
    @chmod($dir, 0775);
    clearstatcache(true, $dir);
    if (!is_writable($dir)) {
        json_response([
            'success' => false,
            'error' => 'Pasta uploads/blog sem permissão de escrita. Ajuste permissões no servidor.',
        ], 500);
    }
}

$name = blog_unique_upload_filename($dir, $basename, $ext);
$dest = $dir . '/' . $name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['success' => false, 'error' => 'Não foi possível salvar o arquivo em uploads/blog.'], 500);
}

$url = '/uploads/blog/' . $name;
$alt = $titleInput !== '' ? $titleInput . ' — ProspectAds' : 'Imagem destacada — ProspectAds';

json_response([
    'success' => true,
    'url' => $url,
    'filename' => $name,
    'alt' => $alt,
]);
