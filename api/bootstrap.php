<?php

declare(strict_types=1);

function load_config(): array
{
    $configPath = __DIR__ . '/config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Arquivo api/config.php não encontrado. Copie config.example.php.');
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('config.php deve retornar um array.');
    }

    return $config;
}

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = load_config();
    $dbPath = $config['db_path'] ?? dirname(__DIR__) . '/data/leads.sqlite';
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            whatsapp TEXT NOT NULL,
            loja TEXT NOT NULL,
            investimento TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "novo",
            notas TEXT,
            ip TEXT,
            user_agent TEXT,
            created_at TEXT NOT NULL
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at DESC)');

    ensure_blog_schema($pdo);
}

function ensure_blog_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blog_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            created_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blog_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            excerpt TEXT,
            content_html TEXT NOT NULL DEFAULT "",
            status TEXT NOT NULL DEFAULT "draft",
            category_id INTEGER,
            featured_image TEXT,
            meta_title TEXT,
            meta_description TEXT,
            reading_time_min INTEGER DEFAULT 1,
            published_at TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_blog_posts_status ON blog_posts(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_blog_posts_slug ON blog_posts(slug)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_blog_posts_published ON blog_posts(published_at DESC)');

    $columns = $pdo->query('PRAGMA table_info(blog_posts)')->fetchAll();
    $hasViewCount = false;
    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'view_count') {
            $hasViewCount = true;
            break;
        }
    }
    if (!$hasViewCount) {
        $pdo->exec('ALTER TABLE blog_posts ADD COLUMN view_count INTEGER NOT NULL DEFAULT 0');
    }
}

const BLOG_POST_STATUSES = ['draft', 'published', 'trash'];

function blog_is_probable_bot(): bool
{
    $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '') {
        return true;
    }

    $patterns = [
        'bot', 'crawl', 'spider', 'slurp', 'preview', 'headless',
        'facebookexternalhit', 'whatsapp', 'telegrambot', 'linkedinbot',
        'google-inspectiontool', 'petalbot', 'bytespider', 'gptbot',
    ];
    foreach ($patterns as $pattern) {
        if (str_contains($ua, $pattern)) {
            return true;
        }
    }

    return false;
}

function blog_format_view_count(int $count): string
{
    return number_format(max(0, $count), 0, ',', '.');
}

function blog_record_post_view(PDO $pdo, int $postId): void
{
    if ($postId <= 0 || blog_is_probable_bot()) {
        return;
    }

    $cookieName = 'pa_bv_' . $postId;
    if (isset($_COOKIE[$cookieName])) {
        return;
    }

    $stmt = $pdo->prepare(
        "UPDATE blog_posts SET view_count = view_count + 1
         WHERE id = :id AND status = 'published'"
    );
    $stmt->execute([':id' => $postId]);
    if ($stmt->rowCount() === 0) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie($cookieName, '1', [
        'expires' => time() + 86400,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function admin_in_blog_subdir(): bool
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    return str_contains($script, '/admin/blog/');
}

function admin_url(string $path): string
{
    $path = ltrim($path, '/');
    if (admin_in_blog_subdir()) {
        return '../' . $path;
    }

    return $path;
}

function api_url(string $path): string
{
    $path = ltrim($path, '/');
    if (admin_in_blog_subdir()) {
        return '../../' . $path;
    }

    return '../' . $path;
}

function blog_upload_dir(): string
{
    $dir = dirname(__DIR__) . '/uploads/blog';
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível criar a pasta uploads/blog.');
        }
    }
    clearstatcache(true, $dir);

    return $dir;
}

function blog_upload_max_bytes(): int
{
    $config = load_config();

    return (int) ($config['blog_upload_max_bytes'] ?? 2 * 1024 * 1024);
}

/** Extensões permitidas para upload no blog. */
function blog_allowed_image_extensions(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
}

function detect_upload_image_mime(string $tmpPath): ?string
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }
    }

    $info = @getimagesize($tmpPath);
    if (is_array($info) && !empty($info['mime']) && is_string($info['mime'])) {
        return $info['mime'];
    }

    return null;
}

