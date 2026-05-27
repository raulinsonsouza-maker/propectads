<?php

declare(strict_types=1);

/**
 * Instalação única: cria o banco SQLite e valida config.php
 * Acesse uma vez: /scripts/install.php?password=SUA_SENHA
 * Remova ou renomeie este arquivo após a instalação.
 */

require dirname(__DIR__) . '/api/bootstrap.php';

$lockFile = dirname(__DIR__) . '/data/.installed';

if (is_file($lockFile) && empty($_GET['force'])) {
    http_response_code(403);
    echo '<h1>Já instalado</h1><p>O sistema já foi configurado. Remova scripts/install.php do servidor.</p>';
    exit;
}

$password = $_GET['password'] ?? $_POST['password'] ?? '';

if ($password === '') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Instalar ProspectAds Leads</title>
        <style>
            body { font-family: system-ui, sans-serif; max-width: 480px; margin: 40px auto; padding: 0 20px; }
            input, button { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; }
        </style>
    </head>
    <body>
        <h1>Instalação</h1>
        <p>Certifique-se de que <code>api/config.php</code> existe (copie de config.example.php).</p>
        <form method="post">
            <label>Senha do painel admin</label>
            <input type="password" name="password" required minlength="8">
            <button type="submit">Instalar banco de dados</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo 'Senha deve ter no mínimo 8 caracteres.';
    exit;
}

try {
    $config = load_config();
    $pdo = get_pdo();
    ensure_schema($pdo);

    set_admin_password_hash($password);
    file_put_contents($lockFile, date('c'));

    echo '<h1>Instalação concluída</h1>';
    echo '<p>Banco criado. Senha admin atualizada.</p>';
    echo '<p><strong>Remova scripts/install.php do servidor agora.</strong></p>';
    echo '<p><a href="../admin/login.php">Ir para o painel</a></p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Erro na instalação</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
