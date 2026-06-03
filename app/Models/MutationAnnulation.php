<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutationAnnulation extends Model
{
    protected $fillable = [
        'mutation_id', 'demande_par', 'approuve_par', 'statut',
        'motif', 'motif_rejet', 'approuve_at',
    ];

    protected $casts = [
        'approuve_at' => 'datetime',
    ];

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(Mutation::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demande_par');
    }

    public function approbateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approuve_par');
    }
}
