<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Projet extends Model
{
    protected $fillable = ['nom', 'code', 'type_lotissement', 'commune_id', 'description', 'latitude', 'longitude', 'fichier_dxf', 'geojson'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected static function booted(): void
    {
        static::creating(function (Projet $projet) {
            if (empty($projet->code)) {
                $projet->code = static::genererCode($projet->nom);
            }
        });
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function parcelles(): HasMany
    {
        return $this->hasMany(Parcelle::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /**
     * Génère un code unique au format "AB1234" :
     * - 2 lettres dérivées du nom du projet (initiales des 2 premiers mots, ou 2 premières lettres si un seul mot)
     * - 4 chiffres séquentiels uniques
     */
    public static function genererCode(?string $nom = null): string
    {
        $nom = trim((string) $nom);
        $nom = preg_replace('/[^A-Za-z\s]/', '', $nom);
        $mots = preg_split('/\s+/', strtoupper($nom), -1, PREG_SPLIT_NO_EMPTY);

        if (count($mots) >= 2) {
            $prefix = substr($mots[0], 0, 1) . substr($mots[1], 0, 1);
        } elseif (count($mots) === 1) {
            $prefix = substr($mots[0], 0, 2);
        } else {
            $prefix = 'PR';
        }
        $prefix = str_pad($prefix, 2, 'X');

        // Trouver le prochain numéro libre pour ce préfixe
        $maxNum = static::where('code', 'like', $prefix . '%')
            ->get()
            ->map(fn($p) => intval(substr($p->code, 2)))
            ->max() ?? 0;
        $num = $maxNum + 1;

        $code = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);

        // Garantie d'unicité (au cas où concurrence)
        while (static::where('code', $code)->exists()) {
            $num++;
            $code = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
