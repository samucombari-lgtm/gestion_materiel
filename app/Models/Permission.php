<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle de la table "permissions" (cahier des charges, section 5.2).
 *
 * LISTE est la seule liste des permissions du projet. Elle est utilisée :
 *  - par PermissionSeeder, pour remplir la table "permissions" ;
 *  - par AppServiceProvider, pour créer un Gate par permission.
 * Ajouter une permission = l'ajouter ici, relancer le seeder, puis l'utiliser dans le code.
 *
 * Quels rôles ont quelle permission : ça, c'est en base (table role_permission).
 */
class Permission extends Model
{
    public const LISTE = [
        'materiel.voir'      => 'Consulter le catalogue',
        'materiel.gerer'     => 'Ajouter, modifier, archiver un matériel',
        'pret.demander'      => 'Créer une demande de prêt',
        'pret.voir'          => 'Consulter des prêts',
        'pret.valider'       => 'Accepter ou refuser une demande',
        'pret.annuler'       => 'Annuler une demande pas encore sortie',
        'pret.sortie_retour' => 'Enregistrer la sortie et le retour du matériel',
        'compte.gerer'       => 'Créer, modifier, désactiver des comptes',
        'role.attribuer'     => "Changer le rôle d'un utilisateur",
        'journal.voir'       => 'Consulter le journal de sécurité',
        'parametres.gerer'   => "Modifier les réglages (durée max d'un prêt, etc.)",
    ];

    protected $table = 'permissions';

    public $timestamps = false;

    protected $fillable = ['nom', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'permission_id', 'role_id');
    }
}
