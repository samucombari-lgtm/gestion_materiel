<?php

namespace App\Models\Concerns;

use App\Models\Etablissement;
use App\Models\Scopes\EtablissementScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * À mettre dans un modèle dont la table a une colonne etablissement_id :
 *     use AppartientAUnEtablissement;
 *
 * Un trait est un morceau de code qu'on « colle » dans plusieurs classes, pour
 * ne pas l'écrire 4 fois (Utilisateur, Materiel, Pret, Classe).
 *
 * Laravel appelle tout seul bootAppartientAUnEtablissement() au démarrage du
 * modèle (convention : « boot » + nom du trait). On y branche :
 *  1. EtablissementScope : la lecture est filtrée sur l'établissement ;
 *  2. l'événement « creating » (juste avant l'INSERT) : etablissement_id est rempli.
 *
 * Règles de l'événement creating :
 *  - personne de connecté (seeder, commande artisan) : on garde la valeur que
 *    le code a donnée. Si elle manque, MariaDB refuse l'insertion (NOT NULL) ;
 *  - super-admin connecté : il voit tous les établissements, on ne peut pas
 *    deviner lequel il vise. Il doit le donner, sinon erreur ;
 *  - autre utilisateur : TOUJOURS son établissement, même si le code a mis
 *    autre chose. Personne ne peut créer de données chez un autre lycée.
 *
 * etablissement_id n'est jamais dans $fillable : un formulaire ne peut pas le choisir.
 */
trait AppartientAUnEtablissement
{
    protected static function bootAppartientAUnEtablissement(): void
    {
        static::addGlobalScope(new EtablissementScope());

        static::creating(function (Model $nouvelleLigne) {
            if (! Auth::hasUser()) {
                return;
            }

            $utilisateurConnecte = Auth::user();

            if ($utilisateurConnecte->estSuperAdmin()) {
                if ($nouvelleLigne->etablissement_id === null) {
                    throw new LogicException('Un super-administrateur doit préciser l\'établissement de ce qu\'il crée.');
                }
                return;
            }

            $nouvelleLigne->etablissement_id = $utilisateurConnecte->etablissement_id;
        });
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class, 'etablissement_id');
    }
}
