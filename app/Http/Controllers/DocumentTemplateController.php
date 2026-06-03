<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;

class DocumentTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentTemplate::query()->orderBy('actif', 'desc')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('nom', 'like', "%{$s}%")
                ->orWhere('centre_fiscal', 'like', "%{$s}%")
                ->orWhere('bureau', 'like', "%{$s}%"));
        }

        $templates = $query->get();
        $types = DocumentTemplate::query()->distinct()->pluck('type');

        // Compteur d'utilisation : nombre de mutations associées à chaque type
        // (utile pour savoir qu'un template est en production)
        $usageParType = DocumentTemplate::query()
            ->selectRaw('type, (SELECT COUNT(*) FROM mutations WHERE statut = ?) AS usage')
            ->setBindings(['validee'])
            ->groupBy('type')
            ->pluck('usage', 'type');

        return view('templates.index', compact('templates', 'types', 'usageParType'));
    }

    public function create()
    {
        // Charger le format par défaut
        $defaut = $this->getDefaut();
        return view('templates.create', compact('defaut'));
    }

    /**
     * Retourne le contenu par défaut du template standard.
     */
    protected function getDefaut(): array
    {
        $entete = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; line-height: 1.5; margin: 0; padding: 0; background-color: #E6FAF8; }
    .page-wrapper { padding: 50px 60px; }
    .header-table { width: 100%; margin-bottom: 15px; }
    .header-left { width: 55%; vertical-align: top; font-size: 9.5pt; line-height: 1.3; text-align: left; }
    .header-right { width: 45%; vertical-align: top; text-align: right; font-size: 11pt; }
    .date-lieu { font-size: 11pt; color: #CC0000; font-weight: bold; }
    .chef-titre { font-size: 11pt; font-weight: bold; margin-top: 10px; }
    .numero { font-size: 22pt; font-weight: bold; color: #CC0000; margin: 20px 0 15px 30px; }
    .objet { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
    .corps { font-size: 11pt; text-align: justify; line-height: 1.6; }
    .corps p { margin-bottom: 12px; }
    .signature { text-align: right; margin-top: 40px; font-size: 11pt; font-weight: bold; }
    .destinataire { margin-top: 50px; font-size: 12pt; font-weight: bold; font-style: italic; }
    .destinataire p { margin: 2px 0; }
</style>
</head>
<body>
<div class="page-wrapper">

<table class="header-table" cellpadding="0" cellspacing="0">
    <tr>
        <td class="header-left">
            <img src="{{entete_image}}" style="width: 260px; height: auto;">
        </td>
        <td class="header-right">
            <span style="font-size:11pt; font-weight:bold; color:#000;">Tivaouane, le </span><span style="font-size:11pt; font-weight:bold; color:#CC0000;">{{date_jour}}</span><br><br>
            <span class="chef-titre">Le Chef du Bureau</span><br><br>
            <div style="display:inline-block; background:#fff; padding:5px; border:1px solid #ccc;">
                <img src="{{qr_code}}" style="width:90px; height:90px;">
            </div>
            <div style="font-size:7pt; color:#666; margin-top:3px;">Scannez pour vérifier</div>
        </td>
    </tr>
</table>';

        $corps = '
<div class="numero">N°{{numero_notification}}</div>

<div class="objet">
    <strong>Objet :</strong> Notification d\'attribution du lot n° AT {{numero_lot}} du plan de lotissement dit<br>
    &laquo; {{nom_projet}} &raquo;, Commune de {{commune}}
</div>

<div class="corps">
    <p>{{civilite_nouveau}},</p>

    <p>J\'ai l\'honneur de porter à votre connaissance, en application des dispositions de l\'article 301 de la loi n° 2013-10 du 28 décembre 2013, modifiée, portant Code général des Collectivités territoriales et conformément aux délibérations de la Commission de concertation sur les lotissements de régularisation, en sa séance du {{date_mutation}}, que vous êtes attributaire du lot visé en objet, issu du lotissement dit &laquo; {{nom_projet}} &raquo;, dans la Commune de {{commune}}.</p>

    <p>À ce titre, vous pouvez, à compter de la réception de la présente notification, déposer une demande de bail auprès de mes services.</p>

    <p>Je précise que cette attribution est personnelle. La parcelle ne peut être ni vendue, ni cédée sans l\'autorisation de l\'Administration. De même, vous avez l\'obligation de mettre en valeur la parcelle, dans un délai déterminé, à compter de la date de cette notification.</p>

    <p>Je vous prie de croire, <strong>{{civilite_nouveau}}</strong>, à mes sentiments distingués.</p>
</div>

<div class="signature">
    {{chef_bureau}}
</div>';

        $pied = '
<div class="destinataire">
    <p>à</p>
    <br>
    <p>{{civilite_nouveau}} {{prenom_nouveau}} {{nom_nouveau}}</p>
    <p>CNI n° {{cni_nouveau}}</p>
    <p>Téléphone : {{telephone_nouveau}}</p>
</div>

</div>
</body>
</html>';

        return [
            'nom' => 'Notification d\'attribution standard',
            'type' => 'notification_attribution',
            'centre_fiscal' => 'Centre des Services Fiscaux de TIVAOUANE',
            'bureau' => 'Bureau des Domaines',
            'entete_html' => $entete,
            'corps_html' => $corps,
            'pied_html' => $pied,
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'entete_html' => 'required|string',
            'corps_html' => 'required|string',
            'pied_html' => 'nullable|string',
            'centre_fiscal' => 'nullable|string|max:255',
            'bureau' => 'nullable|string|max:255',
        ]);

        DocumentTemplate::create($validated);

        return redirect()->route('templates.index')->with('success', 'Template créé avec succès.');
    }

    public function edit(DocumentTemplate $template)
    {
        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, DocumentTemplate $template)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'entete_html' => 'required|string',
            'corps_html' => 'required|string',
            'pied_html' => 'nullable|string',
            'centre_fiscal' => 'nullable|string|max:255',
            'bureau' => 'nullable|string|max:255',
            'actif' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('templates.index')->with('success', 'Template mis à jour.');
    }

    public function destroy(DocumentTemplate $template)
    {
        $template->delete();
        return redirect()->route('templates.index')->with('success', 'Template supprimé.');
    }

    /**
     * Bascule actif/inactif sans passer par l'éditeur.
     */
    public function toggleActif(DocumentTemplate $template)
    {
        $template->update(['actif' => !$template->actif]);
        $etat = $template->actif ? 'activé' : 'désactivé';
        \App\Models\ActivityLog::log('template_toggle', 'template',
            "Template « {$template->nom} » {$etat}",
            ['template_id' => $template->id, 'actif' => $template->actif]
        );
        return back()->with('success', "Template « {$template->nom} » {$etat}.");
    }

    /**
     * Duplique un template existant (utile pour partir d'une base sans casser l'original).
     */
    public function dupliquer(DocumentTemplate $template)
    {
        $copie = $template->replicate();
        $copie->nom = $template->nom . ' (copie)';
        $copie->actif = false;
        $copie->save();
        return redirect()->route('templates.edit', $copie)->with('success', 'Template dupliqué — édition en cours.');
    }

    public function preview(DocumentTemplate $template)
    {
        // Générer un QR code de preview dans public/images
        $qrPreviewPath = public_path('images/qr_preview.png');
        if (!file_exists($qrPreviewPath)) {
            $qrCode = \BaconQrCode\Encoder\Encoder::encode(
                url('/verification/preview-demo'),
                \BaconQrCode\Common\ErrorCorrectionLevel::M(),
                'UTF-8'
            );
            $matrix = $qrCode->getMatrix();
            $w = $matrix->getWidth();
            $scale = 10;
            $size = $w * $scale;
            $img = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefill($img, 0, 0, $white);
            for ($y = 0; $y < $w; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    if ($matrix->get($x, $y) === 1) {
                        imagefilledrectangle($img, $x * $scale, $y * $scale, ($x + 1) * $scale - 1, ($y + 1) * $scale - 1, $black);
                    }
                }
            }
            imagepng($img, $qrPreviewPath);
            imagedestroy($img);
        }

        $dateJour = now()->format('d') . ' ' . str_replace('.', '', mb_strtoupper(now()->translatedFormat('M'))) . ' ' . now()->format('Y');

        // Utiliser des URLs web (pas des chemins fichier) pour le navigateur
        $html = str_replace(
            [
                '{{numero_notification}}', '{{date_mutation}}', '{{numero_lot}}',
                '{{nom_projet}}', '{{commune}}', '{{civilite_nouveau}}',
                '{{prenom_nouveau}}', '{{nom_nouveau}}', '{{nom_complet_nouveau}}',
                '{{cni_nouveau}}', '{{type_piece_nouveau}}', '{{telephone_nouveau}}',
                '{{nin_nouveau}}', '{{ninea_nouveau}}', '{{adresse_nouveau}}',
                '{{civilite_ancien}}', '{{nom_complet_ancien}}',
                '{{cni_ancien}}', '{{type_piece_ancien}}', '{{telephone_ancien}}',
                '{{code_paye}}', '{{type_piece}}', '{{ref_lettre}}',
                '{{centre_fiscal}}', '{{bureau}}', '{{date_jour}}', '{{chef_bureau}}',
                '{{entete_image}}', '{{qr_code}}', '{{code_verification}}',
            ],
            [
                '0000001', '14/04/2026', 'AT 3900',
                'RAVIN', 'Mont-Rolland', 'Madame',
                'Xxxx', 'NGOM', 'Madame Xxxx NGOM',
                'xxxxxxxxxx', 'CNI', '77 561 xx 22',
                '1619197303906', '', 'Dakar, Sénégal',
                'Monsieur', 'Monsieur Abdoul SY',
                '1847200512345', 'CNI', '77 561 24 52',
                'CP-2026-001', 'CNI', 'LM-2026-0001',
                $template->centre_fiscal ?? 'Centre des Services Fiscaux', $template->bureau ?? 'Bureau des Domaines',
                $dateJour, 'Abdoul SY',
                asset('images/entete_dgid.png'), asset('images/qr_preview.png'), 'abc123preview',
            ],
            $template->entete_html . $template->corps_html . ($template->pied_html ?? '')
        );

        return view('templates.preview', compact('html', 'template'));
    }
}
