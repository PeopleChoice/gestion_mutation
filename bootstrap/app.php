<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Force le passage par l'assistant de configuration au 1er lancement
        // (uniquement en mode application de bureau / Tauri, voir le middleware).
        $middleware->web(append: [
            \App\Http\Middleware\EnsureAppIsConfigured::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

/*
|--------------------------------------------------------------------------
| Mode application de bureau (Tauri)
|--------------------------------------------------------------------------
| Quand l'application tourne empaquetée dans Tauri, le binaire et le code
| source sont installés en LECTURE SEULE (ex: Program Files). On relocalise
| donc les éléments MODIFIABLES (.env, dossier storage, base SQLite,
| sessions, cache) dans un dossier inscriptible propre à l'utilisateur,
| transmis par le launcher Rust via la variable GM_DATA_DIR.
*/
if ($dataDir = getenv('GM_DATA_DIR')) {
    $dataDir = rtrim(str_replace('\\', '/', $dataDir), '/');

    // .env lu/écrit depuis le dossier de données utilisateur
    $app->useEnvironmentPath($dataDir);

    // storage/ (logs, cache, sessions, vues compilées, fichiers générés)
    $app->useStoragePath($dataDir.'/storage');
}

return $app;
