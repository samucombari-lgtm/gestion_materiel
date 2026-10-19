<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->definirUnGateParPermission();
    }

    /**
     * Crée un Gate pour chacune des 11 permissions (cahier des charges, section 5.5).
     *
     * Exemple : Gate::allows('pret.valider') appelle la fonction ci-dessous avec
     * l'utilisateur connecté, qui regarde dans role_permission si son rôle a
     * cette permission.
     *
     * Refus par défaut (RA-01) :
     *  - un nom qui n'est pas défini ici est toujours refusé par Laravel ;
     *  - un visiteur non connecté est toujours refusé, car la fonction attend
     *    un Utilisateur (et pas « null »).
     *
     * Les noms viennent de Permission::LISTE et pas d'une requête en base : ainsi
     * l'application démarre même quand la base est vide (php artisan migrate).
     */
    private function definirUnGateParPermission(): void
    {
        foreach (array_keys(Permission::LISTE) as $nomPermission) {
            Gate::define($nomPermission, function (Utilisateur $utilisateur) use ($nomPermission) {
                return $utilisateur->aLaPermission($nomPermission);
            });
        }
    }
}
