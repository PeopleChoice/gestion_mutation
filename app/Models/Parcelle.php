<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcelle extends Model
{
    protected $fillable = [
        'numero_lot', 'projet_id', 'proprietaire_id', 'ref_lettre',
        'date_attribution', 'superficie', 'usage', 'observation',
        'geometrie', 'centroid_lat', 'centroid_lng',
    ];

    protected $casts = [
        'date_attribution' => 'date',
        'geometrie' => 'array',
    ];

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class);
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(Mutation::class);
    }

    public function getDesignationAttribute(): string
    {
        return "Lot {$this->numero_lot} - {$this->projet->nom}";
    }
}