function blog_extension_from_mime(string $mime, string $originalName = ''): ?string
{
    $allowed = blog_allowed_image_extensions();
    if (isset($allowed[$mime])) {
        return $allowed[$mime];
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    foreach ($allowed as $allowedExt) {
        if ($ext === $allowedExt) {
            return $allowedExt;
        }
    }

    return null;
}

/**
 * Nome de arquivo amigável para SEO (Google Imagens).
 * Ex.: trafego-pago-ecommerce-imagem-destaque.jpg
 */
function blog_seo_image_basename(string $articleSlugOrTitle, string $context = 'destaque'): string
{
    $slug = slugify($articleSlugOrTitle);
    $ctx = slugify($context);
    if ($ctx === '' || $ctx === 'post') {
        $ctx = 'destaque';
    }

    $base = $slug . '-imagem-' . $ctx;
    if (strlen($base) > 96) {
        $base = rtrim(substr($base, 0, 96), '-');
    }

    return $base !== '' ? $base : 'blog-imagem-destaque';
}

function blog_unique_upload_filename(string $dir, string $basename, string $ext): string
{
    $candidate = $basename . '.' . $ext;
    $n = 2;
    while (is_file($dir . '/' . $candidate)) {
        $candidate = $basename . '-' . $n . '.' . $ext;
        $n++;
    }

    return $candidate;
}

function blog_upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o limite do servidor.',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Nenhuma imagem enviada.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária indisponível no servidor.',
        UPLOAD_ERR_CANT_WRITE => 'Servidor não conseguiu gravar o arquivo.',
        UPLOAD_ERR_EXTENSION => 'Extensão bloqueada no PHP.',
        default => 'Falha no upload (código ' . $code . ').',
    };
}

/** Alt text sugerido para imagem destacada (SEO em Google Imagens). */
function blog_featured_image_alt(array $post): string
{
    $title = trim((string) ($post['title'] ?? ''));
    if ($title === '') {
        return 'Imagem do artigo ProspectAds';
    }

    return $title . ' — ProspectAds';
}

/** UTF-8 helpers (funcionam sem extensão mbstring). */
function utf8_strtolower(string $text): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($text, 'UTF-8');
    }

    static $map = [
        'Á' => 'á', 'À' => 'à', 'Ã' => 'ã', 'Â' => 'â', 'Ä' => 'ä',
        'É' => 'é', 'È' => 'è', 'Ê' => 'ê', 'Ë' => 'ë',
        'Í' => 'í', 'Ì' => 'ì', 'Î' => 'î', 'Ï' => 'ï',
        'Ó' => 'ó', 'Ò' => 'ò', 'Õ' => 'õ', 'Ô' => 'ô', 'Ö' => 'ö',
        'Ú' => 'ú', 'Ù' => 'ù', 'Û' => 'û', 'Ü' => 'ü',
        'Ç' => 'ç', 'Ñ' => 'ñ',
    ];

    return strtolower(strtr($text, $map));
}

function utf8_strtoupper(string $text): string
{
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($text, 'UTF-8');
    }

    static $map = [
        'á' => 'Á', 'à' => 'À', 'ã' => 'Ã', 'â' => 'Â', 'ä' => 'Ä',
        'é' => 'É', 'è' => 'È', 'ê' => 'Ê', 'ë' => 'Ë',
        'í' => 'Í', 'ì' => 'Ì', 'î' => 'Î', 'ï' => 'Ï',
        'ó' => 'Ó', 'ò' => 'Ò', 'õ' => 'Õ', 'ô' => 'Ô', 'ö' => 'Ö',
        'ú' => 'Ú', 'ù' => 'Ù', 'û' => 'Û', 'ü' => 'Ü',
        'ç' => 'Ç', 'ñ' => 'Ñ',
    ];

    return strtoupper(strtr($text, $map));
}

function utf8_substr(string $text, int $start, int $length = 1): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, $start, $length, 'UTF-8');
    }

    if (preg_match_all('/\X/u', $text, $matches) && !empty($matches[0])) {
        return implode('', array_slice($matches[0], $start, $length));
    }

    return substr($text, $start, $length);
}

function slugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return 'post';
    }

    $text = utf8_strtolower($text);
    $map = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;
    $text = trim($text, '-');

    return $text !== '' ? $text : 'post';
}

