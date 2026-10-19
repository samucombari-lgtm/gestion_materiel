<?php

/**
 * Crée la table de liaison "role_permission" (association POSSEDER du MCD).
 *
 * Une ligne = "ce rôle possède cette permission". La clé primaire est composée
 * des deux colonnes : impossible de donner deux fois la même permission au même rôle.
 *
 * cascadeOnDelete : si on supprime un rôle ou une permission, ses lignes de
 * liaison disparaissent aussi (elles n'auraient plus de sens).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};
