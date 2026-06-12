<?php

/**
 * Routeur pour le serveur web intégré de PHP (php -S), utilisé par
 * l'application de bureau Tauri.
 *
 * Lancé via : php -S 127.0.0.1:<port> server.php
 *
 * Sert directement les fichiers statiques présents dans public/, et
 * transmet toutes les autres requêtes au front controller Laravel
 * (public/index.php).
 */

$publicPath = __DIR__.'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Fichier statique existant dans public/ : laisser PHP le servir tel quel.
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';

require_once $publicPath.'/index.php';
