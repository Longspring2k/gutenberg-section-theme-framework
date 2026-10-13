<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Showcase\\';
    if (str_starts_with($class, $prefix)) {
        $path = __DIR__ . '/src/' . substr($class, strlen($prefix)) . '.php';
        if (is_file($path)) { require $path; }
    }
});
