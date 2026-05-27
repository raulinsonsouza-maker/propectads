<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    check_origin();
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Método não permitido.'], 405);
}

check_origin();

try {
    $pdo = get_pdo();
    ensure_schema($pdo);
} catch (Throwable $e) {
    json_response(['success' => false, 'error' => 'Serviço temporariamente indisponível.'], 503);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];

if (str_contains($contentType, 'application/json')) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        json_response(['success' => false, 'error' => 'JSON inválido.'], 400);
    }
    $input = $decoded;
} else {
    $input = $_POST;
}

if (!empty($input['website'])) {
    json_response(['success' => true, 'id' => 0]);
}

$nome = sanitize_string((string) ($input['nome'] ?? ''), 120);
$whatsapp = normalize_whatsapp((string) ($input['whatsapp'] ?? ''));
$loja = sanitize_string((string) ($input['loja'] ?? ''), 300);
$investimento = (string) ($input['investimento'] ?? '');

if ($nome === '' || strlen($whatsapp) < 10 || $loja === '' || !array_key_exists($investimento, INVESTIMENTO_OPTIONS)) {
    json_response(['success' => false, 'error' => 'Preencha todos os campos obrigatórios.'], 400);
}

if (strlen($whatsapp) <= 11 && !str_starts_with($whatsapp, '55')) {
    $whatsapp = '55' . $whatsapp;
}

$ip = get_client_ip();
check_rate_limit($ip);

$stmt = $pdo->prepare(
    'INSERT INTO leads (nome, whatsapp, loja, investimento, status, ip, user_agent, created_at)
     VALUES (:nome, :whatsapp, :loja, :investimento, :status, :ip, :user_agent, datetime("now"))'
);

$stmt->execute([
    ':nome' => $nome,
    ':whatsapp' => $whatsapp,
    ':loja' => $loja,
    ':investimento' => $investimento,
    ':status' => 'novo',
    ':ip' => $ip,
    ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
]);

json_response([
    'success' => true,
    'id' => (int) $pdo->lastInsertId(),
]);
