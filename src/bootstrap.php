<?php

$libRoot = dirname(__DIR__);
$libNamespaces = [
    'Addr' => $libRoot . '/src/',
    'Alert' => $libRoot . '/lib/Alert/',
    'LibDNS' => $libRoot . '/lib/LibDNS/',
];

spl_autoload_register(function($className) use($libNamespaces) {
    $baseNS = strstr($className, '\\', true);

    if (isset($libNamespaces[$baseNS])) {
        require $libNamespaces[$baseNS] . strtr($className, '\\', '/') . '.php';
    }
});