function unique_post_slug(PDO $pdo, string $baseSlug, ?int $excludeId = null): string
{
    $slug = slugify($baseSlug);
    $candidate = $slug;
    $n = 2;

    while (true) {
        $sql = 'SELECT id FROM blog_posts WHERE slug = :slug';
        $params = [':slug' => $candidate];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $n;
        $n++;
    }
}

function post_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Rascunho',
        'published' => 'Publicado',
        'trash' => 'Lixeira',
        default => $status,
    };
}

function estimate_reading_time(string $html): int
{
    $text = strip_tags($html);
    $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    $count = is_array($words) ? count($words) : 0;
    $minutes = (int) max(1, ceil($count / 200));

    return $minutes;
}

function sanitize_post_html(string $html): string
{
    $allowed = '<p><br><h2><h3><ul><ol><li><a><strong><em><blockquote>';
    $html = strip_tags($html, $allowed);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $wrapped = '<?xml encoding="utf-8" ?><div>' . $html . '</div>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    foreach ($dom->getElementsByTagName('a') as $anchor) {
        $href = $anchor->getAttribute('href');
        if ($href === '' || !preg_match('~^(https?:|mailto:|tel:|/|#)~i', $href)) {
            $anchor->removeAttribute('href');
        } else {
            $anchor->setAttribute('rel', 'noopener noreferrer');
            if (preg_match('#^https?://#i', $href)) {
                $anchor->setAttribute('target', '_blank');
            }
        }
    }

    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }

    $inner = '';
    foreach ($root->childNodes as $child) {
        $inner .= $dom->saveHTML($child);
    }

    return trim($inner);
}

function csrf_token(): string
{
    start_admin_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    start_admin_session();

    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function format_blog_date(?string $iso): string
{
    if ($iso === null || $iso === '') {
        return '';
    }
    $ts = strtotime($iso);
    if ($ts === false) {
        return '';
    }
    $months = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];
    $m = (int) date('n', $ts);

    return date('j', $ts) . ' de ' . ($months[$m] ?? '') . ' de ' . date('Y', $ts);
}

function site_base_url(): string
{
    try {
        $config = load_config();
        $url = rtrim((string) ($config['site_url'] ?? 'https://prospectads.com.br'), '/');
        if ($url !== '') {
            return $url;
        }
    } catch (Throwable) {
        // config ausente em CLI
    }

    return 'https://prospectads.com.br';
}

function blog_post_path(string $slug): string
{
    return '/blog/' . rawurlencode($slug) . '/';
}

function blog_index_path(): string
{
    return '/blog/';
}

function blog_category_path(string $slug): string
{
    return '/blog/categoria/' . rawurlencode($slug) . '/';
}

/** @return array<string, mixed>|null */
function get_blog_category_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM blog_categories WHERE slug = :slug');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/** @return list<array{label: string, slug: string}> */
function blog_hero_topic_nav(): array
{
    return [
        ['label' => 'Tráfego pago', 'slug' => 'trafego-midia'],
        ['label' => 'ROAS', 'slug' => 'trafego-midia'],
        ['label' => 'Conversão', 'slug' => 'conversao-vendas'],
        ['label' => 'Operação', 'slug' => 'estrategia-crescimento'],
    ];
}

/** @return list<array{label: string, slug: string}> */
function blog_resolve_hero_topics(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT DISTINCT c.slug
         FROM blog_categories c
         INNER JOIN blog_posts p ON p.category_id = c.id AND p.status = 'published'"
    );
    $available = [];
    while ($row = $stmt->fetch()) {
        $available[(string) $row['slug']] = true;
    }

    $topics = [];
    foreach (blog_hero_topic_nav() as $topic) {
        if (!empty($available[$topic['slug']])) {
            $topics[] = $topic;
        }
    }

    return $topics;
}

function blog_resolve_category_slug(): string
{
    $slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
    $slug = slugify($slug);

    if ($slug === '' || $slug === 'post' || $slug === 'categoria') {
        return '';
    }

    return $slug;
}

function meta_excerpt(string $text, int $max = 160): string
{
    $text = trim(strip_tags($text));
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1, 'UTF-8')) . '…';
    }

    return strlen($text) <= $max ? $text : rtrim(substr($text, 0, $max - 1)) . '…';
}

