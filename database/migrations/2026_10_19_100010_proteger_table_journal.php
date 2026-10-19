<?php

/**
 * Renforce la table "journal" (cahier des charges, section 6 : lecture seule).
 *
 * 1. date_heure n'a plus de valeur par défaut : c'est Laravel qui la remplit
 *    (Journal::enregistrer), dans le fuseau Europe/Paris. Si un code oublie la
 *    date, l'insertion échoue au lieu de prendre l'heure du serveur MariaDB.
 *
 * 2. Deux triggers MariaDB refusent toute modification et toute suppression
 *    d'une ligne, même en SQL direct avec le compte materiel_app. Un trigger est
 *    un petit programme que la base exécute automatiquement avant une action.
 *    SIGNAL SQLSTATE '45000' = « provoquer une erreur avec ce message ».
 *
 * Les triggers ne sont créés que sur MariaDB / MySQL : les tests automatiques
 * utilisent SQLite (phpunit.xml), qui a une autre syntaxe.
 *
 * Purge RGPD (plus tard) : il faudra passer par un compte MariaDB
 * d'administration qui supprime temporairement le trigger. C'est voulu.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal', function (Blueprint $table) {
            $table->dateTime('date_heure')->default(null)->nullable(false)->change();
        });

        if (! $this->estMariaDbOuMySql()) {
            return;
        }

        DB::unprepared("
            CREATE TRIGGER journal_interdire_modification
            BEFORE UPDATE ON journal
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le journal de securite est en lecture seule'
        ");

        DB::unprepared("
            CREATE TRIGGER journal_interdire_suppression
            BEFORE DELETE ON journal
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le journal de securite est en lecture seule'
        ");
    }

    public function down(): void
    {
        if ($this->estMariaDbOuMySql()) {
            DB::unprepared('DROP TRIGGER IF EXISTS journal_interdire_modification');
            DB::unprepared('DROP TRIGGER IF EXISTS journal_interdire_suppression');
        }

        Schema::table('journal', function (Blueprint $table) {
            $table->dateTime('date_heure')->useCurrent()->change();
        });
    }

    private function estMariaDbOuMySql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
