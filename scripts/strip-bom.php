<?php

declare(strict_types=1);

$path = $argv[1] ?? '';
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "Usage: php strip-bom.php <file>\n");
    exit(1);
}

$c = file_get_contents($path);
if (str_starts_with($c, "\xEF\xBB\xBF")) {
    file_put_contents($path, substr($c, 3));
    echo "BOM removed: {$path}\n";
    exit(0);
}

echo "No BOM: {$path}\n";
