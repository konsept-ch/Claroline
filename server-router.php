<?php

// Router for PHP built-in server.
// Serve existing files from public/, otherwise forward to Symfony front controller.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__.'/public'.$path;

if ('/' !== $path && is_file($file)) {
    return false;
}

require __DIR__.'/public/index.php';
