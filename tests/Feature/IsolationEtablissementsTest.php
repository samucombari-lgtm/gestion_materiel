<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Journal;
use App\Models\Materiel;
use App\Models\Pret;
use App\Models\Role;
use App\Models\Utilisateur;
use Database\Seeders\EtablissementTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Isolation entre établissements : un utilisateur du lycée A ne voit et ne
 * touche AUCUNE donnée du lycée B. Le super-administrateur voit tout.
 *
 * Données : celles des seeders de test (database/seeders), qui remplissent
 * deux lycées. Les données du lycée B sont lues dans setUp(), AVANT tout
 * actingAs() : ensuite, EtablissementScope filtrerait aussi les requêtes du
 * test lui-même. Pour lire la base sans filtre, on utilise assertDatabaseHas()
 * ou DB::table() (requêtes directes, qui ne passent pas par le scope).
 *
 * Adresse directe vers une donnée de B : on vérifie qu'elle donne EXACTEMENT
 * la même réponse qu'un id qui n'existe pas. Rien ne doit révéler qu'elle existe :
 *  - matériel, compte : 404 (liaison de route {materiel} / {compte}) ;
 *  - prêt : 403, règle du projet « inexistant ou interdit → même 403 »
 *    (PretController::trouverPretAutorise).
 *
 * Lancer : php artisan test --filter=IsolationEtablissementsTest
 */
class IsolationEtablissementsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const ID_INEXISTANT = 999999;

    private Etablissement $lyceeA;

    private Etablissement $lyceeB;

    private Utilisateur $leaEleveB;

    private Materiel $materielB;

    private Pret $pretB;

    private Classe $classeB;

    private Utilisateur $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lyceeA = Etablissement::where('nom', EtablissementTestSeeder::LYCEE_A)->firstOrFail();
        $this->lyceeB = Etablissement::where('nom', EtablissementTestSeeder::LYCEE_B)->firstOrFail();
        $this->leaEleveB = $this->utilisateur('eleve@lycee-b.test');
        // INV-PC-001 existe aussi dans le lycée A : on prend bien celui de B.
        $this->materielB = Materiel::where('etablissement_id', $this->lyceeB->id)->where('numero_inventaire', 'INV-PC-001')->firstOrFail();
        $this->pretB = $this->pretDe($this->leaEleveB);
        // Même nom qu'une classe du lycée A.
        $this->classeB = Classe::where('etablissement_id', $this->lyceeB->id)->where('nom', '1re STMG 2')->firstOrFail();
        $this->superAdmin = $this->utilisateur('superadmin@academie.test');
    }

    // ─── Un utilisateur du lycée A ne voit rien du lycée B ─────────────────

    public function test_les_listes_du_lycee_a_ne_montrent_rien_du_lycee_b(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');
        $gaelle = $this->utilisateur('gestionnaire@lycee.test');
        $adam = $this->utilisateur('admin@lycee.test');

        // Catalogue : le INV-PC-001 de A (Dell) oui, celui de B (Lenovo) non.
        $this->actingAs($emma)->get('/materiels')
            ->assertOk()
            ->assertSee('Dell Latitude')
            ->assertDontSee('Lenovo ThinkPad')
            ->assertDontSee('Optoma')
            ->assertDontSee('Galaxy Tab');

        // Prêts (liste et tableau de bord du gestionnaire, qui voit tout SON lycée).
        foreach (['/prets', '/tableau-de-bord'] as $adresse) {
            $this->actingAs($gaelle)->get($adresse)
                ->assertOk()
                ->assertSee('Dell Latitude')
                ->assertDontSee('Lenovo ThinkPad')
                ->assertDontSee('Optoma');
        }

        // Comptes : ni les comptes de B, ni le super-administrateur.
        $this->actingAs($adam)->get('/comptes')
            ->assertOk()
            ->assertSee('eleve@lycee.test')
            ->assertDontSee('lycee-b.test')
            ->assertDontSee('superadmin@academie.test');

        // Classes proposées dans le formulaire d'un compte : seulement celles de A.
        $classeA = Classe::where('etablissement_id', $this->lyceeA->id)->where('nom', '1re STMG 2')->firstOrFail();
        $this->actingAs($adam)->get('/comptes/' . $emma->id . '/modifier')
            ->assertOk()
            ->assertSee('name="classes[]" value="' . $classeA->id . '"', false)
            ->assertDontSee('name="classes[]" value="' . $this->classeB->id . '"', false);
    }

    public function test_les_adresses_directes_vers_le_lycee_b_donnent_la_meme_reponse_qu_un_id_inexistant(): void
    {
        $gaelle = $this->utilisateur('gestionnaire@lycee.test');
        $adam = $this->utilisateur('admin@lycee.test');
        $idRoleEnseignant = Role::where('nom', Role::ENSEIGNANT)->value('id');
        $champsMateriel = ['nom' => 'Piraté', 'categorie' => 'Piraté', 'numero_inventaire' => 'PIRATE-1', 'etat' => Materiel::EN_PANNE];
        $champsCompte = ['nom' => 'Pirate', 'prenom' => 'Pirate', 'email' => 'pirate@lycee.test'];

        // [qui, méthode HTTP, adresse avec {id}, données envoyées, réponse attendue]
        $tentatives = [
            [$gaelle, 'get',   '/materiels/{id}/modifier',  [],                            404],
            [$gaelle, 'put',   '/materiels/{id}',           $champsMateriel,               404],
            [$gaelle, 'patch', '/materiels/{id}/archiver',  [],                            404],
            [$adam,   'get',   '/comptes/{id}/modifier',    [],                            404],
            [$adam,   'put',   '/comptes/{id}',             $champsCompte,                 404],
            [$adam,   'patch', '/comptes/{id}/role',        ['role_id' => $idRoleEnseignant], 404],
            [$adam,   'patch', '/comptes/{id}/desactiver',  [],                            404],
            [$adam,   'patch', '/comptes/{id}/reactiver',   [],                            404],
            [$gaelle, 'get',   '/prets/{id}',               [],                            403],
            [$gaelle, 'patch', '/prets/{id}/valider',       [],                            403],
            [$gaelle, 'patch', '/prets/{id}/refuser',       ['motif_refus' => 'Piraté'],   403],
            [$gaelle, 'patch', '/prets/{id}/annuler',       [],                            403],
            [$gaelle, 'patch', '/prets/{id}/sortie',        [],                            403],
            [$gaelle, 'patch', '/prets/{id}/retour',        ['etat_retour' => 'en_panne'], 403],
        ];

        foreach ($tentatives as [$utilisateur, $methode, $adresse, $donnees, $statutAttendu]) {
            $idDuLyceeB = match (true) {
                str_starts_with($adresse, '/materiels') => $this->materielB->id,
                str_starts_with($adresse, '/comptes') => $this->leaEleveB->id,
                default => $this->pretB->id,
            };

            $reponseLyceeB = $this->actingAs($utilisateur)->$methode(str_replace('{id}', $idDuLyceeB, $adresse), $donnees);
            $reponseInexistant = $this->actingAs($utilisateur)->$methode(str_replace('{id}', self::ID_INEXISTANT, $adresse), $donnees);

            $this->assertSame($statutAttendu, $reponseLyceeB->status(), $methode . ' ' . $adresse . ' sur une donnée du lycée B');
            $this->assertSame($reponseInexistant->status(), $reponseLyceeB->status(), $methode . ' ' . $adresse . ' : réponse différente d\'un id inexistant');
        }

        // Rien n'a changé en base.
        $this->assertDatabaseHas('materiels', ['id' => $this->materielB->id, 'nom' => $this->materielB->nom, 'etat' => Materiel::DISPONIBLE]);
        $this->assertDatabaseHas('utilisateurs', ['id' => $this->leaEleveB->id, 'email' => 'eleve@lycee-b.test', 'actif' => true, 'role_id' => $this->leaEleveB->role_id]);
        $this->assertDatabaseHas('prets', ['id' => $this->pretB->id, 'statut' => Pret::EN_ATTENTE, 'validateur_id' => null]);
    }

    /**
     * Vérification au niveau des modèles, pour CHAQUE compte du lycée A : aucune
     * requête Eloquent ne renvoie une ligne d'un autre lycée ou sans établissement.
     * Elle protège aussi les écrans qui seront ajoutés plus tard.
     */
    public function test_aucun_modele_ne_renvoie_de_donnee_d_un_autre_lycee(): void
    {
        // Des lignes de journal dans B (connexion échouée) et sans établissement (e-mail inconnu).
        $this->post('/connexion', ['email' => 'eleve@lycee-b.test', 'mot_de_passe' => 'Mauvais-Mot-De-Passe']);
        $this->post('/connexion', ['email' => 'inconnu@nulle-part.test', 'mot_de_passe' => 'Mauvais-Mot-De-Passe']);
        $this->assertDatabaseHas('journal', ['etablissement_id' => $this->lyceeB->id]);
        $this->assertDatabaseHas('journal', ['etablissement_id' => null]);

        $modeles = [Utilisateur::class, Materiel::class, Pret::class, Classe::class, Journal::class];
        $idsComptesDuLyceeA = DB::table('utilisateurs')->where('etablissement_id', $this->lyceeA->id)->pluck('id');
        $this->assertGreaterThan(0, $idsComptesDuLyceeA->count());

        foreach ($idsComptesDuLyceeA as $idCompte) {
            $this->actingAs(Utilisateur::findOrFail($idCompte));

            foreach ($modeles as $modele) {
                $lignesHorsDuLyceeA = $modele::where(function ($requete) {
                    $requete->where('etablissement_id', '!=', $this->lyceeA->id)
                        ->orWhereNull('etablissement_id');
                })->count();

                $this->assertSame(0, $lignesHorsDuLyceeA, class_basename($modele) . ' vu par le compte n° ' . $idCompte);
            }

            // Garde-fou : le test ne doit pas réussir parce que la personne ne voit RIEN.
            $this->assertSame(
                DB::table('materiels')->where('etablissement_id', $this->lyceeA->id)->count(),
                Materiel::count()
            );
        }
    }

    // ─── La connexion marche toujours ──────────────────────────────────────

    /**
     * forgetGuards() vide l'utilisateur gardé en mémoire : la page suivante doit
     * le recharger depuis la session (Utilisateur::find), en passant par
     * EtablissementScope. C'est là qu'une boucle infinie serait apparue si le
     * scope appelait Auth::user() au lieu de Auth::hasUser().
     */
    public function test_la_connexion_marche_pour_les_deux_lycees_et_le_super_admin(): void
    {
        $comptes = [
            'eleve@lycee.test' => 'Eleve-Test-2026',
            'admin@lycee-b.test' => 'Admin-B-Test-2026',
            'superadmin@academie.test' => 'Super-Admin-Test-2026',
        ];

        foreach ($comptes as $email => $motDePasse) {
            $this->post('/connexion', ['email' => $email, 'mot_de_passe' => $motDePasse])
                ->assertRedirect(route('tableau-de-bord'));
            $this->assertAuthenticatedAs($this->utilisateur($email));

            $this->app['auth']->forgetGuards();
            $this->get('/tableau-de-bord')->assertOk();
            $this->assertAuthenticated();

            $this->post('/deconnexion');
            $this->assertGuest();
        }
    }

    // ─── Le super-administrateur voit tout ─────────────────────────────────

    public function test_le_super_admin_voit_les_deux_lycees(): void
    {
        $this->actingAs($this->superAdmin)->get('/comptes')
            ->assertOk()
            ->assertSee('eleve@lycee.test')
            ->assertSee('eleve@lycee-b.test');

        $this->actingAs($this->superAdmin)->get('/materiels')
            ->assertOk()
            ->assertSee('Dell Latitude')
            ->assertSee('Lenovo ThinkPad');

        $this->actingAs($this->superAdmin)->get('/comptes/' . $this->leaEleveB->id . '/modifier')
            ->assertOk()
            ->assertSee('eleve@lycee-b.test');

        foreach (['utilisateurs' => Utilisateur::class, 'materiels' => Materiel::class, 'prets' => Pret::class, 'classes' => Classe::class] as $table => $modele) {
            $this->assertSame(DB::table($table)->count(), $modele::count(), $table . ' vus par le super-administrateur');
        }
    }

    // ─── Failles que le scope ne couvre pas (étape 3) ──────────────────────

    public function test_un_eleve_ne_peut_pas_demander_un_materiel_d_un_autre_lycee(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');

        $this->actingAs($emma)->post('/prets', [
            'materiel_id' => $this->materielB->id,
            'date_debut' => now()->addDays(3)->toDateString(),
            'date_fin_prevue' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasErrors(['materiel_id' => 'Ce matériel ne peut pas être emprunté.']);

        $this->assertDatabaseMissing('prets', ['materiel_id' => $this->materielB->id, 'demandeur_id' => $emma->id]);
    }

    public function test_un_administrateur_ne_peut_pas_mettre_un_compte_dans_une_classe_d_un_autre_lycee(): void
    {
        $adam = $this->utilisateur('admin@lycee.test');
        $emma = $this->utilisateur('eleve@lycee.test');

        $this->actingAs($adam)->put('/comptes/' . $emma->id, [
            'nom' => $emma->nom,
            'prenom' => $emma->prenom,
            'email' => $emma->email,
            'classes' => [$this->classeB->id],
        ])->assertSessionHasErrors('classes.0');

        $this->actingAs($adam)->post('/comptes', [
            'nom' => 'Nouveau',
            'prenom' => 'Eleve',
            'email' => 'nouveau@lycee.test',
            'role_id' => Role::where('nom', Role::ELEVE)->value('id'),
            'classes' => [$this->classeB->id],
        ])->assertSessionHasErrors('classes.0');

        $this->assertDatabaseMissing('classe_utilisateur', ['classe_id' => $this->classeB->id, 'utilisateur_id' => $emma->id]);
        $this->assertDatabaseMissing('utilisateurs', ['email' => 'nouveau@lycee.test']);
    }

    public function test_le_numero_d_inventaire_est_unique_par_etablissement(): void
    {
        $gaelle = $this->utilisateur('gestionnaire@lycee.test');
        $champs = ['nom' => 'Tablette', 'categorie' => 'Tablette', 'etat' => Materiel::DISPONIBLE];

        // INV-TAB-001 n'existe que dans le lycée B : accepté dans le lycée A.
        $this->actingAs($gaelle)
            ->post('/materiels', [...$champs, 'numero_inventaire' => 'INV-TAB-001'])
            ->assertSessionHasNoErrors();

        // INV-PC-001 existe déjà dans le lycée A : refusé.
        $this->actingAs($gaelle)
            ->post('/materiels', [...$champs, 'numero_inventaire' => 'INV-PC-001'])
            ->assertSessionHasErrors('numero_inventaire');

        $this->assertDatabaseHas('materiels', ['numero_inventaire' => 'INV-TAB-001', 'etablissement_id' => $this->lyceeA->id]);
    }

    /**
     * Personne n'est connecté pendant une connexion échouée : la ligne prend
     * l'établissement du compte visé. L'admin du lycée A ne la voit pas, le
     * super-administrateur la voit.
     */
    public function test_le_journal_est_isole_par_etablissement(): void
    {
        $this->post('/connexion', ['email' => 'eleve@lycee-b.test', 'mot_de_passe' => 'Mauvais-Mot-De-Passe']);

        $this->assertDatabaseHas('journal', [
            'action' => Journal::CONNEXION_ECHOUEE,
            'utilisateur_id' => $this->leaEleveB->id,
            'etablissement_id' => $this->lyceeB->id,
        ]);

        $this->actingAs($this->utilisateur('admin@lycee.test'))
            ->get('/journal')
            ->assertOk()
            ->assertDontSee('eleve@lycee-b.test');

        $this->actingAs($this->superAdmin)
            ->get('/journal')
            ->assertOk()
            ->assertSee('eleve@lycee-b.test');
    }
}
