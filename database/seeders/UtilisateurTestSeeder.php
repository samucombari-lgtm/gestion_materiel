<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée un compte de test par rôle, plus un 2e élève, et les classes de test.
 *
 * Les classes servent à tester les périmètres :
 *  - « 1re STMG 2 » : Emma (élève) et Eric (enseignant) → Eric voit et valide les prêts d'Emma ;
 *  - « 2nde 3 » : Bruno (2e élève) seul → Eric ne voit pas ses prêts, et Emma
 *    non plus (test IDOR « élève A ouvre le prêt de l'élève B », section 5.7).
 *
 * SÉCURITÉ : ces comptes ont des mots de passe connus de tous (ils sont écrits
 * ici). Le seeder refuse donc de tourner en production (APP_ENV=production).
 *
 * Les mots de passe font au moins 12 caractères (ES-02) et sont hachés avec
 * Hash::make() avant d'être enregistrés (ES-01).
 *
 * doit_changer_mot_de_passe = false : pour ne pas avoir à changer le mot de
 * passe à chaque test. Les vrais comptes, eux, l'auront à true (valeur par défaut).
 */
class UtilisateurTestSeeder extends Seeder
{
    private const COMPTES_DE_TEST = [
        ['prenom' => 'Emma',   'nom' => 'Eleve',        'email' => 'eleve@lycee.test',        'role' => 'eleve',          'mot_de_passe' => 'Eleve-Test-2026',        'classe' => '1re STMG 2'],
        ['prenom' => 'Bruno',  'nom' => 'Eleve',        'email' => 'eleve2@lycee.test',       'role' => 'eleve',          'mot_de_passe' => 'Eleve2-Test-2026',       'classe' => '2nde 3'],
        ['prenom' => 'Eric',   'nom' => 'Enseignant',   'email' => 'enseignant@lycee.test',   'role' => 'enseignant',     'mot_de_passe' => 'Enseignant-Test-2026',   'classe' => '1re STMG 2'],
        ['prenom' => 'Gaelle', 'nom' => 'Gestionnaire', 'email' => 'gestionnaire@lycee.test', 'role' => 'gestionnaire',   'mot_de_passe' => 'Gestionnaire-Test-2026', 'classe' => null],
        ['prenom' => 'Adam',   'nom' => 'Admin',        'email' => 'admin@lycee.test',        'role' => 'administrateur', 'mot_de_passe' => 'Admin-Test-2026',        'classe' => null],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('Comptes de test refusés en production.');
            return;
        }

        $idsRoles = DB::table('roles')->pluck('id', 'nom');

        // Provisoire (étape 1 du multi-établissement) : tout va dans l'établissement par défaut.
        $idEtablissement = DB::table('etablissements')->orderBy('id')->value('id');

        foreach (self::COMPTES_DE_TEST as $compte) {
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
