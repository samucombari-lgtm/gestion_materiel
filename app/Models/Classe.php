<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle de la table "classes" (ex. « 1re STMG 2 »).
 *
 * Ses membres (élèves et enseignants) sont dans la table classe_utilisateur
 * (association APPARTENIR). Sert à la règle « l'enseignant valide les élèves
 * de ses classes ».
 *
 * Une classe appartient à un établissement : le nom est unique dans un
 * établissement, pas entre établissements (deux lycées ont leur « 2nde 3 »).
 */
class Classe extends Model
{
    use AppartientAUnEtablissement;

    protected $table = 'classes';

    public $timestamps = false;

    protected $fillable = ['nom'];

    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(Utilisateur::class, 'classe_utilisateur', 'classe_id', 'utilisateur_id');
    }
}
