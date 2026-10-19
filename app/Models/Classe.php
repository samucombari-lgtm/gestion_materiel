<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle de la table "classes" (ex. « 1re STMG 2 »).
 *
 * Ses membres (élèves et enseignants) sont dans la table classe_utilisateur
 * (association APPARTENIR). Sert à la règle « l'enseignant valide les élèves
 * de ses classes ».
 */
class Classe extends Model
{
    protected $table = 'classes';

    public $timestamps = false;

    protected $fillable = ['nom'];

    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(Utilisateur::class, 'classe_utilisateur', 'classe_id', 'utilisateur_id');
    }
}
