<?php

/**
 * Migration fournie par Laravel, adaptée au projet.
 *
 * La table "users" d'origine a été retirée : elle est remplacée par la table
 * "utilisateurs" (voir 2026_10_19_100004_create_utilisateurs_table.php), qui suit
 * le cahier des charges (section 7.2).
 *
 * On garde ici deux tables techniques que Laravel utilise lui-même, avec leurs
 * noms anglais d'origine :
 *  - password_reset_tokens : jetons de réinitialisation du mot de passe (BF-03) ;
 *  - sessions : sessions ouvertes (SESSION_DRIVER=database dans .env). La colonne
 *    user_id permettra de fermer les sessions d'un utilisateur (RA-06).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
