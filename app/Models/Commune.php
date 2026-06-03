<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commune extends Model
{
    protected $fillable = ['nom', 'departement', 'region'];

    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
    }
}
