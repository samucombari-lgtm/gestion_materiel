<?php

namespace Database\Seeders;

use App\Models\Pret;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crée des demandes de prêt en attente pour tester les écrans et les droits.
 *
 * Lycée A :
 *  - Emma (élève, 1re STMG 2)  : visible par Emma, Eric (son enseignant), Gaëlle ;
 *  - Bruno (élève, 2nde 3)     : visible par Bruno et Gaëlle seulement ;
 *  - Eric (enseignant)         : visible par Eric et Gaëlle ; seule Gaëlle peut la valider (RA-04).
 * Lycée B :
 *  - Léa (élève) et Hugo (enseignant) : visibles seulement dans le lycée B,
 *    jamais par Gaëlle ni par Eric.
 *
 * Chaque prêt est rangé dans l'établissement de son demandeur, et le matériel
 * est cherché DANS cet établissement : INV-PC-001 existe dans les deux lycées.
 *
 * Refuse de tourner en production. Ne fait rien si des prêts existent déjà,
 * pour ne pas créer de doublons ni mélanger avec des prêts saisis à la main.
 * À lancer après UtilisateurTestSeeder et MaterielTestSeeder.
 */
class PretTestSeeder extends Seeder
{
    private const DEMANDES_DE_TEST = [
        ['email' => 'eleve@lycee.test',        'numero_inventaire' => 'INV-PC-001'],
        ['email' => 'eleve2@lycee.test',       'numero_inventaire' => 'INV-VP-001'],
        ['email' => 'enseignant@lycee.test',   'numero_inventaire' => 'INV-PC-002'],
        ['email' => 'eleve@lycee-b.test',      'numero_inventaire' => 'INV-PC-001'],
        ['email' => 'enseignant@lycee-b.test', 'numero_inventaire' => 'INV-VP-001'],
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

        foreach (self::DEMANDES_DE_TEST as $demande) {
            $demandeur = DB::table('utilisateurs')->where('email', $demande['email'])->first();

            DB::table('prets')->insert([
                'etablissement_id' => $demandeur->etablissement_id,
                'materiel_id'      => DB::table('materiels')
                    ->where('etablissement_id', $demandeur->etablissement_id)
                    ->where('numero_inventaire', $demande['numero_inventaire'])
                    ->value('id'),
                'demandeur_id'     => $demandeur->id,
                'date_debut'       => now()->addDays(7)->toDateString(),
                'date_fin_prevue'  => now()->addDays(14)->toDateString(),
                'statut'           => Pret::EN_ATTENTE,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }
}
