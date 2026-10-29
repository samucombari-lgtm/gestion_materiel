<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Remplit la table "permissions" (section 5.2, liste dans Permission::LISTE),
 * puis la table "role_permission" d'après la matrice rôles / permissions (section 5.3).
 *
 * Dans la matrice, « Oui » et une restriction (« Les siens », « Élèves de ses
 * classes »…) donnent tous les deux la permission ici. La restriction de
 * périmètre est vérifiée dans les Policies (app/Policies).
 * « Non » = la permission n'est pas donnée : refus par défaut (RA-01).
 *
 * À lancer après RoleSeeder (il a besoin des rôles).
 */
class PermissionSeeder extends Seeder
{
    /**
     * Permissions de l'administrateur. Le super-administrateur a exactement les
     * mêmes : la seule différence est qu'il n'est pas limité à un établissement
     * (EtablissementScope ne le filtre pas).
     */
    private const PERMISSIONS_ADMINISTRATION = [
        'materiel.voir', 'compte.gerer', 'role.attribuer', 'journal.voir', 'parametres.gerer',
    ];

    private const PERMISSIONS_PAR_ROLE = [
        Role::ELEVE => [
            'materiel.voir', 'pret.demander', 'pret.voir', 'pret.annuler',
        ],
        Role::ENSEIGNANT => [
            'materiel.voir', 'pret.demander', 'pret.voir', 'pret.valider', 'pret.annuler',
        ],
        Role::GESTIONNAIRE => [
            'materiel.voir', 'materiel.gerer', 'pret.demander', 'pret.voir',
            'pret.valider', 'pret.annuler', 'pret.sortie_retour',
        ],
        // L'administrateur n'est pas gestionnaire (principe du moindre privilège, section 3).
        Role::ADMINISTRATEUR => self::PERMISSIONS_ADMINISTRATION,
        Role::SUPER_ADMIN    => self::PERMISSIONS_ADMINISTRATION,
    ];

    public function run(): void
    {
        foreach (Permission::LISTE as $nomPermission => $description) {
            DB::table('permissions')->updateOrInsert(
                ['nom' => $nomPermission],
                ['description' => $description]
            );
        }

        // pluck('id', 'nom') donne un tableau ['eleve' => 1, 'enseignant' => 2, ...]
        $idsRoles = DB::table('roles')->pluck('id', 'nom');
        $idsPermissions = DB::table('permissions')->pluck('id', 'nom');

        foreach (self::PERMISSIONS_PAR_ROLE as $nomRole => $nomsPermissions) {
            foreach ($nomsPermissions as $nomPermission) {
                // insertOrIgnore : si la ligne existe déjà, on ne la recrée pas.
                DB::table('role_permission')->insertOrIgnore([
                    'role_id'       => $idsRoles[$nomRole],
                    'permission_id' => $idsPermissions[$nomPermission],
                ]);
            }
        }
    }
}
