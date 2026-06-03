<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $fillable = [
        'nom_fichier', 'fichier_path', 'projet_id', 'imported_by',
        'statut', 'type', 'total_lignes', 'lignes_traitees', 'lignes_matchees',
    ];

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function importeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(ImportLigne::class);
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(Mutation::class);
    }
}
