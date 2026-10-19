<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Remplit la table "roles" avec les 4 rôles du cahier des charges (section 3).
 *
 * updateOrInsert : crée le rôle s'il n'existe pas, sinon ne fait rien de plus.
 * On peut donc relancer le seeder sans créer de doublons.
 */
class RoleSeeder extends Seeder
{
    private const ROLES = ['eleve', 'enseignant', 'gestionnaire', 'administrateur'];

    public function run(): void
    {
        foreach (self::ROLES as $nomRole) {
            DB::table('roles')->updateOrInsert(['nom' => $nomRole]);
        }
    }
}
