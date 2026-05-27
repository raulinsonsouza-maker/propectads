<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';

$password = $argv[1] ?? 'admin12345';

if (strlen($password) < 8) {
    fwrite(STDERR, "Senha deve ter no mínimo 8 caracteres.\n");
    exit(1);
}

$pdo = get_pdo();
ensure_schema($pdo);

set_admin_password_hash($password);
file_put_contents(dirname(__DIR__) . '/data/.installed', date('c'));

echo "Instalação OK. Login: admin / {$password}\n";
