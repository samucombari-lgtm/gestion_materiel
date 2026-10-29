<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de la table "roles" (cahier des charges, sections 3 et 5.5).
 *
 * Les constantes donnent le nom exact de chaque rôle en base : on écrit
 * Role::GESTIONNAIRE plutôt que 'gestionnaire', une faute de frappe devient
 * alors une erreur PHP visible au lieu d'un refus silencieux.
 */
class Role extends Model
{
    public const ELEVE = 'eleve';
    public const ENSEIGNANT = 'enseignant';
    public const GESTIONNAIRE = 'gestionnaire';
    public const ADMINISTRATEUR = 'administrateur';
    public const SUPER_ADMIN = 'super_admin';

    public const TOUS = [self::ELEVE, self::ENSEIGNANT, self::GESTIONNAIRE, self::ADMINISTRATEUR, self::SUPER_ADMIN];

    /**
     * Rôles qu'un administrateur peut donner depuis les écrans de gestion des comptes.
     * SUPER_ADMIN n'y est volontairement pas : il voit tous les établissements,
     * il n'est créé que par « php artisan materiel:creer-admin --super ».
     * C'est une liste blanche : un rôle ajouté plus tard n'est pas attribuable par défaut.
     */
    public const ATTRIBUABLES_PAR_FORMULAIRE = [self::ELEVE, self::ENSEIGNANT, self::GESTIONNAIRE, self::ADMINISTRATEUR];

    public const LIBELLES = [
        self::ELEVE          => 'Élève',
        self::ENSEIGNANT     => 'Enseignant',
        self::GESTIONNAIRE   => 'Gestionnaire',
        self::ADMINISTRATEUR => 'Administrateur',
        self::SUPER_ADMIN    => 'Super-administrateur',
    ];

    protected $table = 'roles';

    /** La table n'a pas de colonnes created_at / updated_at. */
    public $timestamps = false;

    protected $fillable = ['nom'];

    /** Les permissions du rôle, via la table de liaison role_permission (association POSSEDER). */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'role_id', 'permission_id');
    }

    public function libelle(): string
    {
        return self::LIBELLES[$this->nom] ?? $this->nom;
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(Utilisateur::class, 'role_id');
    }
}
