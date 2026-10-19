<?php

/**
 * Crée la table "materiels" : l'inventaire du lycée (BF-10 à BF-12).
 *
 * La colonne "etat" est un ENUM : MariaDB refuse toute valeur hors de la liste
 * (BF-12). Les valeurs sont écrites sans accent, comme les statuts de prêt.
 *
 * On n'efface jamais un matériel qui a des prêts (BF-11) : on le passe à
 * "archive". La table prets l'empêche aussi avec restrictOnDelete.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('categorie', 100);
            $table->string('numero_inventaire', 50)->unique();
            $table->enum('etat', ['disponible', 'prete', 'en_panne', 'en_reparation', 'archive'])
                ->default('disponible');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};
