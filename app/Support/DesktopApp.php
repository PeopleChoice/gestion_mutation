<?php

namespace App\Support;

/**
 * Helpers pour le mode "application de bureau" (empaquetage Tauri).
 *
 * En mode bureau, le launcher Rust transmet le dossier de données
 * inscriptible de l'utilisateur via la variable d'environnement GM_DATA_DIR.
 * C'est dans ce dossier que vivent le fichier .env, le dossier storage et,
 * le cas échéant, la base SQLite.
 */
class DesktopApp
{
    /** L'application tourne-t-elle empaquetée dans Tauri ? */
    public static function isDesktop(): bool
    {
        return getenv('GM_DATA_DIR') !== false && getenv('GM_DATA_DIR') !== '';
    }

    /** Dossier de données inscriptible de l'utilisateur (mode bureau). */
    public static function dataDir(): string
    {
        $dir = getenv('GM_DATA_DIR') ?: storage_path('app');

        return rtrim(str_replace('\\', '/', $dir), '/');
    }

    /** Chemin absolu du fichier .env utilisé à l'exécution. */
    public static function envPath(): string
    {
        return self::isDesktop()
            ? self::dataDir().'/.env'
            : base_path('.env');
    }

    /** Chemin du marqueur "installation terminée". */
    public static function installedFlagPath(): string
    {
        return self::dataDir().'/installed.flag';
    }

    /** L'assistant de configuration a-t-il déjà été complété ? */
    public static function isInstalled(): bool
    {
        return file_exists(self::installedFlagPath());
    }

    /** Marque l'installation comme terminée. */
    public static function markInstalled(): void
    {
        @file_put_contents(self::installedFlagPath(), date('c'));
    }

    /**
     * Met à jour (ou ajoute) une série de clés dans le fichier .env runtime.
     *
     * @param  array<string, string|int|bool|null>  $values
     */
    public static function updateEnv(array $values): void
    {
        $path = self::envPath();
        $contents = is_file($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.self::formatEnvValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents);
            } else {
                $contents = rtrim($contents, "\r\n")."\n".$line."\n";
            }
        }

        file_put_contents($path, $contents);
    }

    /** Encadre la valeur de guillemets si nécessaire pour le format .env. */
    protected static function formatEnvValue(string|int|bool|null $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if ($value === '' || preg_match('/\s|#|"|\'/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }
}
