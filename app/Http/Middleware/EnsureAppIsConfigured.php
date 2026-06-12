<?php

namespace App\Http\Middleware;

use App\Support\DesktopApp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige vers l'assistant de configuration tant que l'application de bureau
 * n'a pas été configurée (choix de la base de données + migrations).
 *
 * Ce middleware n'agit QUE lorsque l'application tourne en mode bureau (Tauri),
 * détecté via la présence de la variable d'environnement GM_DATA_DIR.
 * En usage web classique, il laisse passer toutes les requêtes.
 */
class EnsureAppIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hors mode bureau : ne rien forcer.
        if (! DesktopApp::isDesktop()) {
            return $next($request);
        }

        // Déjà configuré : laisser passer.
        if (DesktopApp::isInstalled()) {
            return $next($request);
        }

        // Laisser passer les routes de l'assistant lui-même et les assets.
        if ($request->is('setup', 'setup/*', 'build/*', 'storage/*', 'favicon.ico', 'up')) {
            return $next($request);
        }

        return redirect()->route('setup.index');
    }
}
