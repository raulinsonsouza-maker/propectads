<?php

declare(strict_types=1);

/**
 * Corrige slugs com acento gravados por engano no banco.
 * Uso: php scripts/fix-blog-slugs-accented.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$pdo = get_pdo();

$fixes = [
    'aumentar-ticket-médio-ecommerce-sem-mais-tráfego' => 'aumentar-ticket-medio-ecommerce-sem-mais-trafego',
    'como-saber-se-tráfego-pago-funciona-ecommerce' => 'como-saber-se-trafego-pago-funciona-ecommerce',
];

foreach ($fixes as $old => $new) {
    $check = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug');
    $check->execute([':slug' => $new]);
    if ($check->fetch()) {
        echo "Slug destino já existe, removendo duplicata antiga: {$old}\n";
        $pdo->prepare('DELETE FROM blog_posts WHERE slug = :slug')->execute([':slug' => $old]);
        continue;
    }
    $stmt = $pdo->prepare('UPDATE blog_posts SET slug = :new, updated_at = :u WHERE slug = :old');
    $stmt->execute([':new' => $new, ':old' => $old, ':u' => gmdate('c')]);
    if ($stmt->rowCount() > 0) {
        echo "Atualizado slug: {$old} -> {$new}\n";
    } else {
        echo "Não encontrado: {$old}\n";
    }
}

echo "Concluído.\n";
