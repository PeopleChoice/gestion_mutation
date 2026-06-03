<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'nom', 'type', 'entete_html', 'corps_html', 'pied_html',
        'centre_fiscal', 'bureau', 'actif',
    ];

    public function renderPourMutation(Mutation $mutation): string
    {
        $parcelle = $mutation->parcelle;
        $projet = $parcelle->projet;
        $commune = $projet->commune;
        $nouveauProp = $mutation->nouveauProprietaire;
        $ancienProp = $mutation->ancienProprietaire;

        // Générer le code de vérification si absent
        if (!$mutation->code_verification) {
            $mutation->genererCodeVerification();
        }

        // Générer le QR code en base64 (fichier unique par mutation pour éviter les races)
        $qrBase64 = $this->genererQrCodeBase64($mutation->url_verification, 'qr_mutation_' . $mutation->id);

        $placeholders = [
            '{{numero_notification}}' => $mutation->numero_notification,
            '{{date_mutation}}' => $mutation->date_mutation
                ? $mutation->date_mutation->locale('fr')->translatedFormat('d F Y')
                : '',
            '{{numero_lot}}' => $parcelle->numero_lot,
            '{{nom_projet}}' => $projet->nom,
            '{{commune}}' => $commune->nom,
            '{{civilite_nouveau}}' => $nouveauProp?->civilite ?? '',
            '{{prenom_nouveau}}' => $nouveauProp?->prenom ?? '',
            '{{nom_nouveau}}' => $nouveauProp?->nom ?? '',
            '{{nom_complet_nouveau}}' => $nouveauProp?->nom_complet ?? '',
            '{{cni_nouveau}}' => $nouveauProp?->cni_passport ?? '',
            '{{type_piece_nouveau}}' => $nouveauProp?->type_piece ?? '',
            '{{telephone_nouveau}}' => $nouveauProp?->telephone ?? '',
            '{{nin_nouveau}}' => $nouveauProp?->nin ?? '',
            '{{ninea_nouveau}}' => $nouveauProp?->ninea ?? '',
            '{{adresse_nouveau}}' => $nouveauProp?->adresse ?? '',
            '{{civilite_ancien}}' => $ancienProp?->civilite ?? '',
            '{{nom_complet_ancien}}' => $ancienProp?->nom_complet ?? '',
            '{{cni_ancien}}' => $ancienProp?->cni_passport ?? '',
            '{{type_piece_ancien}}' => $ancienProp?->type_piece ?? '',
            '{{telephone_ancien}}' => $ancienProp?->telephone ?? '',
            '{{code_paye}}' => $mutation->code_paye ?? '',
            '{{type_piece}}' => $mutation->type_piece ?? '',
            '{{piece_formatee}}' => $mutation->piece_formatee,
            '{{demandeur_prenom}}' => $mutation->demandeur_prenom ?? '',
            '{{demandeur_nom}}' => $mutation->demandeur_nom ?? '',
            '{{demandeur_telephone}}' => $mutation->demandeur_telephone ?? '',
            '{{ref_lettre}}' => $mutation->ref_lettre ?? '',
            '{{centre_fiscal}}' => $this->centre_fiscal ?? '',
            '{{bureau}}' => $this->bureau ?? '',
            '{{date_jour}}' => now()->locale('fr')->translatedFormat('d F Y'),
            '{{chef_bureau}}' => $mutation->validateur?->name ?? '',
            '{{entete_image}}' => public_path('images/entete_dgid.png'),
            '{{qr_code}}' => $qrBase64,
            '{{code_verification}}' => $mutation->code_verification,
        ];

        $html = $this->entete_html . $this->corps_html . ($this->pied_html ?? '');

        return str_replace(array_keys($placeholders), array_values($placeholders), $html);
    }

    /**
     * Générer un QR code PNG via la matrice brute de BaconQrCode.
     * Le paramètre $fileKey rend le fichier de sortie unique pour éviter
     * les race conditions quand plusieurs PDFs sont générés en parallèle
     * (ex. fusion PDFs d'un import).
     */
    protected function genererQrCodeBase64(string $data, string $fileKey = 'qr_temp'): string
    {
        $pngPath = storage_path('app/' . preg_replace('/[^A-Za-z0-9_-]/', '_', $fileKey) . '.png');

        $writer = new \BaconQrCode\Writer(
            new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300, 1),
                new \BaconQrCode\Renderer\Image\EpsImageBackEnd()
            )
        );

        // Utiliser la matrice pour dessiner en GD
        $encoder = new \BaconQrCode\Encoder\Encoder();
        $qrCode = \BaconQrCode\Encoder\Encoder::encode(
            $data,
            \BaconQrCode\Common\ErrorCorrectionLevel::M(),
            'UTF-8'
        );

        $matrix = $qrCode->getMatrix();
        $matrixWidth = $matrix->getWidth();
        $scale = 10;
        $size = $matrixWidth * $scale;

        $img = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $matrixWidth; $y++) {
            for ($x = 0; $x < $matrixWidth; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $img,
                        $x * $scale, $y * $scale,
                        ($x + 1) * $scale - 1, ($y + 1) * $scale - 1,
                        $black
                    );
                }
            }
        }

        imagepng($img, $pngPath);
        imagedestroy($img);

        return $pngPath;
    }
}
