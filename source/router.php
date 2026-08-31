<?php

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

if (preg_match('#^/api/#', $uri)) {
    require __DIR__ . '/api.php';
    return true;
}

if (preg_match('#^/out/pictures/generated/#', $uri)) {
    require __DIR__ . '/getimg.php';
    return true;
}

if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
