<?php

namespace App\Models;

/**
 * Catalogue centralisé des placeholders disponibles pour les templates de documents.
 * Modifie cette liste pour ajouter/retirer un placeholder partout (édition + doc).
 */
class DocumentTemplatePlaceholders
{
    /**
     * Retourne un tableau groupé par catégorie :
     *   [
     *     'Catégorie' => [
     *       ['key' => 'placeholder', 'label' => 'Libellé', 'description' => '...'],
     *     ]
     *   ]
     */
    public static function all(): array
    {
        return [
            'Notification / Mutation' => [
                ['key' => 'numero_notification', 'label' => 'N° Notification',  'description' => 'Ex: 0000123'],
                ['key' => 'date_mutation',       'label' => 'Date mutation',    'description' => 'Format long fr : 27 mai 2026'],
                ['key' => 'date_jour',           'label' => 'Date du jour',     'description' => 'Date d\'émission du document'],
                ['key' => 'ref_lettre',          'label' => 'Réf. lettre',      'description' => 'Référence du courrier'],
                ['key' => 'piece_formatee',      'label' => 'Pièce formatée',   'description' => 'Ex: CNI_SN n° : 1234567'],
                ['key' => 'type_piece',          'label' => 'Type pièce',       'description' => 'CNI ou Passeport'],
                ['key' => 'code_paye',           'label' => 'Code payé',        'description' => 'Code pays (ex: SN)'],
            ],
            'Parcelle / Projet' => [
                ['key' => 'numero_lot', 'label' => 'N° Lot',   'description' => 'Numéro de la parcelle'],
                ['key' => 'nom_projet', 'label' => 'Projet',   'description' => 'Nom du lotissement'],
                ['key' => 'commune',    'label' => 'Commune',  'description' => 'Commune de rattachement'],
            ],
            'Nouveau propriétaire (attributaire)' => [
                ['key' => 'civilite_nouveau',       'label' => 'Civilité',         'description' => 'Monsieur / Madame / Société'],
                ['key' => 'prenom_nouveau',        'label' => 'Prénom',           'description' => ''],
                ['key' => 'nom_nouveau',           'label' => 'Nom',              'description' => ''],
                ['key' => 'nom_complet_nouveau',   'label' => 'Nom complet',      'description' => 'Civilité + Prénom + Nom'],
                ['key' => 'cni_nouveau',           'label' => 'N° pièce',         'description' => 'CNI / passeport numéro'],
                ['key' => 'type_piece_nouveau',    'label' => 'Type pièce',       'description' => ''],
                ['key' => 'telephone_nouveau',     'label' => 'Téléphone',        'description' => ''],
                ['key' => 'ninea_nouveau',         'label' => 'NINEA',            'description' => 'Pour les sociétés'],
                ['key' => 'adresse_nouveau',      'label' => 'Adresse',          'description' => ''],
                ['key' => 'piece_formatee_nouveau','label' => 'Pièce formatée',  'description' => 'Format complet pièce'],
            ],
            'Ancien propriétaire (précédent)' => [
                ['key' => 'civilite_ancien',       'label' => 'Civilité',     'description' => ''],
                ['key' => 'nom_complet_ancien',    'label' => 'Nom complet',  'description' => ''],
                ['key' => 'cni_ancien',            'label' => 'N° pièce',     'description' => ''],
                ['key' => 'type_piece_ancien',     'label' => 'Type pièce',   'description' => ''],
                ['key' => 'telephone_ancien',      'label' => 'Téléphone',    'description' => ''],
                ['key' => 'piece_formatee_ancien', 'label' => 'Pièce formatée', 'description' => ''],
            ],
            'Demandeur (mutation seulement)' => [
                ['key' => 'demandeur_prenom',     'label' => 'Prénom',     'description' => ''],
                ['key' => 'demandeur_nom',        'label' => 'Nom',        'description' => ''],
                ['key' => 'demandeur_telephone',  'label' => 'Téléphone',  'description' => ''],
            ],
            'Administration / Signataire' => [
                ['key' => 'chef_bureau',  'label' => 'Chef de bureau', 'description' => 'Nom du validateur'],
                ['key' => 'centre_fiscal', 'label' => 'Centre fiscal', 'description' => 'Du template'],
                ['key' => 'bureau',       'label' => 'Bureau',         'description' => 'Du template'],
            ],
            'Visuels / Vérification' => [
                ['key' => 'entete_image',       'label' => 'Image d\'en-tête', 'description' => 'Chemin local de l\'image officielle'],
                ['key' => 'qr_code',            'label' => 'QR code',          'description' => 'Image PNG du QR généré'],
                ['key' => 'code_verification',  'label' => 'Code de vérification', 'description' => 'Hash SHA-256 unique'],
            ],
        ];
    }

    /** Liste plate de tous les placeholders (pour JS / docs) */
    public static function flat(): array
    {
        $out = [];
        foreach (self::all() as $cat => $items) {
            foreach ($items as $item) {
                $out[] = $item + ['category' => $cat];
            }
        }
        return $out;
    }
}