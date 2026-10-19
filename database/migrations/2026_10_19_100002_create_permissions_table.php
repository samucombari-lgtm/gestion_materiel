<?php

/**
 * Crée la table "permissions" (cahier des charges, section 5.2).
 *
 * Le nom est court et utilisé tel quel dans le code (ex. Gate::allows('pret.valider')),
 * d'où la contrainte unique : deux permissions ne peuvent pas avoir le même nom.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 50)->unique();
            $table->string('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
