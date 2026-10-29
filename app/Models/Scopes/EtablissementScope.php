<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Isolation des établissements : chaque requête Eloquent sur un modèle qui a
 * ce scope ne renvoie que les lignes de l'établissement de l'utilisateur connecté.
 *
 * Un « global scope » est un filtre que Laravel ajoute tout seul à CHAQUE
 * requête du modèle (Materiel::all(), Pret::find(12), la liaison de route
 * {materiel}, les whereHas…). On ne peut donc pas l'oublier dans un contrôleur.
 *
 * ATTENTION : il ne s'applique PAS aux requêtes DB::table(...) ni aux règles de
 * validation Rule::unique / Rule::exists, qui ne passent pas par Eloquent.
 * Celles-là doivent filtrer sur etablissement_id elles-mêmes.
 *
 * Branché sur les modèles par le trait App\Models\Concerns\AppartientAUnEtablissement.
 */
class EtablissementScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // hasUser() et pas user() : user() chargerait l'utilisateur depuis la
        // base, donc referait une requête sur Utilisateur, qui repasserait par
        // ce scope… boucle infinie. hasUser() répond seulement « est-il déjà chargé ? ».
        // Personne de connecté : page de connexion, commande artisan, seeder.
        if (! Auth::hasUser()) {
            return;
        }

        $utilisateurConnecte = Auth::user();

        if ($utilisateurConnecte->estSuperAdmin()) {
            return;
        }

        // Sans cette garde, where(colonne, null) deviendrait « IS NULL » et le
        // compte verrait les lignes sans établissement (super-admins, ancien journal).
        // Un compte normal sans établissement ne doit rien voir.
        if ($utilisateurConnecte->etablissement_id === null) {
            $builder->whereRaw('1 = 0');
            return;
        }

        // qualifyColumn() donne « materiels.etablissement_id » au lieu de
        // « etablissement_id » : pas d'erreur « colonne ambiguë » dans les jointures.
        $builder->where($model->qualifyColumn('etablissement_id'), $utilisateurConnecte->etablissement_id);
    }
}
