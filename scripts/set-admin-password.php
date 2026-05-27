<?php

declare(strict_types=1);

$password = $argv[1] ?? '';
if ($password === '') {
    fwrite(STDERR, "Uso: php scripts/set-admin-password.php SENHA\n");
    exit(1);
}

$configPath = dirname(__DIR__) . '/api/config.php';
if (!is_file($configPath)) {
    copy(dirname(__DIR__) . '/api/config.example.php', $configPath);
}

require dirname(__DIR__) . '/api/bootstrap.php';

try {
    set_admin_password_hash($password);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$contents = file_get_contents($configPath);
$updated = preg_replace(
    "/'admin_user'\\s*=>\\s*'[^']*'/",
    "'admin_user' => 'admin'",
    $contents,
    1
);
if ($updated !== null) {
    file_put_contents($configPath, $updated);
}
echo "Senha do admin atualizada. Login: admin / {$password}\n";
