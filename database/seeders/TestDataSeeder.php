<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Import;
use App\Models\ImportLigne;
use App\Models\Mutation;
use App\Models\MutationAnnulation;
use App\Models\Parcelle;
use App\Models\Projet;
use App\Models\Proprietaire;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ===== UTILISATEURS =====
        $receveur = User::firstOrCreate(
            ['email' => 'receveur@domaines.sn'],
            ['name' => 'Mamadou DIALLO', 'password' => bcrypt('password')]
        );
        $receveur->assignRole('receveur');

        $gestionnaire = User::firstOrCreate(
            ['email' => 'gestionnaire@domaines.sn'],
            ['name' => 'Abdoulaye NDIAYE', 'password' => bcrypt('password')]
        );
        $gestionnaire->assignRole('gestionnaire');

        $operateur = User::firstOrCreate(
            ['email' => 'operateur@domaines.sn'],
            ['name' => 'Fatou SARR', 'password' => bcrypt('password')]
        );
        $operateur->assignRole('operateur');

        $admin = User::where('email', 'admin@domaines.sn')->first();

        // ===== COMMUNES =====
        $communes = [];
        $communesData = [
            ['nom' => 'Mont-Rolland', 'departement' => 'Tivaouane', 'region' => 'Thiès'],
            ['nom' => 'Tivaouane', 'departement' => 'Tivaouane', 'region' => 'Thiès'],
            ['nom' => 'Mékhé', 'departement' => 'Tivaouane', 'region' => 'Thiès'],
            ['nom' => 'Pambal', 'departement' => 'Tivaouane', 'region' => 'Thiès'],
            ['nom' => 'Pire', 'departement' => 'Tivaouane', 'region' => 'Thiès'],
            ['nom' => 'Mbayène', 'departement' => 'Tivaouane', 'region' => 'Thiès'],
            ['nom' => 'Darou Khoudoss', 'departement' => 'Tivaouane', 'region' => 'Thiès'],
        ];
        foreach ($communesData as $c) {
            $communes[] = Commune::firstOrCreate(['nom' => $c['nom']], $c);
        }

        // ===== PROJETS =====
        $projets = [];
        $projetsData = [
            ['nom' => 'RAVIN', 'type_lotissement' => 'Régularisation', 'commune' => 0, 'description' => 'Lotissement dit RAVIN dans la Commune de Mont-Rolland'],
            ['nom' => 'CITE RELIGIEUSE', 'type_lotissement' => 'Extension', 'commune' => 1, 'description' => 'Extension de la cité religieuse de Tivaouane'],
            ['nom' => 'ZONE SUD', 'type_lotissement' => 'Régularisation', 'commune' => 2, 'description' => 'Lotissement zone sud de Mékhé'],
            ['nom' => 'PARCELLES ASSAINIES', 'type_lotissement' => 'Nouveau', 'commune' => 1, 'description' => 'Nouveau lotissement parcelles assainies Tivaouane'],
            ['nom' => 'KEUR MASSAR', 'type_lotissement' => 'Régularisation', 'commune' => 3, 'description' => 'Régularisation quartier Keur Massar Pambal'],
        ];
        foreach ($projetsData as $p) {
            $projets[] = Projet::firstOrCreate(
                ['nom' => $p['nom'], 'commune_id' => $communes[$p['commune']]->id],
                [
                    'type_lotissement' => $p['type_lotissement'],
                    'commune_id' => $communes[$p['commune']]->id,
                    'description' => $p['description'],
                ]
            );
        }

        // ===== PROPRIÉTAIRES =====
        $proprietaires = [];
        $propsData = [
            ['civilite' => 'Monsieur', 'prenom' => 'Abdoul', 'nom' => 'SY', 'nin' => '1619197303906', 'cni_passport' => '1847200512345', 'telephone' => '77 561 24 52'],
            ['civilite' => 'Madame', 'prenom' => 'Fama', 'nom' => 'NDIAYE', 'nin' => '2619197303906', 'cni_passport' => '2753198800123', 'telephone' => '78 432 11 87'],
            ['civilite' => 'Société', 'prenom' => 'Abdou Fama 2011 Sarl', 'nom' => '', 'ninea' => '000294731', 'cni_passport' => null, 'telephone' => '33 867 87 00'],
            ['civilite' => 'Monsieur', 'prenom' => 'Ibrahima', 'nom' => 'FALL', 'nin' => '1785198401234', 'cni_passport' => '1785198401234', 'telephone' => '76 300 45 12'],
            ['civilite' => 'Madame', 'prenom' => 'Aïssatou', 'nom' => 'DIOP', 'nin' => '2890199200567', 'cni_passport' => '2890199200567', 'telephone' => '77 123 45 67'],
            ['civilite' => 'Monsieur', 'prenom' => 'Ousmane', 'nom' => 'BA', 'nin' => '1654198700890', 'cni_passport' => '1654198700890', 'telephone' => '70 890 12 34'],
            ['civilite' => 'Monsieur', 'prenom' => 'Cheikh', 'nom' => 'MBAYE', 'nin' => '1432197600345', 'cni_passport' => '1432197600345', 'telephone' => '77 654 32 10'],
            ['civilite' => 'Madame', 'prenom' => 'Mariama', 'nom' => 'GUEYE', 'nin' => '2321198500678', 'cni_passport' => '2321198500678', 'telephone' => '78 765 43 21'],
            ['civilite' => 'Monsieur', 'prenom' => 'Moussa', 'nom' => 'SECK', 'nin' => '1567199000901', 'cni_passport' => '1567199000901', 'telephone' => '76 543 21 09'],
            ['civilite' => 'Madame', 'prenom' => 'Khady', 'nom' => 'DIOUF', 'nin' => '2210198800234', 'cni_passport' => '2210198800234', 'telephone' => '77 876 54 32'],
            ['civilite' => 'Monsieur', 'prenom' => 'Saliou', 'nom' => 'SOW', 'nin' => '1890197800567', 'cni_passport' => '1890197800567', 'telephone' => '70 234 56 78'],
            ['civilite' => 'Monsieur', 'prenom' => 'Djibril', 'nom' => 'THIOMBANE', 'nin' => '1345198200890', 'cni_passport' => '1345198200890', 'telephone' => '77 345 67 89'],
            ['civilite' => 'Société', 'prenom' => 'SCI Dakar Invest', 'nom' => '', 'ninea' => '005412890', 'cni_passport' => null, 'telephone' => '33 821 45 67'],
            ['civilite' => 'Monsieur', 'prenom' => 'Pape', 'nom' => 'TOURE', 'nin' => '1678198500123', 'cni_passport' => '1678198500123', 'telephone' => '76 678 90 12'],
            ['civilite' => 'Madame', 'prenom' => 'Ndèye', 'nom' => 'NGOM', 'nin' => '2456199100456', 'cni_passport' => '2456199100456', 'telephone' => '78 456 78 90'],
            ['civilite' => 'Monsieur', 'prenom' => 'Amadou', 'nom' => 'DIAGNE', 'nin' => '1234198900789', 'cni_passport' => '1234198900789', 'telephone' => '77 234 89 01'],
            ['civilite' => 'Madame', 'prenom' => 'Sokhna', 'nom' => 'KANE', 'nin' => '2567199300012', 'cni_passport' => '2567199300012', 'telephone' => '70 567 01 23'],
            ['civilite' => 'Monsieur', 'prenom' => 'Modou', 'nom' => 'FAYE', 'nin' => '1901198600345', 'cni_passport' => '1901198600345', 'telephone' => '76 901 34 56'],
            ['civilite' => 'Madame', 'prenom' => 'Ami', 'nom' => 'CISSE', 'nin' => '2678199400678', 'cni_passport' => '2678199400678', 'telephone' => '78 678 67 89'],
            ['civilite' => 'Monsieur', 'prenom' => 'Babacar', 'nom' => 'WADE', 'nin' => '1012198700901', 'cni_passport' => '1012198700901', 'telephone' => '77 012 90 12'],
        ];
        foreach ($propsData as $p) {
            $proprietaires[] = Proprietaire::create($p);
        }

        // ===== PARCELLES =====
        // Projet RAVIN (Mont-Rolland) - 40 lots
        $parcelles = [];
        for ($i = 3900; $i <= 3939; $i++) {
            $propIdx = ($i - 3900) % count($proprietaires);
            $parcelles[] = Parcelle::create([
                'numero_lot' => (string) $i,
                'projet_id' => $projets[0]->id, // RAVIN
                'proprietaire_id' => $proprietaires[$propIdx]->id,
                'date_attribution' => now()->subMonths(rand(1, 24)),
                'superficie' => rand(150, 500),
                'usage' => ['habitation', 'commercial', 'mixte'][rand(0, 2)],
            ]);
        }

        // Projet CITE RELIGIEUSE (Tivaouane) - 20 lots
        for ($i = 100; $i <= 119; $i++) {
            $propIdx = rand(0, count($proprietaires) - 1);
            $parcelles[] = Parcelle::create([
                'numero_lot' => 'CR-' . $i,
                'projet_id' => $projets[1]->id,
                'proprietaire_id' => $proprietaires[$propIdx]->id,
                'date_attribution' => now()->subMonths(rand(1, 36)),
                'superficie' => rand(200, 600),
                'usage' => ['habitation', 'commercial'][rand(0, 1)],
            ]);
        }

        // Projet ZONE SUD (Mékhé) - 15 lots
        for ($i = 1; $i <= 15; $i++) {
            $propIdx = rand(0, count($proprietaires) - 1);
            $parcelles[] = Parcelle::create([
                'numero_lot' => 'ZS-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'projet_id' => $projets[2]->id,
                'proprietaire_id' => $proprietaires[$propIdx]->id,
                'date_attribution' => now()->subMonths(rand(1, 12)),
                'superficie' => rand(180, 400),
                'usage' => 'habitation',
            ]);
        }

        // Projet PARCELLES ASSAINIES (Tivaouane) - 10 lots
        for ($i = 1; $i <= 10; $i++) {
            $propIdx = rand(0, count($proprietaires) - 1);
            $parcelles[] = Parcelle::create([
                'numero_lot' => 'PA-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'projet_id' => $projets[3]->id,
                'proprietaire_id' => $proprietaires[$propIdx]->id,
                'date_attribution' => now()->subMonths(rand(1, 6)),
                'superficie' => rand(150, 300),
                'usage' => 'habitation',
            ]);
        }

        // ===== IMPORT SIMULÉ =====
        $import = Import::create([
            'nom_fichier' => 'ATTRIBUTIONS_DJIBRIL THIOMBANE_SALIOU SOW_30012026.xls',
            'fichier_path' => 'imports/test_file.xls',
            'projet_id' => $projets[0]->id,
            'imported_by' => $admin->id,
            'statut' => 'termine',
            'total_lignes' => 10,
            'lignes_traitees' => 10,
            'lignes_matchees' => 8,
        ]);

        // Lignes d'import matchées
        $importLignes = [];
        $lotsMutables = [3900, 3901, 3902, 3903, 3904, 3905, 3906, 3907];
        foreach ($lotsMutables as $idx => $lot) {
            $p = Parcelle::where('numero_lot', (string) $lot)->where('projet_id', $projets[0]->id)->first();
            $importLignes[] = ImportLigne::create([
                'import_id' => $import->id,
                'numero_ordre' => $idx + 1,
                'civilite' => $propsData[$idx]['civilite'] ?? 'Monsieur',
                'prenom' => $propsData[$idx]['prenom'],
                'nom' => $propsData[$idx]['nom'],
                'nin' => $propsData[$idx]['nin'] ?? null,
                'ninea' => $propsData[$idx]['ninea'] ?? null,
                'telephone' => $propsData[$idx]['telephone'],
                'numero_lot' => (string) $lot,
                'ref_lettre' => 'REF-2026-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'date_excel' => '14.04.2026',
                'parcelle_id' => $p?->id,
                'matched' => true,
                'statut' => 'en_attente',
            ]);
        }

        // 2 lignes non matchées
        ImportLigne::create([
            'import_id' => $import->id,
            'numero_ordre' => 9,
            'civilite' => 'Monsieur',
            'prenom' => 'Lamine',
            'nom' => 'DIENG',
            'nin' => '1111199000111',
            'telephone' => '77 999 88 77',
            'numero_lot' => '9999',
            'date_excel' => '14.04.2026',
            'matched' => false,
            'statut' => 'en_attente',
        ]);
        ImportLigne::create([
            'import_id' => $import->id,
            'numero_ordre' => 10,
            'civilite' => 'Madame',
            'prenom' => 'Awa',
            'nom' => 'THIAM',
            'nin' => '2222199100222',
            'telephone' => '78 111 22 33',
            'numero_lot' => '8888',
            'date_excel' => '14.04.2026',
            'matched' => false,
            'statut' => 'en_attente',
        ]);

        // ===== MUTATIONS DÉJÀ TRAITÉES =====
        // 5 mutations validées (sur un autre import)
        $import2 = Import::create([
            'nom_fichier' => 'MUTATIONS_CITE_RELIGIEUSE_15032026.xlsx',
            'fichier_path' => 'imports/test_file2.xlsx',
            'projet_id' => $projets[1]->id,
            'imported_by' => $operateur->id,
            'statut' => 'termine',
            'total_lignes' => 8,
            'lignes_traitees' => 8,
            'lignes_matchees' => 8,
        ]);

        $mutationCounter = 1;
        $crParcelles = Parcelle::where('projet_id', $projets[1]->id)->take(5)->get();
        foreach ($crParcelles as $p) {
            $ancienProp = $p->proprietaire;
            $nouveauPropIdx = rand(0, count($proprietaires) - 1);
            $nouveauProp = Proprietaire::create([
                'civilite' => ['Monsieur', 'Madame'][rand(0, 1)],
                'prenom' => ['Alioune', 'Seynabou', 'Malick', 'Coumba', 'Daouda'][$mutationCounter - 1],
                'nom' => ['NIANG', 'TALL', 'DIAW', 'SAMB', 'LY'][$mutationCounter - 1],
                'nin' => '1' . rand(100000000, 999999999) . rand(100, 999),
                'cni_passport' => '1' . rand(100000000, 999999999) . rand(100, 999),
                'telephone' => '7' . rand(0, 8) . ' ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
            ]);

            Mutation::create([
                'parcelle_id' => $p->id,
                'ancien_proprietaire_id' => $ancienProp?->id,
                'nouveau_proprietaire_id' => $nouveauProp->id,
                'import_id' => $import2->id,
                'statut' => 'validee',
                'ref_lettre' => 'LM-2026-' . str_pad($mutationCounter, 4, '0', STR_PAD_LEFT),
                'numero_notification' => str_pad($mutationCounter, 7, '0', STR_PAD_LEFT),
                'date_mutation' => now()->subDays(rand(5, 30)),
                'validated_by' => $receveur->id,
                'validated_at' => now()->subDays(rand(5, 30)),
            ]);

            $p->update(['proprietaire_id' => $nouveauProp->id]);
            $mutationCounter++;
        }

        // 3 mutations refusées
        $crParcelles2 = Parcelle::where('projet_id', $projets[1]->id)->skip(5)->take(3)->get();
        $motifsRefus = [
            'Le terrain appartient à l\'État, mutation non autorisée.',
            'Documents d\'identité non conformes, pièces manquantes.',
            'Litige en cours sur cette parcelle, mutation suspendue.',
        ];
        foreach ($crParcelles2 as $idx => $p) {
            Mutation::create([
                'parcelle_id' => $p->id,
                'ancien_proprietaire_id' => $p->proprietaire?->id,
                'import_id' => $import2->id,
                'statut' => 'refusee',
                'motif_refus' => $motifsRefus[$idx],
                'date_mutation' => now()->subDays(rand(5, 20)),
                'validated_by' => $receveur->id,
                'validated_at' => now()->subDays(rand(5, 20)),
            ]);
            $mutationCounter++;
        }

        // ===== MUTATION AVEC HISTORIQUE MULTIPLE (même parcelle mutée 3 fois) =====
        $parcelleHistorique = Parcelle::where('numero_lot', '3910')->where('projet_id', $projets[0]->id)->first();
        if ($parcelleHistorique) {
            $prop1 = $parcelleHistorique->proprietaire;

            $prop2 = Proprietaire::create([
                'civilite' => 'Monsieur', 'prenom' => 'El Hadji', 'nom' => 'SALL',
                'nin' => '1999198800111', 'cni_passport' => '1999198800111', 'telephone' => '77 111 22 33',
            ]);
            Mutation::create([
                'parcelle_id' => $parcelleHistorique->id,
                'ancien_proprietaire_id' => $prop1?->id,
                'nouveau_proprietaire_id' => $prop2->id,
                'statut' => 'validee',
                'numero_notification' => str_pad($mutationCounter++, 7, '0', STR_PAD_LEFT),
                'date_mutation' => now()->subMonths(8),
                'validated_by' => $receveur->id,
                'validated_at' => now()->subMonths(8),
            ]);

            $prop3 = Proprietaire::create([
                'civilite' => 'Madame', 'prenom' => 'Diary', 'nom' => 'THIAM',
                'nin' => '2888199200222', 'cni_passport' => '2888199200222', 'telephone' => '78 333 44 55',
            ]);
            Mutation::create([
                'parcelle_id' => $parcelleHistorique->id,
                'ancien_proprietaire_id' => $prop2->id,
                'nouveau_proprietaire_id' => $prop3->id,
                'statut' => 'validee',
                'numero_notification' => str_pad($mutationCounter++, 7, '0', STR_PAD_LEFT),
                'date_mutation' => now()->subMonths(3),
                'validated_by' => $receveur->id,
                'validated_at' => now()->subMonths(3),
            ]);

            $prop4 = Proprietaire::create([
                'civilite' => 'Monsieur', 'prenom' => 'Serigne', 'nom' => 'MBACKE',
                'nin' => '1777198500333', 'cni_passport' => '1777198500333', 'telephone' => '76 555 66 77',
            ]);
            $mutationRecente = Mutation::create([
                'parcelle_id' => $parcelleHistorique->id,
                'ancien_proprietaire_id' => $prop3->id,
                'nouveau_proprietaire_id' => $prop4->id,
                'statut' => 'validee',
                'numero_notification' => str_pad($mutationCounter++, 7, '0', STR_PAD_LEFT),
                'date_mutation' => now()->subDays(10),
                'validated_by' => $receveur->id,
                'validated_at' => now()->subDays(10),
            ]);

            $parcelleHistorique->update(['proprietaire_id' => $prop4->id]);

            // Demande d'annulation en attente sur la dernière mutation
            MutationAnnulation::create([
                'mutation_id' => $mutationRecente->id,
                'demande_par' => $receveur->id,
                'motif' => 'Erreur de saisie sur le nouveau propriétaire, le CNI ne correspond pas au nom indiqué.',
            ]);
        }

        // ===== MUTATION ANNULÉE =====
        $parcelleAnnulee = Parcelle::where('numero_lot', '3920')->where('projet_id', $projets[0]->id)->first();
        if ($parcelleAnnulee) {
            $ancienPropAnnul = $parcelleAnnulee->proprietaire;
            $nouveauPropAnnul = Proprietaire::create([
                'civilite' => 'Monsieur', 'prenom' => 'Aliou', 'nom' => 'NDOYE',
                'nin' => '1555198800444', 'cni_passport' => '1555198800444', 'telephone' => '70 777 88 99',
            ]);

            $mutAnnulee = Mutation::create([
                'parcelle_id' => $parcelleAnnulee->id,
                'ancien_proprietaire_id' => $ancienPropAnnul?->id,
                'nouveau_proprietaire_id' => $nouveauPropAnnul->id,
                'statut' => 'annulee',
                'numero_notification' => str_pad($mutationCounter++, 7, '0', STR_PAD_LEFT),
                'date_mutation' => now()->subDays(15),
                'validated_by' => $receveur->id,
                'validated_at' => now()->subDays(15),
            ]);

            MutationAnnulation::create([
                'mutation_id' => $mutAnnulee->id,
                'demande_par' => $receveur->id,
                'approuve_par' => $admin->id,
                'statut' => 'approuvee',
                'motif' => 'Mutation effectuée par erreur, le promoteur a fourni un mauvais dossier.',
                'approuve_at' => now()->subDays(12),
            ]);
        }

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('  - 7 communes');
        $this->command->info('  - 5 projets');
        $this->command->info('  - ' . Proprietaire::count() . ' propriétaires');
        $this->command->info('  - ' . Parcelle::count() . ' parcelles');
        $this->command->info('  - 2 imports');
        $this->command->info('  - ' . Mutation::count() . ' mutations (validées, refusées, annulées)');
        $this->command->info('  - ' . MutationAnnulation::count() . ' demandes d\'annulation');
        $this->command->info('');
        $this->command->info('Comptes utilisateurs :');
        $this->command->info('  admin@domaines.sn / password (admin)');
        $this->command->info('  receveur@domaines.sn / password (receveur)');
        $this->command->info('  operateur@domaines.sn / password (opérateur)');
    }
}
