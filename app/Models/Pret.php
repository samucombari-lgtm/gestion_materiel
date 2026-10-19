<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle de la table "prets" (associations DEMANDER, VALIDER, CONCERNER).
 *
 * Version minimale, utilisée par PretPolicy. Les écrans arrivent à l'étape 6.
 *
 * Cycle d'un prêt : en_attente → valide ou refuse → sorti → rendu
 *                   (ou annule tant qu'il n'est pas sorti).
 */
class Pret extends Model
{
    public const EN_ATTENTE = 'en_attente';
    public const VALIDE = 'valide';
    public const REFUSE = 'refuse';
    public const SORTI = 'sorti';
    public const RENDU = 'rendu';
    public const ANNULE = 'annule';

    protected $table = 'prets';

    /**
     * Volontairement absents : validateur_id, statut, motif_refus, date_retour,
     * etat_retour. Ils changent seulement par les actions valider / sortir /
     * rendre, jamais par un formulaire envoyé tel quel.
     */
    protected $fillable = ['materiel_id', 'demandeur_id', 'date_debut', 'date_fin_prevue'];

    protected function casts(): array
    {
        // Les clés en entier : les Policies les comparent avec === à $utilisateur->id.
        return [
            'materiel_id' => 'integer',
            'demandeur_id' => 'integer',
            'validateur_id' => 'integer',
            'date_debut' => 'date',
            'date_fin_prevue' => 'date',
            'date_retour' => 'date',
        ];
    }

    public function materiel(): BelongsTo
    {
        return $this->belongsTo(Materiel::class, 'materiel_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'demandeur_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'validateur_id');
    }
}
