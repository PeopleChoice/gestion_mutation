<?php

namespace App\Http\Controllers;

use App\Models\Mutation;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Page publique de vérification par hash (résultat du scan).
     */
    public function verifier(string $hash)
    {
        $mutation = Mutation::verifierDocument($hash);

        if ($mutation) {
            $mutation->load('parcelle.projet.commune', 'nouveauProprietaire', 'ancienProprietaire', 'validateur');
        }

        return view('verification.resultat', [
            'mutation' => $mutation,
            'hash' => $hash,
            'authentique' => $mutation !== null,
            'isAuth' => auth()->check(),
        ]);
    }

    /**
     * Page de scan QR code (nécessite connexion).
     */
    public function scanner()
    {
        return view('verification.scanner');
    }

    /**
     * Vérification par saisie manuelle du code.
     */
    public function verifierManuel(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $hash = trim($request->code);
        $mutation = Mutation::verifierDocument($hash);

        if ($mutation) {
            $mutation->load('parcelle.projet.commune', 'nouveauProprietaire', 'ancienProprietaire', 'validateur');
        }

        // Données publiques : limiter l'exposition des données personnelles
        $isAuth = auth()->check();

        return response()->json([
            'authentique' => $mutation !== null,
            'mutation' => $mutation ? [
                'id' => $mutation->id,
                'numero_notification' => $mutation->numero_notification,
                'date_mutation' => $mutation->date_mutation?->format('d/m/Y'),
                'statut' => $mutation->statut,
                'lot' => $mutation->parcelle->numero_lot,
                'projet' => $mutation->parcelle->projet->nom,
                'commune' => $mutation->parcelle->projet->commune->nom,
                'nouveau_proprietaire' => $mutation->nouveauProprietaire?->nom_complet,
                'ancien_proprietaire' => $isAuth ? $mutation->ancienProprietaire?->nom_complet : null,
                'cni' => $isAuth ? $mutation->nouveauProprietaire?->cni_passport : substr($mutation->nouveauProprietaire?->cni_passport ?? '', 0, 4) . '****',
                'telephone' => $isAuth ? $mutation->nouveauProprietaire?->telephone : null,
                'validateur' => $isAuth ? $mutation->validateur?->name : null,
                'url' => $isAuth ? route('mutations.show', $mutation->id) : null,
            ] : null,
        ]);
    }
}
