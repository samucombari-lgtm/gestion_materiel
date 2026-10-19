<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée un compte de test par rôle, et une classe qui contient l'élève et
 * l'enseignant de test (pour tester plus tard « l'enseignant valide les élèves
 * de ses classes »).
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
        ['prenom' => 'Emma',   'nom' => 'Eleve',        'email' => 'eleve@lycee.test',        'role' => 'eleve',          'mot_de_passe' => 'Eleve-Test-2026'],
        ['prenom' => 'Eric',   'nom' => 'Enseignant',   'email' => 'enseignant@lycee.test',   'role' => 'enseignant',     'mot_de_passe' => 'Enseignant-Test-2026'],
        ['prenom' => 'Gaelle', 'nom' => 'Gestionnaire', 'email' => 'gestionnaire@lycee.test', 'role' => 'gestionnaire',   'mot_de_passe' => 'Gestionnaire-Test-2026'],
        ['prenom' => 'Adam',   'nom' => 'Admin',        'email' => 'admin@lycee.test',        'role' => 'administrateur', 'mot_de_passe' => 'Admin-Test-2026'],
    ];

    private const CLASSE_DE_TEST = '1re STMG 2';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('Comptes de test refusés en production.');
            return;
        }

        $idsRoles = DB::table('roles')->pluck('id', 'nom');

        foreach (self::COMPTES_DE_TEST as $compte) {
            DB::table('utilisateurs')->updateOrInsert(
                ['email' => $compte['email']],
                [
                    'prenom'                    => $compte['prenom'],
                    'nom'                       => $compte['nom'],
                    'mot_de_passe_hash'         => Hash::make($compte['mot_de_passe']),
                    'role_id'                   => $idsRoles[$compte['role']],
                    'actif'                     => true,
                    'doit_changer_mot_de_passe' => false,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]
            );
        }

        DB::table('classes')->updateOrInsert(['nom' => self::CLASSE_DE_TEST]);
        $idClasse = DB::table('classes')->where('nom', self::CLASSE_DE_TEST)->value('id');

        $idsMembresDeLaClasse = DB::table('utilisateurs')
            ->whereIn('email', ['eleve@lycee.test', 'enseignant@lycee.test'])
            ->pluck('id');

        foreach ($idsMembresDeLaClasse as $idUtilisateur) {
            DB::table('classe_utilisateur')->insertOrIgnore([
                'classe_id'      => $idClasse,
                'utilisateur_id' => $idUtilisateur,
            ]);
        }
    }
}
