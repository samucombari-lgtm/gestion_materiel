<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée les comptes de test des 2 lycées, leurs classes, et un super-administrateur.
 *
 * Lycée A (Saint-Joseph) : un compte par rôle, plus un 2e élève.
 * Les classes servent à tester les périmètres :
 *  - « 1re STMG 2 » : Emma (élève) et Eric (enseignant) → Eric voit et valide les prêts d'Emma ;
 *  - « 2nde 3 » : Bruno (2e élève) seul → Eric ne voit pas ses prêts, et Emma
 *    non plus (test IDOR « élève A ouvre le prêt de l'élève B », section 5.7).
 *
 * Lycée B (Jean-Moulin, fictif) : un élève, un enseignant, un gestionnaire, un
 * administrateur. Leur classe s'appelle aussi « 1re STMG 2 » : le nom d'une
 * classe est unique par établissement, pas sur tout le site. Personne du
 * lycée A ne doit voir ces comptes.
 *
 * Super-administrateur : pas d'établissement, il voit les deux lycées. En vrai,
 * il n'est créé que par « php artisan materiel:creer-admin --super ».
 *
 * Les e-mails restent uniques sur tout le site (ils servent d'identifiant de connexion).
 *
 * SÉCURITÉ : ces comptes ont des mots de passe connus de tous (ils sont écrits
 * ici). Le seeder refuse donc de tourner en production (APP_ENV=production).
 *
 * Les mots de passe font au moins 12 caractères (ES-02) et sont hachés avec
 * Hash::make() avant d'être enregistrés (ES-01).
 *
 * doit_changer_mot_de_passe = false : pour ne pas avoir à changer le mot de
 * passe à chaque test. Les vrais comptes, eux, l'auront à true (valeur par défaut).
 *
 * À lancer après RoleSeeder et EtablissementTestSeeder.
 */
class UtilisateurTestSeeder extends Seeder
{
    private const A = EtablissementTestSeeder::LYCEE_A;
    private const B = EtablissementTestSeeder::LYCEE_B;

    private const COMPTES_DE_TEST = [
        // Lycée A
        ['etablissement' => self::A, 'prenom' => 'Emma',   'nom' => 'Eleve',        'email' => 'eleve@lycee.test',        'role' => Role::ELEVE,          'mot_de_passe' => 'Eleve-Test-2026',        'classe' => '1re STMG 2'],
        ['etablissement' => self::A, 'prenom' => 'Bruno',  'nom' => 'Eleve',        'email' => 'eleve2@lycee.test',       'role' => Role::ELEVE,          'mot_de_passe' => 'Eleve2-Test-2026',       'classe' => '2nde 3'],
        ['etablissement' => self::A, 'prenom' => 'Eric',   'nom' => 'Enseignant',   'email' => 'enseignant@lycee.test',   'role' => Role::ENSEIGNANT,     'mot_de_passe' => 'Enseignant-Test-2026',   'classe' => '1re STMG 2'],
        ['etablissement' => self::A, 'prenom' => 'Gaelle', 'nom' => 'Gestionnaire', 'email' => 'gestionnaire@lycee.test', 'role' => Role::GESTIONNAIRE,   'mot_de_passe' => 'Gestionnaire-Test-2026', 'classe' => null],
        ['etablissement' => self::A, 'prenom' => 'Adam',   'nom' => 'Admin',        'email' => 'admin@lycee.test',        'role' => Role::ADMINISTRATEUR, 'mot_de_passe' => 'Admin-Test-2026',        'classe' => null],
        // Lycée B
        ['etablissement' => self::B, 'prenom' => 'Lea',    'nom' => 'Eleve',        'email' => 'eleve@lycee-b.test',        'role' => Role::ELEVE,          'mot_de_passe' => 'Eleve-B-Test-2026',        'classe' => '1re STMG 2'],
        ['etablissement' => self::B, 'prenom' => 'Hugo',   'nom' => 'Enseignant',   'email' => 'enseignant@lycee-b.test',   'role' => Role::ENSEIGNANT,     'mot_de_passe' => 'Enseignant-B-Test-2026',   'classe' => '1re STMG 2'],
        ['etablissement' => self::B, 'prenom' => 'Ines',   'nom' => 'Gestionnaire', 'email' => 'gestionnaire@lycee-b.test', 'role' => Role::GESTIONNAIRE,   'mot_de_passe' => 'Gestionnaire-B-Test-2026', 'classe' => null],
        ['etablissement' => self::B, 'prenom' => 'Basile', 'nom' => 'Admin',        'email' => 'admin@lycee-b.test',        'role' => Role::ADMINISTRATEUR, 'mot_de_passe' => 'Admin-B-Test-2026',        'classe' => null],
        // Tous les établissements
        ['etablissement' => null,    'prenom' => 'Sacha',  'nom' => 'Superadmin',   'email' => 'superadmin@academie.test',  'role' => Role::SUPER_ADMIN,    'mot_de_passe' => 'Super-Admin-Test-2026',    'classe' => null],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('Comptes de test refusés en production.');
            return;
        }

        $idsRoles = DB::table('roles')->pluck('id', 'nom');

        foreach (self::COMPTES_DE_TEST as $compte) {
            $idEtablissement = $compte['etablissement'] === null
                ? null
                : EtablissementTestSeeder::idDe($compte['etablissement']);

            DB::table('utilisateurs')->updateOrInsert(
                ['email' => $compte['email']],
                [
                    'etablissement_id'          => $idEtablissement,
                    'prenom'                    => $compte['prenom'],
                    'nom'                       => $compte['nom'],
                    'mot_de_passe_hash'         => Hash::make($compte['mot_de_passe']),
                    'role_id'                   => $idsRoles[$compte['role']],
                    'actif'                     => true,
                    'echecs_connexion'          => 0,
                    'bloque_jusqua'             => null,
                    'doit_changer_mot_de_passe' => false,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]
            );

            if ($compte['classe'] !== null) {
                $this->ajouterALaClasse($compte['email'], $compte['classe'], $idEtablissement);
            }
        }
    }

    /** La classe est cherchée par établissement ET nom : « 1re STMG 2 » existe dans les deux lycées. */
    private function ajouterALaClasse(string $email, string $nomClasse, int $idEtablissement): void
    {
        $classe = ['etablissement_id' => $idEtablissement, 'nom' => $nomClasse];
        DB::table('classes')->updateOrInsert($classe);

        DB::table('classe_utilisateur')->insertOrIgnore([
            'classe_id'      => DB::table('classes')->where($classe)->value('id'),
            'utilisateur_id' => DB::table('utilisateurs')->where('email', $email)->value('id'),
        ]);
    }
}
