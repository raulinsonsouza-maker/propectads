<?php

declare(strict_types=1);

/**
 * Corrige categorias de posts e remove categoria legada vazia (conversao).
 * Uso: php scripts/fix-blog-categories.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$pdo = get_pdo();
ensure_schema($pdo);

$catTrafego = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'trafego-midia'")->fetchColumn();
$catConversao = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'conversao-vendas'")->fetchColumn();
$catEcommerce = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'e-commerce'")->fetchColumn();
$catEstrategia = (int) $pdo->query("SELECT id FROM blog_categories WHERE slug = 'estrategia-crescimento'")->fetchColumn();
$catLegado = $pdo->query("SELECT id FROM blog_categories WHERE slug = 'conversao'")->fetchColumn();

if (!$catTrafego || !$catConversao || !$catEcommerce || !$catEstrategia) {
    fwrite(STDERR, "Categorias principais não encontradas. Rode os seeds primeiro.\n");
    exit(1);
}

/** slug => category_id correto */
$fixes = [
    // Legado: tráfego pago estava em E-commerce
    'trafego-pago-ecommerce' => $catTrafego,
    // category_key com acento caiu em Estratégia no seed 11–20
    'reduzir-abandono-carrinho-ecommerce' => $catConversao,
    'site-lento-afeta-vendas-ecommerce' => $catConversao,
    'aumentar-ticket-medio-ecommerce-sem-mais-trafego' => $catConversao,
    'vender-mais-whatsapp-ecommerce' => $catConversao,
    'roas-ruim-como-diagnosticar-ecommerce' => $catTrafego,
    'como-saber-se-trafego-pago-funciona-ecommerce' => $catTrafego,
    'escolher-produtos-para-anunciar-ecommerce' => $catTrafego,
    'vale-investir-google-shopping-ecommerce' => $catTrafego,
];

$upd = $pdo->prepare('UPDATE blog_posts SET category_id = :cid, updated_at = :u WHERE slug = :slug');
$now = gmdate('c');

foreach ($fixes as $slug => $categoryId) {
    $upd->execute([':cid' => $categoryId, ':slug' => $slug, ':u' => $now]);
    if ($upd->rowCount() > 0) {
        echo "Corrigido: {$slug}\n";
    }
}

// Posts ainda na categoria legada "Conversão" (slug conversao) → Conversão e vendas
if ($catLegado) {
    $moved = $pdo->prepare(
        'UPDATE blog_posts SET category_id = :new, updated_at = :u WHERE category_id = :old'
    )->execute([':new' => $catConversao, ':old' => (int) $catLegado, ':u' => $now]);
    $count = $pdo->query('SELECT changes()')->fetchColumn();
    if ($count > 0) {
        echo "Migrados {$count} post(s) de conversao → conversao-vendas\n";
    }

    $left = $pdo->prepare('SELECT COUNT(*) FROM blog_posts WHERE category_id = ?');
    $left->execute([(int) $catLegado]);
    if ((int) $left->fetchColumn() === 0) {
        $pdo->prepare('DELETE FROM blog_categories WHERE id = ?')->execute([(int) $catLegado]);
        echo "Removida categoria vazia: Conversão (slug conversao)\n";
    }
}

echo "\nDistribuição final:\n";
$rows = $pdo->query(
    'SELECT c.name, c.slug, COUNT(p.id) AS n
     FROM blog_categories c
     LEFT JOIN blog_posts p ON p.category_id = c.id AND p.status = \'published\'
     GROUP BY c.id ORDER BY c.name'
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo sprintf("  %-32s %-22s %d\n", $r['name'], $r['slug'], (int) $r['n']);
}

echo "\nConcluído.\n";
