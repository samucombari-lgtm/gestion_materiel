<?php

/**
 * Crée la table "roles" (cahier des charges, sections 3 et 5.5).
 *
 * Un rôle = un groupe de permissions : eleve, enseignant, gestionnaire,
 * administrateur. Le visiteur n'est pas stocké : c'est quelqu'un de non connecté.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 50)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
