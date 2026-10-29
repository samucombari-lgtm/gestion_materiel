<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal, lancé par « php artisan db:seed ».
 *
 * Il appelle les autres seeders dans l'ordre : les rôles d'abord, car les
 * permissions et les utilisateurs pointent vers eux ; puis les établissements,
 * car les comptes, matériels, classes et prêts de test y sont rangés.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            EtablissementTestSeeder::class,
            UtilisateurTestSeeder::class,
            MaterielTestSeeder::class,
            PretTestSeeder::class,
        ]);
    }
}
