<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de la table "materiels" : l'inventaire (BF-10 à BF-12).
 *
 * Version minimale : les écrans de gestion arrivent à l'étape 5.
 */
class Materiel extends Model
{
    public const ETATS = ['disponible', 'prete', 'en_panne', 'en_reparation', 'archive'];

    protected $table = 'materiels';

    protected $fillable = ['nom', 'categorie', 'numero_inventaire', 'etat'];

    public function prets(): HasMany
    {
        return $this->hasMany(Pret::class, 'materiel_id');
    }
}
