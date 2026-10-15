<?php
declare(strict_types=1);

/**
 * Zoghdan | زغدان configuration loader.
 * Modern Mercantile design reminder: preserve the premium editorial hierarchy in all UI additions.
 */
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    return require $localConfig;
}

return [
    'installed' => false,
    'app_key' => 'change-this-during-installation',
    'db' => ['host' => '', 'name' => '', 'user' => '', 'pass' => ''],
];
