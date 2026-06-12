<?php

namespace App\Http\Controllers;

use App\Support\DesktopApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use PDO;
use PDOException;
use Throwable;

/**
 * Assistant de configuration au premier lancement de l'application de bureau.
 *
 * Permet à l'utilisateur de choisir le type de base de données
 * (MySQL par défaut, ou SQLite), de tester la connexion, puis d'écrire la
 * configuration dans le fichier .env et de lancer les migrations.
 */
class SetupController extends Controller
{
    /** Affiche le formulaire de configuration. */
    public function index()
    {
        // Si déjà installé, inutile de repasser par l'assistant.
        if (DesktopApp::isInstalled()) {
            return redirect('/login');
        }

        return view('setup.index', [
            'defaults' => [
                'driver'   => old('driver', 'mysql'),
                'host'     => old('host', '127.0.0.1'),
                'port'     => old('port', '3306'),
                'database' => old('database', 'gestion_mutations'),
                'username' => old('username', 'root'),
                'password' => old('password', ''),
            ],
        ]);
    }

    /** Teste la connexion à la base sans rien écrire (AJAX). */
    public function test(Request $request)
    {
        $data = $this->validateInput($request);

        try {
            if ($data['driver'] === 'sqlite') {
                // SQLite : on vérifie juste qu'on peut créer/ouvrir le fichier.
                $path = $this->sqlitePath();
                @touch($path);
                new PDO('sqlite:'.$path);
            } else {
                $this->makeMysqlPdo($data);
            }

            return response()->json(['ok' => true, 'message' => 'Connexion réussie.']);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Échec : '.$e->getMessage(),
            ], 422);
        }
    }

    /** Enregistre la configuration, lance les migrations et termine l'install. */
    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        // 1. Vérifier la connexion avant d'écrire quoi que ce soit.
        try {
            if ($data['driver'] === 'sqlite') {
                $sqlitePath = $this->sqlitePath();
                @touch($sqlitePath);
                new PDO('sqlite:'.$sqlitePath);
            } else {
                $this->makeMysqlPdo($data);
            }
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'connexion' => 'Connexion impossible : '.$e->getMessage(),
            ]);
        }

        // 2. Écrire la configuration dans le .env runtime.
        $env = [
            'APP_ENV'       => 'production',
            'APP_DEBUG'     => false,
            'DB_CONNECTION' => $data['driver'],
        ];

        if ($data['driver'] === 'sqlite') {
            $env['DB_DATABASE'] = $this->sqlitePath();
        } else {
            $env['DB_HOST']     = $data['host'];
            $env['DB_PORT']     = $data['port'];
            $env['DB_DATABASE'] = $data['database'];
            $env['DB_USERNAME'] = $data['username'];
            $env['DB_PASSWORD'] = $data['password'] ?? '';
        }

        DesktopApp::updateEnv($env);

        // 3. Générer une clé applicative si absente.
        if (! $this->hasAppKey()) {
            DesktopApp::updateEnv(['APP_KEY' => 'base64:'.base64_encode(random_bytes(32))]);
        }

        // 4. Recharger la config à chaud et lancer les migrations.
        try {
            $this->applyRuntimeConfig($data);

            Artisan::call('config:clear');
            Artisan::call('migrate', ['--force' => true]);

            // Seeders idempotents (rôles/permissions, compte admin, etc.).
            if ($this->seederExists()) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'connexion' => 'Migrations échouées : '.$e->getMessage(),
            ]);
        }

        // 5. Marquer l'installation comme terminée.
        DesktopApp::markInstalled();

        return redirect('/login')->with('status', 'Configuration terminée. Vous pouvez vous connecter.');
    }

    /** @return array<string,string> */
    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'driver'   => 'required|in:mysql,sqlite',
            'host'     => 'required_if:driver,mysql|nullable|string',
            'port'     => 'required_if:driver,mysql|nullable|string',
            'database' => 'required_if:driver,mysql|nullable|string',
            'username' => 'required_if:driver,mysql|nullable|string',
            'password' => 'nullable|string',
        ]);
    }

    /** Ouvre une connexion PDO MySQL (lève une exception en cas d'échec). */
    protected function makeMysqlPdo(array $data): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $data['host'],
            $data['port'] ?: '3306',
            $data['database']
        );

        return new PDO($dsn, $data['username'], $data['password'] ?? '', [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /** Chemin du fichier SQLite (dans le dossier de données utilisateur). */
    protected function sqlitePath(): string
    {
        return DesktopApp::dataDir().'/database.sqlite';
    }

    /** Applique la config DB choisie à la connexion courante (sans redémarrer). */
    protected function applyRuntimeConfig(array $data): void
    {
        config(['database.default' => $data['driver']]);

        if ($data['driver'] === 'sqlite') {
            config(['database.connections.sqlite.database' => $this->sqlitePath()]);
        } else {
            config(['database.connections.mysql' => array_merge(
                config('database.connections.mysql'),
                [
                    'host'     => $data['host'],
                    'port'     => $data['port'] ?: '3306',
                    'database' => $data['database'],
                    'username' => $data['username'],
                    'password' => $data['password'] ?? '',
                ]
            )]);
        }

        app('db')->purge($data['driver']);
    }

    protected function hasAppKey(): bool
    {
        $key = (string) env('APP_KEY');

        return $key !== '' && $key !== 'base64:';
    }

    protected function seederExists(): bool
    {
        return class_exists(\Database\Seeders\DatabaseSeeder::class)
            && is_file(database_path('seeders/DatabaseSeeder.php'));
    }
}
