<?php

/**
 * Crée la table "journal" : le journal de sécurité (section 6, association EFFECTUER).
 *
 * utilisateur_id peut être vide : une connexion échouée avec un e-mail inconnu
 * n'a pas d'auteur connu.
 *
 * Pas de created_at / updated_at : le journal est en lecture seule, une ligne
 * n'est jamais modifiée. La date est dans date_heure, remplie par MariaDB
 * (useCurrent) pour ne pas dépendre de l'heure envoyée par le code.
 *
 * ip : 45 caractères, la taille maximale d'une adresse IPv6.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_heure')->useCurrent()->index();
            $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->restrictOnDelete();
            $table->string('action', 50)->index();
            $table->string('objet')->nullable();
            $table->string('ip', 45)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal');
    }
};
