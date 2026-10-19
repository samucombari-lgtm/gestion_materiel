<?php

/**
 * Crée la table "classes" (ex. « 1re STMG 2 »).
 *
 * Elle sert à la règle « l'enseignant valide les demandes des élèves de ses
 * classes » (section 5.3), via la table classe_utilisateur.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 50)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
