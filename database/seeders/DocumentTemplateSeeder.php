<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DocumentTemplate::updateOrCreate(
            ['type' => 'notification_attribution'],
            [
                'nom' => 'Notification d\'attribution standard',
                'centre_fiscal' => 'Centre des Services Fiscaux de TIVAOUANE',
                'bureau' => 'Bureau des Domaines',
                'entete_html' => '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    body { font-family: Garamond, "EB Garamond", "Adobe Garamond Pro", "Times New Roman", serif; font-size: 12pt; color: #000; line-height: 1.25; margin: 0; padding: 0; background-color: #fff; }
    .page-wrapper { padding: 8px 40px 10px 40px; background-color: #fff; border: 3px double #000; box-sizing: border-box; position: absolute; top: 4mm; left: 4mm; right: 4mm; bottom: 4mm; }
    .header-table { width: 100%; margin-bottom: 2px; }
    .header-left { width: 55%; vertical-align: top; font-size: 9.5pt; line-height: 1.3; text-align: left; }
    .header-right { width: 45%; vertical-align: top; text-align: right; font-size: 11pt; }
    .header-sep { border: none; border-top: 2px solid #000; margin: 4px auto; width: 90%; }
    .republique { font-size: 11pt; font-weight: bold; font-style: italic; letter-spacing: 0.5px; }
    .devise { font-size: 9.5pt; font-style: italic; }
    .ministere { font-size: 10pt; font-weight: bold; }
    .direction-generale { font-size: 9pt; font-weight: bold; }
    .direction { font-size: 10pt; font-weight: bold; }
    .centre-fiscal { font-size: 10pt; font-weight: bold; font-style: italic; }
    .centre-fiscal-sep { border: none; border-top: 1.5px solid #000; margin: 2px auto; width: 85%; }
    .bureau-label { font-size: 10pt; font-weight: bold; font-style: italic; }
    .date-lieu { font-size: 11pt; color: #CC0000; font-weight: bold; }
    .chef-titre { font-size: 11pt; font-weight: bold; margin-top: 10px; }
    .numero { font-size: 17pt; font-weight: bold; color: #CC0000; margin: 0 0 8px 25px; padding: 4px 18px; background-color: #D1D5DB; display: inline-block; border-radius: 3px; }
    .objet { font-size: 11pt; font-weight: bold; margin-bottom: 6px; }
    .objet table { border-collapse: collapse; }
    .objet td { vertical-align: top; padding: 0; }
    .objet td.objet-label { white-space: nowrap; padding-right: 6px; }
    .objet td.objet-content { text-align: justify; font-weight: bold; }
    .corps { font-size: 11pt; text-align: justify; line-height: 1.3; }
    .corps p { margin-bottom: 4px; }
    .signature { text-align: right; margin-top: 8px; font-size: 12pt; font-weight: bold; }
    .destinataire { margin-top: 12px; font-size: 11pt; font-weight: bold; font-style: italic; }
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
        </td>
    </tr>
</table>',
                'corps_html' => '
<div class="numero">N° {{numero_notification}}</div>

<div class="objet">
    <table><tr>
        <td class="objet-label">Objet :</td>
        <td class="objet-content">Notification d\'attribution du lot n° {{numero_lot}} du plan de lotissement dit &laquo; {{nom_projet}} &raquo;, Commune de {{commune}}</td>
    </tr></table>
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
</div>',
                'pied_html' => '
<div class="destinataire">
    <p>à</p>
    <br>
    <p>{{civilite_nouveau}} {{prenom_nouveau}} {{nom_nouveau}}</p>
    <p>{{piece_formatee}}</p>
    <p>Téléphone : {{telephone_nouveau}}</p>
</div>

</div>
</body>
</html>',
                'actif' => true,
            ]
        );
    }
}
