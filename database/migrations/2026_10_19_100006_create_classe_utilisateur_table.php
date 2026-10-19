<?php

/**
 * Crée la table de liaison "classe_utilisateur" (association APPARTENIR du MCD).
 *
 * Une ligne = "cet élève ou cet enseignant fait partie de cette classe".
 * Un utilisateur peut être dans plusieurs classes (un enseignant en a souvent
 * plusieurs), l'administrateur dans aucune.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classe_utilisateur', function (Blueprint $table) {
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();

            $table->primary(['classe_id', 'utilisateur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classe_utilisateur');
    }
};
