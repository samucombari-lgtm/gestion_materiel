<?php

/**
 * Crée la table "etablissements" : passage en multi-établissement.
 *
 * Chaque établissement (lycée) ne voit que ses propres données. Les autres
 * tables pointent vers celle-ci par une colonne etablissement_id (migration
 * suivante : 2026_10_29_100002_ajouter_etablissement_aux_tables).
 *
 * Elle crée aussi l'établissement par défaut : les données déjà en base
 * (comptes, matériels, prêts, classes) lui seront rattachées par la migration
 * suivante, qui a besoin qu'il existe.
 *
 * Le nom est unique : deux établissements du même nom seraient impossibles à
 * distinguer dans les écrans du super-administrateur.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NOM_ETABLISSEMENT_PAR_DEFAUT = 'Lycée Saint-Joseph';

    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150)->unique();
        });

        DB::table('etablissements')->insert(['nom' => self::NOM_ETABLISSEMENT_PAR_DEFAUT]);
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements');
    }
};
