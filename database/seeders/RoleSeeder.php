<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions
        $permissions = [
            'importer_fichier',
            'voir_imports',
            'gerer_parcelles',
            'valider_mutation',
            'refuser_mutation',
            'annuler_mutation',
            'approuver_annulation',
            'generer_rapport',
            'generer_pdf',
            'gerer_templates',
            'gerer_utilisateurs',
            'gerer_projets',
            'gerer_communes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Rôle Admin
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        // Rôle Receveur des Domaines
        $receveur = Role::firstOrCreate(['name' => 'receveur']);
        $receveur->syncPermissions([
            'voir_imports',
            'valider_mutation',
            'refuser_mutation',
            'generer_rapport',
            'generer_pdf',
            'gerer_parcelles',
        ]);

        // Rôle Gestionnaire
        $gestionnaire = Role::firstOrCreate(['name' => 'gestionnaire']);
        $gestionnaire->syncPermissions([
            'importer_fichier',
            'voir_imports',
            'gerer_parcelles',
            'valider_mutation',
            'refuser_mutation',
            'annuler_mutation',
            'generer_rapport',
            'generer_pdf',
            'gerer_projets',
            'gerer_communes',
        ]);

        // Rôle Opérateur (saisie)
        $operateur = Role::firstOrCreate(['name' => 'operateur']);
        $operateur->syncPermissions([
            'importer_fichier',
            'voir_imports',
            'gerer_parcelles',
        ]);

        // Créer l'utilisateur admin par défaut.
        // Le mot de passe doit être défini via .env (ADMIN_SEED_PASSWORD),
        // sinon on en génère un aléatoire et on l'affiche en console.
        $adminEmail = env('ADMIN_SEED_EMAIL', 'admin@domaines.sn');
        $adminPassword = env('ADMIN_SEED_PASSWORD');

        if (empty($adminPassword)) {
            $adminPassword = \Illuminate\Support\Str::random(16);
            $this->command?->warn("⚠️  ADMIN_SEED_PASSWORD non défini dans .env — mot de passe généré : {$adminPassword}");
            $this->command?->warn("   Notez-le, il ne sera plus affiché. Changez-le dès la première connexion.");
        }

        $adminUser = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrateur',
                'password' => bcrypt($adminPassword),
            ]
        );
        $adminUser->assignRole('admin');
    }
}
