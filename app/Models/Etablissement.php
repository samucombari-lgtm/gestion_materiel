<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de la table "etablissements" : un lycée (passage en multi-établissement).
 *
 * Les utilisateurs, matériels, prêts, classes et lignes du journal ont une
 * colonne etablissement_id qui pointe vers cette table. Chaque établissement
 * ne voit que ses propres données.
 *
 * Le super-administrateur n'a pas d'établissement (etablissement_id vide) :
 * il les voit tous.
 */
class Etablissement extends Model
{
    protected $table = 'etablissements';

    /** La table n'a pas de colonnes created_at / updated_at. */
    public $timestamps = false;

    protected $fillable = ['nom'];

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(Utilisateur::class, 'etablissement_id');
    }

    public function materiels(): HasMany
    {
        return $this->hasMany(Materiel::class, 'etablissement_id');
    }

    public function prets(): HasMany
    {
        return $this->hasMany(Pret::class, 'etablissement_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'etablissement_id');
    }
}
