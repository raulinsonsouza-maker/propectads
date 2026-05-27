<?php
/**
 * Copie este arquivo para config.php e ajuste os valores.
 * Nunca commite config.php no repositório.
 */

return [
    // URL pública do site (sitemap, canonical, Open Graph)
    'site_url' => 'https://prospectads.com.br',
    'admin_user' => 'admin',
    // Gere com: php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT);"
    'admin_password_hash' => '$2y$10$REPLACE_WITH_PASSWORD_HASH',
    'db_path' => dirname(__DIR__) . '/data/leads.sqlite',
    'session_lifetime' => 28800,
    'allowed_origins' => [
        'https://prospectads.com.br',
        'http://localhost',
        'http://127.0.0.1',
    ],
    'whatsapp_business' => '5519982459427',
    // Upload de imagem destacada do blog (bytes). Padrão: 2 MB.
    'blog_upload_max_bytes' => 2 * 1024 * 1024,
];
