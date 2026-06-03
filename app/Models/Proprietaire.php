<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proprietaire extends Model
{
    protected $fillable = ['civilite', 'prenom', 'nom', 'nin', 'ninea', 'cni_passport', 'type_piece', 'telephone', 'adresse'];

    public function parcelles(): HasMany
    {
        return $this->hasMany(Parcelle::class);
    }

    public function getNomCompletAttribute(): string
    {
        if ($this->civilite === 'Société') {
            return $this->prenom; // Pour les sociétés, le prénom contient le nom complet
        }
        return trim("{$this->civilite} {$this->prenom} {$this->nom}");
    }

    public function getIdentifiantAttribute(): string
    {
        return $this->cni_passport ?: $this->nin ?: $this->ninea ?: 'N/A';
    }

    /**
     * Format imprimé de la pièce d'identité, aligné sur Mutation::piece_formatee.
     * - CNI       : "CNI_{code_paye} n° xxxxxxxx"
     * - Passeport : "PP_{code_paye}  n° xxxxxxxx"
     * (code_paye récupéré sur la dernière mutation validée)
     */
    public function getPieceFormateeAttribute(): string
    {
        if (!$this->cni_passport) {
            return '';
        }

        $type = trim((string) $this->type_piece);
        $prefixType = stripos($type, 'pass') === 0 ? 'PP' : ($type !== '' ? $type : '');

        $codePaye = Mutation::where('nouveau_proprietaire_id', $this->id)
            ->whereNotNull('code_paye')
            ->latest('id')
            ->value('code_paye');

        if ($prefixType === '') {
            return $codePaye ? "{$codePaye} n° : {$this->cni_passport}" : "n° : {$this->cni_passport}";
        }

        $prefix = $codePaye ? "{$prefixType}_{$codePaye}" : $prefixType;
        return "{$prefix} n° : {$this->cni_passport}";
    }
}
