<?php

/**
 * Crée la table "utilisateurs" (cahier des charges, section 7.2), qui remplace
 * la table "users" de Laravel.
 *
 * Colonnes liées à la sécurité :
 *  - mot_de_passe_hash : jamais le mot de passe en clair, seulement son hachage
 *    bcrypt fait par Hash::make() (ES-01). Laravel cherche par défaut une colonne
 *    "password" : à l'étape 3, le modèle Utilisateur devra indiquer ce nom
 *    (propriété $authPasswordName, lue par getAuthPassword()).
 *  - actif : un compte désactivé ne peut plus se connecter (BF-04, section 5.6).
 *  - echecs_connexion / bloque_jusqua : blocage 15 min après 5 échecs (ES-03).
 *  - doit_changer_mot_de_passe : changement obligatoire du mot de passe
 *    temporaire à la première connexion (section 5.6).
 *
 * Pas de colonne remember_token : pas de « se souvenir de moi », la session doit
 * expirer après 30 minutes d'inactivité (BF-02).
 *
 * restrictOnDelete sur role_id : MariaDB refuse de supprimer un rôle tant qu'un
 * utilisateur l'a encore.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email')->unique();
            $table->string('mot_de_passe_hash');
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->boolean('actif')->default(true);
            $table->unsignedTinyInteger('echecs_connexion')->default(0);
            $table->dateTime('bloque_jusqua')->nullable();
            $table->boolean('doit_changer_mot_de_passe')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};
