<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Role;
use App\Models\Utilisateur;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la commande « php artisan materiel:creer-admin ».
 *
 * On ne lance que les seeders des rôles et des permissions : c'est la situation
 * d'une mise en production (aucun compte de test).
 *
 * expectsQuestion() simule les réponses tapées au clavier.
 */
class CreerAdministrateurTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    public function test_la_commande_cree_un_administrateur_avec_un_mot_de_passe_temporaire(): void
    {
        $this->artisan('materiel:creer-admin')
            ->expectsQuestion('Prénom', 'Alice')
            ->expectsQuestion('Nom', 'Martin')
            ->expectsQuestion('Adresse e-mail (identifiant de connexion)', 'Alice.Martin@Lycee.fr')
            ->expectsChoice('Établissement', 'Lycée Saint-Joseph', ['Lycée Saint-Joseph'])
            ->expectsConfirmation('Créer le compte administrateur alice.martin@lycee.fr ?', 'yes')
            ->expectsOutputToContain('Mot de passe temporaire')
            ->assertSuccessful();

        $administrateur = Utilisateur::where('email', 'alice.martin@lycee.fr')->firstOrFail();
        $this->assertTrue($administrateur->aLeRole(Role::ADMINISTRATEUR));
        $this->assertSame('Lycée Saint-Joseph', $administrateur->etablissement->nom);
        $this->assertTrue($administrateur->actif);
        $this->assertTrue($administrateur->doit_changer_mot_de_passe);
        $this->assertStringStartsWith('$2y$', $administrateur->mot_de_passe_hash);
        $this->assertDatabaseHas('journal', ['action' => Journal::COMPTE_CREE, 'utilisateur_id' => null]);
    }

    public function test_l_option_super_cree_un_super_administrateur_sans_etablissement(): void
    {
        $this->artisan('materiel:creer-admin --super')
            ->expectsQuestion('Prénom', 'Sam')
            ->expectsQuestion('Nom', 'Durand')
            ->expectsQuestion('Adresse e-mail (identifiant de connexion)', 'sam.durand@academie.fr')
            ->expectsConfirmation('Créer le compte SUPER-ADMINISTRATEUR sam.durand@academie.fr (accès à tous les établissements) ?', 'yes')
            ->assertSuccessful();

        $superAdmin = Utilisateur::where('email', 'sam.durand@academie.fr')->firstOrFail();
        $this->assertTrue($superAdmin->estSuperAdmin());
        $this->assertNull($superAdmin->etablissement_id);
        $this->assertTrue($superAdmin->doit_changer_mot_de_passe);
    }

    public function test_la_commande_refuse_un_email_deja_utilise_ou_invalide(): void
    {
        $this->creerUtilisateur('deja.pris@lycee.fr', Role::ELEVE);
        // On compte AVANT : la base en mémoire peut contenir des comptes créés par d'autres tests.
        $nombreDeComptesAvant = Utilisateur::count();

        $this->artisan('materiel:creer-admin')
            ->expectsQuestion('Prénom', 'Alice')
            ->expectsQuestion('Nom', 'Martin')
            ->expectsQuestion('Adresse e-mail (identifiant de connexion)', 'pas-un-email')
            ->expectsOutputToContain('L\'adresse e-mail n\'est pas valide.')
            ->assertFailed();

        $this->artisan('materiel:creer-admin')
            ->expectsQuestion('Prénom', 'Alice')
            ->expectsQuestion('Nom', 'Martin')
            ->expectsQuestion('Adresse e-mail (identifiant de connexion)', 'DEJA.PRIS@lycee.fr')
            ->expectsOutputToContain('Cette adresse e-mail est déjà utilisée par un autre compte.')
            ->assertFailed();

        $this->assertSame($nombreDeComptesAvant, Utilisateur::count());
        $this->assertFalse(Utilisateur::where('email', 'deja.pris@lycee.fr')->firstOrFail()->aLeRole(Role::ADMINISTRATEUR));
    }
}
