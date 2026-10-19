<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Remplit la table "roles" avec les 4 rôles du cahier des charges (section 3).
 * La liste vient de Role::TOUS, la même que celle utilisée dans le code.
 *
 * updateOrInsert : crée le rôle s'il n'existe pas, sinon ne fait rien de plus.
 * On peut donc relancer le seeder sans créer de doublons.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::TOUS as $nomRole) {
            DB::table('roles')->updateOrInsert(['nom' => $nomRole]);
        }
    }
}
