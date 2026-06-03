<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLigne extends Model
{
    protected $fillable = [
        'import_id', 'numero_ordre', 'civilite', 'prenom', 'nom',
        'type_piece', 'code_paye', 'cni_passport',
        'demandeur_prenom', 'demandeur_nom', 'demandeur_telephone',
        'ninea', 'telephone', 'numero_lot', 'ref_lettre',
        'date_excel', 'observation', 'precedent_attributaire',
        'parcelle_id', 'matched', 'statut',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function parcelle(): BelongsTo
    {
        return $this->belongsTo(Parcelle::class);
    }
}
