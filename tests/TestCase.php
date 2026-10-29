<?php

namespace Tests;

use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Materiel;
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
 *
 * Ces fonctions tournent SANS utilisateur connecté : EtablissementScope ne filtre
 * rien et l'événement creating ne remplit pas etablissement_id. Elles donnent
 * donc l'établissement elles-mêmes.
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

    /**
     * Crée un prêt pour ce demandeur, sur le premier matériel disponible de son
     * établissement, avec le statut voulu.
     */
    protected function creerPret(Utilisateur $demandeur, string $statut = Pret::EN_ATTENTE): Pret
    {
        $pret = new Pret([
            'materiel_id' => Materiel::where('etablissement_id', $demandeur->etablissement_id)
                ->where('etat', Materiel::DISPONIBLE)
                ->value('id'),
            'demandeur_id' => $demandeur->id,
            'date_debut' => now()->addDays(20)->toDateString(),
            'date_fin_prevue' => now()->addDays(25)->toDateString(),
        ]);
        $pret->etablissement_id = $demandeur->etablissement_id;
        $pret->statut = $statut;
        $pret->save();

        return $pret;
    }

    /**
     * Crée un compte actif avec ce rôle et ces classes (créées si besoin).
     * Sans $idEtablissement : l'établissement par défaut (le premier en base).
     */
    protected function creerUtilisateur(string $email, string $nomRole, array $nomsClasses = [], ?int $idEtablissement = null): Utilisateur
    {
        $idEtablissement ??= Etablissement::orderBy('id')->value('id');

        $utilisateur = new Utilisateur([
            'nom' => 'Test',
            'prenom' => $email,
            'email' => $email,
            'role_id' => Role::where('nom', $nomRole)->value('id'),
            'actif' => true,
            'doit_changer_mot_de_passe' => false,
        ]);
        $utilisateur->mot_de_passe_hash = Hash::make('Mot-De-Passe-Test-2026');
        $utilisateur->etablissement_id = $idEtablissement;
        $utilisateur->save();

        foreach ($nomsClasses as $nomClasse) {
            $utilisateur->classes()->attach($this->classe($nomClasse, $idEtablissement)->id);
        }

        return $utilisateur;
    }

    /** Retrouve la classe de ce nom dans cet établissement, ou la crée. */
    private function classe(string $nomClasse, int $idEtablissement): Classe
    {
        $classe = Classe::where('etablissement_id', $idEtablissement)->where('nom', $nomClasse)->first();

        if ($classe === null) {
            $classe = new Classe(['nom' => $nomClasse]);
            $classe->etablissement_id = $idEtablissement;
            $classe->save();
        }

        return $classe;
    }
}
