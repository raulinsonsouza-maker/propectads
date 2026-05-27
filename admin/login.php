<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';

start_admin_session();

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: leads.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $config = load_config();
        $user = trim((string) ($_POST['user'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($user === ($config['admin_user'] ?? '') && password_verify($password, $config['admin_password_hash'] ?? '')) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user;
            $_SESSION['admin_login_at'] = time();
            header('Location: leads.php');
            exit;
        }

        $error = 'Usuário ou senha incorretos.';
    } catch (Throwable $e) {
        $error = 'Erro de configuração. Verifique api/config.php';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login | ProspectAds</title>
    <?php require dirname(__DIR__) . '/includes/site-favicon.php'; site_render_favicon(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body login-body">
    <div class="admin-bg" aria-hidden="true"></div>
    <div class="login-wrap">
        <div class="login-card">
            <a href="../" class="login-card__brand site-brand">
                <span class="site-brand__name">ProspectAds</span>
            </a>
            <h1>Login</h1>
            <p>Painel de leads e blog</p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" class="login-form">
                <label for="user">Usuário</label>
                <input type="text" id="user" name="user" required autocomplete="username">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <button type="submit" class="btn-primary">Entrar</button>
            </form>
            <p class="login-back">
                <a href="../">← Voltar ao site</a>
            </p>
        </div>
    </div>
</body>
</html>
