<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mutation extends Model
{
    protected $fillable = [
        'parcelle_id', 'ancien_proprietaire_id', 'nouveau_proprietaire_id',
        'import_id', 'statut', 'ref_lettre', 'code_paye', 'type_piece',
        'demandeur_prenom', 'demandeur_nom', 'demandeur_telephone',
        'numero_notification', 'code_verification',
        'date_mutation', 'motif_refus', 'observation', 'validated_by', 'validated_at',
    ];

    protected $casts = [
        'date_mutation' => 'date',
        'validated_at' => 'datetime',
    ];

    public function parcelle(): BelongsTo
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function ancienProprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class, 'ancien_proprietaire_id');
    }

    public function nouveauProprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class, 'nouveau_proprietaire_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function annulation(): HasOne
    {
        return $this->hasOne(MutationAnnulation::class);
    }

    public static function genererNumeroNotification(int $projetId = null): string
    {
        // Lock-based pour éviter race condition lors d'appels concurrents.
        return \DB::transaction(function () use ($projetId) {
            $query = static::whereNotNull('numero_notification');

            if ($projetId) {
                $query->whereHas('parcelle', fn($q) => $q->where('projet_id', $projetId));
            }

            // lockForUpdate (sur le row du dernier) — sur SQLite c'est best-effort,
            // sur MySQL/Postgres ça verrouille effectivement.
            $dernier = $query->orderByDesc('id')->lockForUpdate()->value('numero_notification');

            $numero = $dernier ? (intval(preg_replace('/\D/', '', $dernier)) + 1) : 1;
            $candidat = str_pad($numero, 7, '0', STR_PAD_LEFT);

            // Garantie d'unicité en cas de collision résiduelle
            while (static::where('numero_notification', $candidat)->exists()) {
                $numero++;
                $candidat = str_pad($numero, 7, '0', STR_PAD_LEFT);
            }

            return $candidat;
        });
    }

    /**
     * Générer un code de vérification hashé unique pour authentifier le document.
     * Hash = SHA-256 de (id + notification + lot + proprietaire + date + clé secrète)
     */
    public function genererCodeVerification(): string
    {
        $this->loadMissing('parcelle', 'nouveauProprietaire');

        $data = implode('|', [
            $this->id,
            $this->numero_notification,
            $this->parcelle->numero_lot ?? '',
            $this->parcelle->projet_id ?? '',
            $this->nouveau_proprietaire_id ?? '',
            $this->nouveauProprietaire?->cni_passport ?? '',
            $this->date_mutation?->format('Y-m-d') ?? '',
        ]);

        // HMAC-SHA256 avec la clé secrète Laravel (la clé n'est pas concaténée au message)
        $key = config('app.key');
        // Si APP_KEY est au format "base64:xxx", décoder pour avoir les vrais bytes
        if (is_string($key) && str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $hash = hash_hmac('sha256', $data, (string) $key);
        $this->update(['code_verification' => $hash]);

        return $hash;
    }

    /**
     * Indique s'il s'agit d'une première ATTRIBUTION (pas d'ancien propriétaire)
     * plutôt que d'une MUTATION (transfert entre proprios).
     */
    public function getEstAttributionAttribute(): bool
    {
        return is_null($this->ancien_proprietaire_id);
    }

    public function getTypeLibelleAttribute(): string
    {
        return $this->est_attribution ? 'Attribution' : 'Mutation';
    }

    /**
     * URL publique de vérification pour le QR code.
     */
    public function getUrlVerificationAttribute(): string
    {
        return url('/verification/' . $this->code_verification);
    }

    /**
     * Format imprimé de la pièce d'identité.
     * - CNI       : "CNI_{code_paye} n° : xxxxxxxx"  (ex: CNI_SN n° : 1234567890, CNI_MLI n° : ...)
     * - Passeport : "PP_{code_paye}  n° : xxxxxxxx"  (ex: PP_SN  n° : 1234567890, PP_MLI  n° : ...)
     */
    public function getPieceFormateeAttribute(): string
    {
        $this->loadMissing('nouveauProprietaire');
        $numero = $this->nouveauProprietaire?->cni_passport;
        if (!$numero) {
            return '';
        }

        $type = trim((string) ($this->type_piece ?: $this->nouveauProprietaire?->type_piece ?? ''));
        $prefixType = stripos($type, 'pass') === 0 ? 'PP' : ($type !== '' ? $type : '');

        if ($prefixType === '') {
            return $this->code_paye ? "{$this->code_paye} n° : {$numero}" : "n° : {$numero}";
        }

        $prefix = $this->code_paye ? "{$prefixType}_{$this->code_paye}" : $prefixType;
        return "{$prefix} n° : {$numero}";
    }

    /**
     * Vérifier l'authenticité d'un document par son hash.
     */
    public static function verifierDocument(string $hash): ?self
    {
        return static::where('code_verification', $hash)->first();
    }
}
