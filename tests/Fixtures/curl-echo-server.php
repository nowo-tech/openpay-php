<?php

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, \PHP_URL_PATH) ?: '/';

if ('/latin1' === $path) {
    header('Content-Type: text/plain; charset=ISO-8859-1');
    echo "\xC1";

    exit;
}

if ('/echo' === $path) {
    http_response_code(201);
    header('Content-Type: application/json');
    echo json_encode([
        'method' => $method,
        'ok' => true,
    ], \JSON_THROW_ON_ERROR);

    exit;
}

http_response_code(200);
header('Content-Type: application/json');
echo '{"ok":true}';
