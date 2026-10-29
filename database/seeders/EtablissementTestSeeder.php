<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Crée les 2 établissements de test, pour essayer l'isolation multi-établissement :
 *  - LYCEE_A : l'établissement par défaut, déjà créé par la migration
 *    2026_10_29_100001 (updateOrInsert ne le recrée pas) ;
 *  - LYCEE_B : un lycée fictif, uniquement pour les tests.
 *
 * Les autres seeders de test utilisent ces constantes et idDe() pour savoir
 * dans quel lycée ranger chaque compte, matériel, classe ou prêt.
 *
 * Refuse de tourner en production : le lycée B n'existe pas en vrai.
 */
class EtablissementTestSeeder extends Seeder
{
    public const LYCEE_A = 'Lycée Saint-Joseph';
    public const LYCEE_B = 'Lycée Jean-Moulin (test)';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('Établissements de test refusés en production.');
            return;
        }

        foreach ([self::LYCEE_A, self::LYCEE_B] as $nomEtablissement) {
            DB::table('etablissements')->updateOrInsert(['nom' => $nomEtablissement]);
        }
    }

    /**
     * Id de l'établissement de ce nom. Erreur claire s'il n'existe pas, au lieu
     * d'un « NOT NULL constraint failed » plus loin.
     */
    public static function idDe(string $nomEtablissement): int
    {
        $id = DB::table('etablissements')->where('nom', $nomEtablissement)->value('id');

        if ($id === null) {
            throw new RuntimeException('Établissement « ' . $nomEtablissement . ' » introuvable : lancez EtablissementTestSeeder avant.');
        }

        return $id;
    }
}
