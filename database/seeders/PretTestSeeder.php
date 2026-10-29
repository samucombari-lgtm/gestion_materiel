<?php

namespace Database\Seeders;

use App\Models\Pret;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crée 3 demandes de prêt en attente pour tester les écrans et les droits :
 *  - Emma (élève, 1re STMG 2)  : visible par Emma, Eric (son enseignant), Gaëlle ;
 *  - Bruno (élève, 2nde 3)     : visible par Bruno et Gaëlle seulement ;
 *  - Eric (enseignant)         : visible par Eric et Gaëlle ; seule Gaëlle peut la valider (RA-04).
 *
 * Refuse de tourner en production. Ne fait rien si des prêts existent déjà,
 * pour ne pas créer de doublons ni mélanger avec des prêts saisis à la main.
 * À lancer après UtilisateurTestSeeder et MaterielTestSeeder.
 */
class PretTestSeeder extends Seeder
{
    private const DEMANDES_DE_TEST = [
        ['email' => 'eleve@lycee.test',      'numero_inventaire' => 'INV-PC-001'],
        ['email' => 'eleve2@lycee.test',     'numero_inventaire' => 'INV-VP-001'],
        ['email' => 'enseignant@lycee.test', 'numero_inventaire' => 'INV-PC-002'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('Prêts de test refusés en production.');
            return;
        }

        if (DB::table('prets')->exists()) {
            $this->command->info('Des prêts existent déjà : aucun prêt de test ajouté.');
            return;
        }

        // Provisoire (étape 1 du multi-établissement) : tout va dans l'établissement par défaut.
        $idEtablissement = DB::table('etablissements')->orderBy('id')->value('id');

        foreach (self::DEMANDES_DE_TEST as $demande) {
            DB::table('prets')->insert([
                'etablissement_id' => $idEtablissement,
                'materiel_id'     => DB::table('materiels')->where('numero_inventaire', $demande['numero_inventaire'])->value('id'),
                'demandeur_id'    => DB::table('utilisateurs')->where('email', $demande['email'])->value('id'),
                'date_debut'      => now()->addDays(7)->toDateString(),
                'date_fin_prevue' => now()->addDays(14)->toDateString(),
                'statut'          => Pret::EN_ATTENTE,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
