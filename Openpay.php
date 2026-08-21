<?php

declare(strict_types=1);

/**
 * Manual-install bootstrap for the Openpay PHP SDK.
 *
 * Composer users should load vendor/autoload.php instead of this file.
 * This register is PSR-4 compatible with Openpay\ → Openpay/.
 */
if (!function_exists('curl_init')) {
    throw new Exception('CURL PHP extension is required to run Openpay client.');
}
if (!function_exists('json_decode')) {
    throw new Exception('JSON PHP extension is required to run Openpay client.');
}
if (!function_exists('mb_detect_encoding')) {
    throw new Exception('Multibyte String PHP extension is required to run Openpay client.');
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Openpay\\')) {
        return;
    }

    $path = __DIR__.'/'.str_replace('\\', '/', $class).'.php';
    if (is_file($path)) {
        require $path;
    }
});
