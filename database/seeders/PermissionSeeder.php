<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Remplit la table "permissions" (section 5.2), puis la table "role_permission"
 * d'après la matrice rôles / permissions (section 5.3).
 *
 * Dans la matrice, « Oui » et une restriction (« Les siens », « Élèves de ses
 * classes »…) donnent tous les deux la permission ici. La restriction de
 * périmètre sera vérifiée plus tard dans les Policies (étape 4).
 * « Non » = la permission n'est pas donnée : refus par défaut (RA-01).
 *
 * À lancer après RoleSeeder (il a besoin des rôles).
 */
class PermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'materiel.voir'       => 'Consulter le catalogue',
        'materiel.gerer'      => 'Ajouter, modifier, archiver un matériel',
        'pret.demander'       => 'Créer une demande de prêt',
        'pret.voir'           => 'Consulter des prêts',
        'pret.valider'        => 'Accepter ou refuser une demande',
        'pret.annuler'        => 'Annuler une demande pas encore sortie',
        'pret.sortie_retour'  => 'Enregistrer la sortie et le retour du matériel',
        'compte.gerer'        => 'Créer, modifier, désactiver des comptes',
        'role.attribuer'      => "Changer le rôle d'un utilisateur",
        'journal.voir'        => 'Consulter le journal de sécurité',
        'parametres.gerer'    => "Modifier les réglages (durée max d'un prêt, etc.)",
    ];

    private const PERMISSIONS_PAR_ROLE = [
        'eleve' => [
            'materiel.voir', 'pret.demander', 'pret.voir', 'pret.annuler',
        ],
        'enseignant' => [
            'materiel.voir', 'pret.demander', 'pret.voir', 'pret.valider', 'pret.annuler',
        ],
        'gestionnaire' => [
            'materiel.voir', 'materiel.gerer', 'pret.demander', 'pret.voir',
            'pret.valider', 'pret.annuler', 'pret.sortie_retour',
        ],
        // L'administrateur n'est pas gestionnaire (principe du moindre privilège, section 3).
        'administrateur' => [
            'materiel.voir', 'compte.gerer', 'role.attribuer', 'journal.voir', 'parametres.gerer',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $nomPermission => $description) {
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
