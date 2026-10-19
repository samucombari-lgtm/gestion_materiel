<?php

namespace App\Policies;

use App\Models\Utilisateur;
use Illuminate\Auth\Access\Response;

/**
 * Règles sur la gestion des comptes (BF-04, RA-05, section 5.6).
 *
 * $admin = l'utilisateur connecté qui fait l'action ; $cible = le compte visé.
 *
 * desactiver() et changerRole() renvoient un objet Response au lieu d'un simple
 * true / false : Response::deny('raison') permet au contrôleur d'afficher
 * POURQUOI l'action est refusée (avec Gate::inspect()). Pour Laravel, un
 * deny() reste un refus : @can répond non, Gate::authorize() renvoie 403.
 *
 * Laravel trouve cette classe tout seul grâce à son nom : Utilisateur → UtilisateurPolicy.
 */
class UtilisateurPolicy
{
    /** Modifier un compte (nom, e-mail, classes) : jamais le sien depuis l'administration. */
    public function update(Utilisateur $admin, Utilisateur $cible): bool
    {
        return $admin->aLaPermission('compte.gerer')
            && $admin->id !== $cible->id;
    }

    /** RA-05 : ni son propre rôle, ni le rôle du dernier administrateur actif. */
    public function changerRole(Utilisateur $admin, Utilisateur $cible): Response
    {
        if (! $admin->aLaPermission('role.attribuer')) {
            return Response::deny('Vous n\'avez pas le droit d\'attribuer des rôles.');
        }

        if ($admin->id === $cible->id) {
            return Response::deny('Vous ne pouvez pas changer votre propre rôle.');
        }

        if ($cible->estDernierAdministrateurActif()) {
            return Response::deny('C\'est le dernier administrateur actif : il doit garder ce rôle.');
        }

        return Response::allow();
    }

    /**
     * Désactiver un compte :
     *  - RA-05 : jamais le sien, jamais le dernier administrateur actif ;
     *  - section 5.6 : jamais un compte qui a encore du matériel non rendu.
     */
    public function desactiver(Utilisateur $admin, Utilisateur $cible): Response
    {
        if (! $admin->aLaPermission('compte.gerer')) {
            return Response::deny('Vous n\'avez pas le droit de gérer les comptes.');
        }

        if ($admin->id === $cible->id) {
            return Response::deny('Vous ne pouvez pas désactiver votre propre compte.');
        }

        if (! $cible->actif) {
            return Response::deny('Ce compte est déjà désactivé.');
        }

        if ($cible->estDernierAdministrateurActif()) {
            return Response::deny('C\'est le dernier administrateur actif : il ne peut pas être désactivé.');
        }

        if ($cible->aDuMaterielNonRendu()) {
            return Response::deny('Ce compte a encore du matériel non rendu : il ne peut pas être désactivé.');
        }

        return Response::allow();
    }

    /** Réactiver un compte désactivé. */
    public function reactiver(Utilisateur $admin, Utilisateur $cible): bool
    {
        return $admin->aLaPermission('compte.gerer')
            && $admin->id !== $cible->id
            && ! $cible->actif;
    }
}
