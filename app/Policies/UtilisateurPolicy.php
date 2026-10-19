<?php

namespace App\Policies;

use App\Models\Pret;
use App\Models\Role;
use App\Models\Utilisateur;

/**
 * Règles sur la gestion des comptes (cahier des charges, RA-05 et section 5.6).
 *
 * $admin = l'utilisateur connecté qui fait l'action ; $cible = le compte visé.
 * Sera branchée sur les écrans de gestion des comptes (BF-04).
 *
 * Laravel trouve cette classe tout seul grâce à son nom : Utilisateur → UtilisateurPolicy.
 */
class UtilisateurPolicy
{
    /** Modifier un compte : jamais le sien depuis l'écran d'administration (RA-05). */
    public function update(Utilisateur $admin, Utilisateur $cible): bool
    {
        return $admin->aLaPermission('compte.gerer')
            && $admin->id !== $cible->id;
    }

    /**
     * Désactiver un compte :
     *  - jamais le sien (RA-05) ;
     *  - jamais le dernier administrateur actif (RA-05) ;
     *  - jamais un compte qui a encore du matériel non rendu (section 5.6).
     */
    public function desactiver(Utilisateur $admin, Utilisateur $cible): bool
    {
        if (! $admin->aLaPermission('compte.gerer') || $admin->id === $cible->id) {
            return false;
        }

        if ($cible->aLeRole(Role::ADMINISTRATEUR) && $this->nombreAdministrateursActifs() <= 1) {
            return false;
        }

        return ! $cible->pretsDemandes()->where('statut', Pret::SORTI)->exists();
    }

    /** Changer le rôle d'un compte : jamais le sien (RA-05). */
    public function changerRole(Utilisateur $admin, Utilisateur $cible): bool
    {
        return $admin->aLaPermission('role.attribuer')
            && $admin->id !== $cible->id;
    }

    private function nombreAdministrateursActifs(): int
    {
        return Utilisateur::where('actif', true)
            ->whereHas('role', function ($requete) {
                $requete->where('nom', Role::ADMINISTRATEUR);
            })
            ->count();
    }
}