/** @return list<array<string, mixed>> */
function get_related_blog_posts(PDO $pdo, array $currentPost, int $limit = 4): array
{
    $limit = max(1, min(6, $limit));
    $categoryId = (int) ($currentPost['category_id'] ?? 0);
    $postId = (int) ($currentPost['id'] ?? 0);

    if ($categoryId > 0) {
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p
             LEFT JOIN blog_categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND p.id != :id AND p.category_id = :cat
             ORDER BY p.published_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':cat', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if (count($rows) >= 2) {
            return $rows;
        }
    }

    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM blog_posts p
         LEFT JOIN blog_categories c ON c.id = p.category_id
         WHERE p.status = 'published' AND p.id != :id
         ORDER BY p.published_at DESC
         LIMIT :lim"
    );
    $stmt->bindValue(':id', $postId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Redireciona /blog/slug/ → post.php apenas se o slug não veio na query (rewrite interno).
 * Não redireciona post.php → URL bonita (evita cair na listagem quando rewrite falha).
 */
function blog_resolve_post_slug(): string
{
    $slug = sanitize_string((string) ($_GET['slug'] ?? ''), 200);
    if ($slug !== '') {
        return $slug;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/blog/([a-z0-9][a-z0-9-]*)/?$#', $uri, $m)
        && !in_array($m[1], ['index', 'post', 'sitemap', 'feed', 'categoria', 'category'], true)) {
        return sanitize_string($m[1], 200);
    }

    return '';
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', (string) $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function check_origin(): void
{
    $config = load_config();
    $allowed = $config['allowed_origins'] ?? [];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin !== '' && !in_array($origin, $allowed, true)) {
        json_response(['success' => false, 'error' => 'Origem não permitida.'], 403);
    }

    if ($origin !== '') {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Vary: Origin');
    }
}

function check_rate_limit(string $ip, int $maxPerHour = 5): void
{
    $dir = dirname(__DIR__) . '/data/rate-limit';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $file = $dir . '/' . hash('sha256', $ip) . '.json';
    $now = time();
    $window = 3600;
    $entries = [];

    if (is_file($file)) {
        $raw = file_get_contents($file);
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            $entries = array_filter($decoded, static fn($ts) => is_int($ts) && ($now - $ts) < $window);
        }
    }

    if (count($entries) >= $maxPerHour) {
        json_response(['success' => false, 'error' => 'Muitas tentativas. Tente novamente em alguns minutos.'], 429);
    }

    $entries[] = $now;
    file_put_contents($file, json_encode(array_values($entries)), LOCK_EX);
}

const INVESTIMENTO_OPTIONS = [
    'nao_investe' => 'Ainda não invisto em mídia',
    'ate_2k' => 'Até R$ 2 mil/mês',
    '2k_10k' => 'R$ 2 mil a R$ 10 mil/mês',
    'acima_10k' => 'Acima de R$ 10 mil/mês',
];

const STATUS_OPTIONS = ['novo', 'em_contato', 'convertido', 'descartado'];

function sanitize_string(string $value, int $max = 500): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function normalize_whatsapp(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function start_admin_session(): void
{
    $config = load_config();
    $lifetime = (int) ($config['session_lifetime'] ?? 28800);

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();
    }
}

function require_admin(): void
{
    start_admin_session();
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: ' . admin_url('login.php'));
        exit;
    }
}

/** Atualiza o hash da senha admin em config.php (str_replace evita corromper $2y$ do bcrypt). */
function set_admin_password_hash(string $plainPassword): void
{
    $configPath = __DIR__ . '/config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Arquivo api/config.php não encontrado.');
    }

    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $contents = file_get_contents($configPath);
    if ($contents === false || !preg_match("/'admin_password_hash'\\s*=>\\s*'[^']*'/", $contents, $match)) {
        throw new RuntimeException('admin_password_hash não encontrado em config.php');
    }

    $replacement = "'admin_password_hash' => '" . addslashes($hash) . "'";
    file_put_contents($configPath, str_replace($match[0], $replacement, $contents, $count));
    if ($count !== 1) {
        throw new RuntimeException('Não foi possível atualizar admin_password_hash em config.php');
    }
}

function status_label(string $status): string
{
    return match ($status) {
        'novo' => 'Novo',
        'em_contato' => 'Em contato',
        'convertido' => 'Convertido',
        'descartado' => 'Descartado',
        default => $status,
    };
}
