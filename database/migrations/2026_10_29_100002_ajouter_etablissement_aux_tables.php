<?php

/**
 * Ajoute la colonne etablissement_id (clé étrangère vers "etablissements") aux
 * tables qui contiennent des données propres à un établissement.
 *
 * Colonne OBLIGATOIRE (NOT NULL) : materiels, prets, classes.
 *   Un matériel, un prêt ou une classe appartient toujours à un établissement.
 *
 * Colonne FACULTATIVE (NULL autorisé) :
 *  - utilisateurs : le super-administrateur n'appartient à aucun établissement
 *    (il les voit tous) ;
 *  - journal : les lignes écrites avant cette migration restent sans
 *    établissement, car les triggers MariaDB interdisent tout UPDATE sur cette
 *    table (migration 2026_10_19_100010). Seul le super-administrateur les verra.
 *    Même chose pour une connexion échouée avec un e-mail inconnu.
 *
 * Les lignes déjà en base (sauf le journal) sont rattachées à l'établissement
 * par défaut, créé par la migration précédente. Pour les tables obligatoires,
 * on procède en 3 temps, car MariaDB refuse d'ajouter une colonne NOT NULL
 * sans valeur à des lignes existantes :
 *   1. ajouter la colonne en NULL autorisé ;
 *   2. remplir les lignes existantes ;
 *   3. passer la colonne en NOT NULL.
 *
 * Unicité par établissement (clé unique composite) :
 *  - numero_inventaire : deux lycées peuvent avoir chacun un « INV-PC-001 » ;
 *  - nom d'une classe : deux lycées peuvent avoir chacun une « 2nde 3 ».
 *
 * restrictOnDelete : MariaDB refuse de supprimer un établissement qui a encore
 * des données.
 *
 * down() échoue si deux établissements ont le même numéro d'inventaire ou le
 * même nom de classe : l'ancienne unicité globale ne peut plus être respectée.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES_ETABLISSEMENT_OBLIGATOIRE = ['materiels', 'prets', 'classes'];

    private const TABLES_ETABLISSEMENT_FACULTATIF = ['utilisateurs', 'journal'];

    /** Tables dont les lignes existantes sont rattachées à l'établissement par défaut (pas le journal). */
    private const TABLES_A_RATTACHER = ['utilisateurs', 'materiels', 'prets', 'classes'];

    public function up(): void
    {
        foreach ([...self::TABLES_ETABLISSEMENT_OBLIGATOIRE, ...self::TABLES_ETABLISSEMENT_FACULTATIF] as $nomTable) {
            Schema::table($nomTable, function (Blueprint $table) {
                $table->foreignId('etablissement_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('etablissements')
                    ->restrictOnDelete();
            });
        }

        $this->rattacherLesDonneesExistantes();

        foreach (self::TABLES_ETABLISSEMENT_OBLIGATOIRE as $nomTable) {
            Schema::table($nomTable, function (Blueprint $table) {
                $table->unsignedBigInteger('etablissement_id')->nullable(false)->change();
            });
        }

        Schema::table('materiels', function (Blueprint $table) {
            $table->dropUnique(['numero_inventaire']);
            $table->unique(['etablissement_id', 'numero_inventaire']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique(['nom']);
            $table->unique(['etablissement_id', 'nom']);
        });
    }

    public function down(): void
    {
        // On retire la clé étrangère AVANT la clé composite : MariaDB refuse de
        // supprimer un index dont une clé étrangère a encore besoin.
        Schema::table('materiels', function (Blueprint $table) {
            $table->dropForeign(['etablissement_id']);
            $table->dropUnique(['etablissement_id', 'numero_inventaire']);
            $table->unique('numero_inventaire');
            $table->dropColumn('etablissement_id');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['etablissement_id']);
            $table->dropUnique(['etablissement_id', 'nom']);
            $table->unique('nom');
            $table->dropColumn('etablissement_id');
        });

        foreach (['prets', ...self::TABLES_ETABLISSEMENT_FACULTATIF] as $nomTable) {
            Schema::table($nomTable, function (Blueprint $table) {
                $table->dropConstrainedForeignId('etablissement_id');
            });
        }
    }

    /**
     * Met l'établissement par défaut sur toutes les lignes déjà en base.
     * Sur une base vide (migrate:fresh), il n'y a rien à rattacher.
     */
    private function rattacherLesDonneesExistantes(): void
    {
        $idEtablissementParDefaut = DB::table('etablissements')->orderBy('id')->value('id');

        foreach (self::TABLES_A_RATTACHER as $nomTable) {
            DB::table($nomTable)->update(['etablissement_id' => $idEtablissementParDefaut]);
        }
    }
};
