<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Journal;
use App\Models\Materiel;
use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Isolation entre établissements (passage en multi-établissement).
 *
 * Les seeders remplissent le lycée A (établissement par défaut). setUp() ajoute
 * un lycée B, avec un élève, une classe et un matériel.
 *
 * Attention en écrivant un test : après actingAs(), EtablissementScope filtre
 * AUSSI les requêtes du test lui-même. Pour vérifier la base sans filtre, on
 * utilise assertDatabaseHas / assertDatabaseMissing (requêtes directes).
 *
 * Ces tests visent les failles qu'EtablissementScope NE couvre PAS : les règles
 * de validation Rule::exists / Rule::unique (elles ne passent pas par Eloquent)
 * et le journal (écrit parfois sans personne de connecté).
 *
 * Lancer : php artisan test --filter=IsolationEtablissementsTest
 */
class IsolationEtablissementsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Etablissement $lyceeB;

    private Utilisateur $bobDuLyceeB;

    private Materiel $materielDuLyceeB;

    private Classe $classeDuLyceeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lyceeB = Etablissement::create(['nom' => 'Lycée B (test)']);

        $this->bobDuLyceeB = $this->creerUtilisateur('bob@lycee-b.test', Role::ELEVE, ['Terminale du lycée B'], $this->lyceeB->id);
        $this->classeDuLyceeB = $this->bobDuLyceeB->classes()->firstOrFail();

        $this->materielDuLyceeB = new Materiel([
            'nom' => 'PC du lycée B',
            'categorie' => 'Ordinateur portable',
            'numero_inventaire' => 'INV-B-777',
            'etat' => Materiel::DISPONIBLE,
        ]);
        $this->materielDuLyceeB->etablissement_id = $this->lyceeB->id;
        $this->materielDuLyceeB->save();
    }

    public function test_un_eleve_ne_peut_pas_demander_un_materiel_d_un_autre_lycee(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');

        $this->actingAs($emma)->post('/prets', [
            'materiel_id' => $this->materielDuLyceeB->id,
            'date_debut' => now()->addDays(3)->toDateString(),
            'date_fin_prevue' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasErrors(['materiel_id' => 'Ce matériel ne peut pas être emprunté.']);

        $this->assertDatabaseMissing('prets', ['materiel_id' => $this->materielDuLyceeB->id]);
    }

    public function test_un_administrateur_ne_peut_pas_mettre_un_compte_dans_une_classe_d_un_autre_lycee(): void
    {
        $adam = $this->utilisateur('admin@lycee.test');
        $emma = $this->utilisateur('eleve@lycee.test');

        $this->actingAs($adam)->put('/comptes/' . $emma->id, [
            'nom' => $emma->nom,
            'prenom' => $emma->prenom,
            'email' => $emma->email,
            'classes' => [$this->classeDuLyceeB->id],
        ])->assertSessionHasErrors('classes.0');

        $this->actingAs($adam)->post('/comptes', [
            'nom' => 'Nouveau',
            'prenom' => 'Eleve',
            'email' => 'nouveau@lycee.test',
            'role_id' => Role::where('nom', Role::ELEVE)->value('id'),
            'classes' => [$this->classeDuLyceeB->id],
        ])->assertSessionHasErrors('classes.0');

        $this->assertDatabaseMissing('classe_utilisateur', ['classe_id' => $this->classeDuLyceeB->id, 'utilisateur_id' => $emma->id]);
        $this->assertDatabaseMissing('utilisateurs', ['email' => 'nouveau@lycee.test']);
        $this->actingAs($adam)->get('/comptes/' . $emma->id . '/modifier')
            ->assertDontSee('Terminale du lycée B');
    }

    public function test_le_numero_d_inventaire_est_unique_par_etablissement(): void
    {
        $gaelle = $this->utilisateur('gestionnaire@lycee.test');
        $champs = ['nom' => 'PC', 'categorie' => 'Ordinateur portable', 'etat' => Materiel::DISPONIBLE];

        // Déjà pris dans le lycée B seulement : accepté dans le lycée A.
        $this->actingAs($gaelle)
            ->post('/materiels', [...$champs, 'numero_inventaire' => 'INV-B-777'])
            ->assertSessionHasNoErrors();

        // Déjà pris dans le lycée A (seeders) : refusé.
        $this->actingAs($gaelle)
            ->post('/materiels', [...$champs, 'numero_inventaire' => 'INV-PC-001'])
            ->assertSessionHasErrors('numero_inventaire');

        $this->assertDatabaseHas('materiels', [
            'numero_inventaire' => 'INV-B-777',
            'etablissement_id' => $gaelle->etablissement_id,
        ]);
    }

    /**
     * Personne n'est connecté pendant une connexion échouée : la ligne prend
     * l'établissement du compte visé. L'admin du lycée A ne la voit pas, le
     * super-administrateur la voit.
     */
    public function test_le_journal_est_isole_par_etablissement(): void
    {
        // Créé avant tout actingAs() : sinon l'événement creating lui donnerait
        // l'établissement de l'utilisateur connecté.
        $superAdmin = $this->creerSuperAdmin();

        $this->post('/connexion', ['email' => 'bob@lycee-b.test', 'mot_de_passe' => 'Mauvais-Mot-De-Passe']);

        $this->assertDatabaseHas('journal', [
            'action' => Journal::CONNEXION_ECHOUEE,
            'utilisateur_id' => $this->bobDuLyceeB->id,
            'etablissement_id' => $this->lyceeB->id,
        ]);

        $this->actingAs($this->utilisateur('admin@lycee.test'))
            ->get('/journal')
            ->assertOk()
            ->assertDontSee('bob@lycee-b.test');

        $this->actingAs($superAdmin)
            ->get('/journal')
            ->assertOk()
            ->assertSee('bob@lycee-b.test');
    }

    /** Créé directement : le seul vrai moyen est « php artisan materiel:creer-admin --super ». */
    private function creerSuperAdmin(): Utilisateur
    {
        $superAdmin = new Utilisateur([
            'nom' => 'Super',
            'prenom' => 'Admin',
            'email' => 'super@academie.test',
            'role_id' => Role::where('nom', Role::SUPER_ADMIN)->value('id'),
            'actif' => true,
            'doit_changer_mot_de_passe' => false,
        ]);
        $superAdmin->mot_de_passe_hash = Hash::make('Super-Admin-Test-2026');
        $superAdmin->save();

        return $superAdmin;
    }
}
