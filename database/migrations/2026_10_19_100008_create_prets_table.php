<?php

/**
 * Crée la table "prets" (associations DEMANDER, VALIDER et CONCERNER du MCD).
 *
 * Cycle d'un prêt (colonne statut) :
 *   en_attente → valide ou refuse → sorti → rendu   (ou annule tant qu'il n'est pas sorti)
 *
 * Deux clés étrangères vers utilisateurs :
 *  - demandeur_id : obligatoire, celui qui demande le prêt ;
 *  - validateur_id : vide tant que la demande est en attente. La règle
 *    « validateur différent du demandeur » (RA-04) sera vérifiée dans la
 *    PretPolicy à l'étape 4.
 *
 * created_at sert de date de la demande.
 * etat_retour : état du matériel constaté au retour (BF-22), vide avant le retour.
 *
 * restrictOnDelete partout : on ne peut pas supprimer un matériel ou un
 * utilisateur qui a un historique de prêts (BF-11, section 5.6).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materiel_id')->constrained('materiels')->restrictOnDelete();
            $table->foreignId('demandeur_id')->constrained('utilisateurs')->restrictOnDelete();
            $table->foreignId('validateur_id')->nullable()->constrained('utilisateurs')->restrictOnDelete();
            $table->date('date_debut');
            $table->date('date_fin_prevue');
            $table->date('date_retour')->nullable();
            $table->enum('statut', ['en_attente', 'valide', 'refuse', 'sorti', 'rendu', 'annule'])
                ->default('en_attente');
            $table->string('motif_refus')->nullable();
            $table->enum('etat_retour', ['disponible', 'en_panne', 'en_reparation'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prets');
    }
};
