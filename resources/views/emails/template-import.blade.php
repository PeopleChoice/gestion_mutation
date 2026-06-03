<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; margin: 0; padding: 0; background: #f4f6f9; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0f6e63, #20D5C0); padding: 25px 30px; color: #fff; }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; font-size: 13px; opacity: 0.9; }
        .body { padding: 30px; }
        .body p { margin-bottom: 15px; }
        .info-box { background: #E6FAF8; border-left: 4px solid #20D5C0; padding: 15px; border-radius: 0 8px 8px 0; margin: 20px 0; }
        .info-box strong { display: block; margin-bottom: 5px; }
        .footer { background: #f8f9fa; padding: 20px 30px; font-size: 12px; color: #888; text-align: center; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Direction Générale des Impôts et des Domaines</h2>
            <p>Template d'import de mutations</p>
        </div>

        <div class="body">
            <p>Bonjour <strong>{{ $nomPromoteur }}</strong>,</p>

            <p>Veuillez trouver ci-joint le template Excel à remplir pour le projet suivant :</p>

            <div class="info-box">
                <strong>Projet : {{ $projet->nom }}</strong>
                Commune : {{ $projet->commune->nom }}<br>
                Type : {{ $projet->type_lotissement ?? 'N/A' }}
            </div>

            @if($messagePersonnalise)
                <p>{{ $messagePersonnalise }}</p>
            @endif

            <p><strong>Instructions :</strong></p>
            <ul>
                <li>Pour chaque lot, renseignez le <strong>nouveau attributaire</strong> (Civilité, Prénom, NOM, Type pièce, Code payé, N° pièce, Téléphone) <strong>et</strong> le <strong>demandeur</strong> (Prénom, NOM, Téléphone — souvent = précédent propriétaire)</li>
                <li>Ne modifiez pas les numéros de lot (colonne LOT)</li>
                <li>Respectez le format du fichier</li>
                <li>Renvoyez le fichier rempli par email ou déposez-le au bureau</li>
            </ul>

            <p>Cordialement,<br><strong>{{ auth()->user()->name ?? 'Le Bureau des Domaines' }}</strong></p>
        </div>

        <div class="footer">
            Direction Générale des Impôts et des Domaines - Système de Gestion des Mutations
        </div>
    </div>
</body>
</html>
