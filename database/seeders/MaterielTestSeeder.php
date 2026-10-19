<?php

namespace Database\Seeders;

use App\Models\Materiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crée quelques matériels de test pour essayer le catalogue (BF-10 à BF-12).
 *
 * Comme UtilisateurTestSeeder, il refuse de tourner en production : le vrai
 * inventaire sera saisi par le gestionnaire.
 *
 * updateOrInsert sur le numéro d'inventaire : relancer le seeder ne crée pas de
 * doublons.
 */
class MaterielTestSeeder extends Seeder
{
    private const MATERIELS_DE_TEST = [
        ['numero_inventaire' => 'INV-PC-001',  'nom' => 'Ordinateur portable Dell Latitude', 'categorie' => 'Ordinateur portable', 'etat' => Materiel::DISPONIBLE],
        ['numero_inventaire' => 'INV-PC-002',  'nom' => 'Ordinateur portable HP ProBook',    'categorie' => 'Ordinateur portable', 'etat' => Materiel::DISPONIBLE],
        ['numero_inventaire' => 'INV-VP-001',  'nom' => 'Vidéoprojecteur Epson EB-W51',      'categorie' => 'Vidéoprojecteur',     'etat' => Materiel::DISPONIBLE],
        ['numero_inventaire' => 'INV-VP-002',  'nom' => 'Vidéoprojecteur BenQ MW560',        'categorie' => 'Vidéoprojecteur',     'etat' => Materiel::EN_PANNE],
        ['numero_inventaire' => 'INV-CAM-001', 'nom' => 'Caméra Canon Legria HF G70',        'categorie' => 'Caméra',              'etat' => Materiel::EN_REPARATION],
        ['numero_inventaire' => 'INV-CAB-001', 'nom' => 'Câble HDMI 5 m',                    'categorie' => 'Câble',               'etat' => Materiel::DISPONIBLE],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('Matériels de test refusés en production.');
            return;
        }

        foreach (self::MATERIELS_DE_TEST as $materiel) {
            DB::table('materiels')->updateOrInsert(
                ['numero_inventaire' => $materiel['numero_inventaire']],
                [
                    'nom'        => $materiel['nom'],
                    'categorie'  => $materiel['categorie'],
                    'etat'       => $materiel['etat'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
