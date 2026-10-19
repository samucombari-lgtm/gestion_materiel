<?php

namespace App\Policies;

use App\Models\Pret;
use App\Models\Role;
use App\Models\Utilisateur;

/**
 * Règles de périmètre sur les prêts (cahier des charges, sections 5.3 et 5.4).
 *
 * Chaque méthode vérifie d'abord la permission du rôle (Gate), puis le périmètre :
 * à qui appartient le prêt, dans quel statut il est.
 *
 * Utilisation dans un contrôleur (étape 6) :
 *     Gate::authorize('view', $pret);      // 403 automatique si la réponse est non
 * (en Laravel 12, $this->authorize() n'existe plus dans le Controller de base)
 * Dans une vue : @can('view', $pret) ... @endcan
 *
 * Laravel trouve cette classe tout seul grâce à son nom : Pret → PretPolicy.
 */
class PretPolicy
{
    /**
     * RA-03 (contrôle du propriétaire, contre l'IDOR) :
     *  - élève : ses prêts ;
     *  - enseignant : ses prêts + ceux des élèves de ses classes ;
     *  - gestionnaire : tous.
     */
    public function view(Utilisateur $utilisateur, Pret $pret): bool
    {
        if (! $utilisateur->aLaPermission('pret.voir')) {
            return false;
        }

        if ($utilisateur->aLeRole(Role::GESTIONNAIRE)) {
            return true;
        }

        if ($pret->demandeur_id === $utilisateur->id) {
            return true;
        }

        return $utilisateur->enseigneA($pret->demandeur);
    }

    /**
     * Valider ou refuser une demande en attente :
     *  - RA-04 : personne ne valide sa propre demande ;
     *  - enseignant : seulement les demandes des élèves de ses classes ;
     *  - gestionnaire : toutes les autres (dont celles des enseignants et des
     *    autres gestionnaires).
     */
    public function valider(Utilisateur $utilisateur, Pret $pret): bool
    {
        if (! $utilisateur->aLaPermission('pret.valider')) {
            return false;
        }

        if ($pret->statut !== Pret::EN_ATTENTE) {
            return false;
        }

        if ($pret->demandeur_id === $utilisateur->id) {
            return false;
        }

        if ($utilisateur->aLeRole(Role::GESTIONNAIRE)) {
            return true;
        }

        return $utilisateur->enseigneA($pret->demandeur);
    }

    /**
     * Annuler un prêt pas encore sorti :
     *  - élève, enseignant : seulement sa propre demande, encore en attente ;
     *  - gestionnaire : toute demande en attente ou validée.
     */
    public function annuler(Utilisateur $utilisateur, Pret $pret): bool
    {
        if (! $utilisateur->aLaPermission('pret.annuler')) {
            return false;
        }

        if ($utilisateur->aLeRole(Role::GESTIONNAIRE)) {
            return in_array($pret->statut, [Pret::EN_ATTENTE, Pret::VALIDE], true);
        }

        return $pret->demandeur_id === $utilisateur->id
            && $pret->statut === Pret::EN_ATTENTE;
    }
}
