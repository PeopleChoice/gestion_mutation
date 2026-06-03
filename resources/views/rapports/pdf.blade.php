<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport des Mutations</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { font-size: 16px; text-align: center; color: #1a472a; }
        h2 { font-size: 12px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #1a472a; color: #fff; }
        .stats { margin: 15px 0; }
        .stats span { display: inline-block; margin-right: 20px; padding: 5px 10px; background: #f0f0f0; border-radius: 4px; }
        .badge-validee { color: #28a745; font-weight: bold; }
        .badge-refusee { color: #dc3545; font-weight: bold; }
        .badge-annulee { color: #6c757d; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <h1>RAPPORT DES MUTATIONS DE PARCELLES</h1>
    <p style="text-align:center; color:#666;">Généré le {{ now()->format('d/m/Y à H:i') }}</p>

    <div class="stats">
        <span>Total : <strong>{{ $stats['total'] }}</strong></span>
        <span>Validées : <strong style="color:#28a745;">{{ $stats['validees'] }}</strong></span>
        <span>Refusées : <strong style="color:#dc3545;">{{ $stats['refusees'] }}</strong></span>
        <span>Annulées : <strong style="color:#6c757d;">{{ $stats['annulees'] }}</strong></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Date</th>
                <th>Lot</th>
                <th>Projet</th>
                <th>Commune</th>
                <th>Ancien prop.</th>
                <th>Nouveau prop.</th>
                <th>Statut</th>
                <th>Motif refus</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutations as $m)
            <tr>
                <td>{{ $m->numero_notification ?? '-' }}</td>
                <td>{{ $m->date_mutation?->format('d/m/Y') }}</td>
                <td>{{ $m->parcelle->numero_lot }}</td>
                <td>{{ $m->parcelle->projet->nom }}</td>
                <td>{{ $m->parcelle->projet->commune->nom }}</td>
                <td>{{ $m->ancienProprietaire?->nom_complet ?? '-' }}</td>
                <td>{{ $m->nouveauProprietaire?->nom_complet ?? '-' }}</td>
                <td class="badge-{{ $m->statut }}">{{ ucfirst($m->statut) }}</td>
                <td>{{ $m->motif_refus ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Direction Générale des Impôts et des Domaines - Système de Gestion des Mutations
    </div>
</body>
</html>
