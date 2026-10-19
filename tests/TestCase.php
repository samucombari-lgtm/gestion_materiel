<?php

namespace Tests;

use App\Models\Classe;
use App\Models\Pret;
use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Classe de base de tous les tests.
 *
 * Les tests tournent sur une base SQLite en mémoire (voir phpunit.xml) :
 * ils ne touchent jamais la base MariaDB de développement.
 *
 * Les fonctions ci-dessous évitent de répéter les mêmes lignes dans chaque test.
 * Les comptes de test sont ceux de database/seeders/UtilisateurTestSeeder.php.
 */
abstract class TestCase extends BaseTestCase
{
    /** Retrouve un compte des seeders par son e-mail (ex. 'eleve@lycee.test'). */
    protected function utilisateur(string $email): Utilisateur
    {
        return Utilisateur::where('email', $email)->firstOrFail();
    }

    /** Le premier prêt demandé par cet utilisateur (les seeders en créent un par compte de test). */
    protected function pretDe(Utilisateur $demandeur): Pret
    {
        return Pret::where('demandeur_id', $demandeur->id)->orderBy('id')->firstOrFail();
    }

    /** Crée un prêt pour ce demandeur, sur le premier matériel disponible, avec le statut voulu. */
    protected function creerPret(Utilisateur $demandeur, string $statut = Pret::EN_ATTENTE): Pret
    {
        $pret = Pret::create([
            'materiel_id' => \App\Models\Materiel::where('etat', 'disponible')->value('id'),
            'demandeur_id' => $demandeur->id,
            'date_debut' => now()->addDays(20)->toDateString(),
            'date_fin_prevue' => now()->addDays(25)->toDateString(),
        ]);
        $pret->statut = $statut;
        $pret->save();

        return $pret;
    }

    /** Crée un compte actif avec ce rôle et ces classes (créées si besoin). */
    protected function creerUtilisateur(string $email, string $nomRole, array $nomsClasses = []): Utilisateur
    {
        $utilisateur = new Utilisateur([
            'nom' => 'Test',
            'prenom' => $email,
            'email' => $email,
            'role_id' => Role::where('nom', $nomRole)->value('id'),
            'actif' => true,
            'doit_changer_mot_de_passe' => false,
        ]);
        $utilisateur->mot_de_passe_hash = Hash::make('Mot-De-Passe-Test-2026');
        $utilisateur->save();

        foreach ($nomsClasses as $nomClasse) {
            $classe = Classe::firstOrCreate(['nom' => $nomClasse]);
            $utilisateur->classes()->attach($classe->id);
        }

        return $utilisateur;
    }
}
