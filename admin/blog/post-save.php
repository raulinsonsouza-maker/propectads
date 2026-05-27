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
$title = sanitize_string((string) ($_POST['title'] ?? ''), 200);
$slugInput = sanitize_string((string) ($_POST['slug'] ?? ''), 200);
$excerpt = sanitize_string((string) ($_POST['excerpt'] ?? ''), 500);
$contentHtml = sanitize_post_html((string) ($_POST['content_html'] ?? ''));
$status = (string) ($_POST['status'] ?? 'draft');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$metaTitle = sanitize_string((string) ($_POST['meta_title'] ?? ''), 70);
$metaDescription = sanitize_string((string) ($_POST['meta_description'] ?? ''), 160);
$featuredImage = sanitize_string((string) ($_POST['featured_image'] ?? ''), 255);
$removeImage = !empty($_POST['remove_featured_image']);
if ($title === '') {
    header('Location: post-edit.php?' . ($id ? 'id=' . $id . '&' : '') . 'error=title');
    exit;
}

if (!in_array($status, BLOG_POST_STATUSES, true)) {
    $status = 'draft';
}

$slug = $slugInput !== '' ? slugify($slugInput) : slugify($title);
$slug = unique_post_slug($pdo, $slug, $id > 0 ? $id : null);

$now = gmdate('c');
$readingTime = estimate_reading_time($contentHtml);
$categoryId = $categoryId > 0 ? $categoryId : null;

if ($removeImage) {
    $featuredImage = '';
}

$publishedAt = null;
if ($status === 'published') {
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT published_at, status FROM blog_posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();
        if ($existing && $existing['status'] === 'published' && !empty($existing['published_at'])) {
            $publishedAt = $existing['published_at'];
        } else {
            $publishedAt = $now;
        }
    } else {
        $publishedAt = $now;
    }
}

if ($id > 0) {
    $stmt = $pdo->prepare(
        'UPDATE blog_posts SET title = :title, slug = :slug, excerpt = :excerpt, content_html = :content,
         status = :status, category_id = :cat, featured_image = :img, meta_title = :mt, meta_description = :md,
         reading_time_min = :rt, published_at = :pub, updated_at = :upd WHERE id = :id'
    );
    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':excerpt' => $excerpt,
        ':content' => $contentHtml,
        ':status' => $status,
        ':cat' => $categoryId,
        ':img' => $featuredImage !== '' ? $featuredImage : null,
        ':mt' => $metaTitle !== '' ? $metaTitle : null,
        ':md' => $metaDescription !== '' ? $metaDescription : null,
        ':rt' => $readingTime,
        ':pub' => $publishedAt,
        ':upd' => $now,
        ':id' => $id,
    ]);
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO blog_posts (title, slug, excerpt, content_html, status, category_id, featured_image,
         meta_title, meta_description, reading_time_min, published_at, created_at, updated_at)
         VALUES (:title, :slug, :excerpt, :content, :status, :cat, :img, :mt, :md, :rt, :pub, :c, :upd)'
    );
    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':excerpt' => $excerpt,
        ':content' => $contentHtml,
        ':status' => $status,
        ':cat' => $categoryId,
        ':img' => $featuredImage !== '' ? $featuredImage : null,
        ':mt' => $metaTitle !== '' ? $metaTitle : null,
        ':md' => $metaDescription !== '' ? $metaDescription : null,
        ':rt' => $readingTime,
        ':pub' => $publishedAt,
        ':c' => $now,
        ':upd' => $now,
    ]);
    $id = (int) $pdo->lastInsertId();
}

header('Location: posts.php?msg=saved');
exit;
