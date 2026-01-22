<?php

declare(strict_types=1);

// Try to use the main project's autoloader first
$autoloadPaths = [
    __DIR__.'/../../../vendor/autoload.php', // When running from bundle in main project
    __DIR__.'/../vendor/autoload.php',        // When running as standalone bundle
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}
