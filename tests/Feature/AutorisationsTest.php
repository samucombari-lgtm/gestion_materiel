<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Pret;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests d'autorisation du cahier des charges (section 5.7), à garder comme
 * preuves pour l'épreuve E5.
 *
 * Chaque test commence avec une base neuve :
 *  - RefreshDatabase recrée toutes les tables avant chaque test ;
 *  - $seed = true lance DatabaseSeeder : rôles, permissions, comptes de test
 *    (Emma et Bruno élèves dans deux classes différentes, Eric enseignant
 *    d'Emma, Gaëlle gestionnaire, Adam administrateur) et 3 demandes de prêt.
 *
 * actingAs($utilisateur) : la requête suivante est faite « connecté en tant que ».
 * Pendant les tests, Laravel ne vérifie pas le jeton CSRF : pas besoin de le fournir.
 *
 * Lancer : php artisan test --filter=AutorisationsTest
 */
class AutorisationsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // ─── Tableau 5.7, ligne par ligne ──────────────────────────────────────

    /** 5.7 : « Visiteur ouvre /prets → Redirigé vers la connexion ». */
    public function test_un_visiteur_qui_ouvre_les_prets_est_redirige_vers_la_connexion(): void
    {
        $this->get('/prets')->assertRedirect('/connexion');
        $this->get('/')->assertRedirect('/tableau-de-bord');
        $this->get('/tableau-de-bord')->assertRedirect('/connexion');
    }

    /** 5.7 : « Élève A ouvre l'URL du prêt de l'élève B → 403 + ligne dans le journal » (RA-03, RA-08). */
    public function test_un_eleve_qui_ouvre_le_pret_d_un_autre_eleve_recoit_403_et_c_est_journalise(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');
        $pretDeBruno = $this->pretDe($this->utilisateur('eleve2@lycee.test'));

        $this->actingAs($emma)->get('/prets/' . $pretDeBruno->id)->assertForbidden();

        $this->assertDatabaseHas('journal', [
            'utilisateur_id' => $emma->id,
            'action' => Journal::ACCES_REFUSE,
            'objet' => 'GET /prets/' . $pretDeBruno->id,
        ]);
    }

    /** 5.7 : « Élève envoie une requête de validation de prêt → 403 ». */
    public function test_un_eleve_ne_peut_valider_aucun_pret(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');
        $pretDEmma = $this->pretDe($emma);
        $pretDeBruno = $this->pretDe($this->utilisateur('eleve2@lycee.test'));

        $this->actingAs($emma)->patch('/prets/' . $pretDEmma->id . '/valider')->assertForbidden();
        $this->actingAs($emma)->patch('/prets/' . $pretDeBruno->id . '/valider')->assertForbidden();

        $this->assertSame(Pret::EN_ATTENTE, $pretDEmma->fresh()->statut);
        $this->assertSame(Pret::EN_ATTENTE, $pretDeBruno->fresh()->statut);
    }

    /** 5.7 : « Enseignant valide la demande d'un élève d'une autre classe → 403 ». */
    public function test_un_enseignant_ne_peut_pas_valider_un_eleve_d_une_autre_classe(): void
    {
        $eric = $this->utilisateur('enseignant@lycee.test');               // classe 1re STMG 2
        $pretDeBruno = $this->pretDe($this->utilisateur('eleve2@lycee.test')); // classe 2nde 3

        $this->actingAs($eric)->patch('/prets/' . $pretDeBruno->id . '/valider')->assertForbidden();

        $this->assertSame(Pret::EN_ATTENTE, $pretDeBruno->fresh()->statut);
    }

    /** 5.7 : « Gestionnaire valide sa propre demande → Refusé (RA-04) ». */
    public function test_un_gestionnaire_ne_peut_pas_valider_sa_propre_demande(): void
    {
        $gaelle = $this->utilisateur('gestionnaire@lycee.test');
        $demandeDeGaelle = $this->creerPret($gaelle);

        $this->actingAs($gaelle)->patch('/prets/' . $demandeDeGaelle->id . '/valider')->assertForbidden();
        $this->actingAs($gaelle)
            ->patch('/prets/' . $demandeDeGaelle->id . '/refuser', ['motif_refus' => 'test'])
            ->assertForbidden();

        $demandeDeGaelle->refresh();
        $this->assertSame(Pret::EN_ATTENTE, $demandeDeGaelle->statut);
        $this->assertNull($demandeDeGaelle->validateur_id);
    }

    /** 5.7 : « Administrateur tente de retirer son propre rôle → Refusé (RA-05) ». */
    public function test_un_administrateur_ne_peut_pas_retirer_son_propre_role(): void
    {
        $adam = $this->utilisateur('admin@lycee.test');
        $idRoleEleve = Role::where('nom', Role::ELEVE)->value('id');

        $this->actingAs($adam)
            ->patch('/comptes/' . $adam->id . '/role', ['role_id' => $idRoleEleve])
            ->assertSessionHasErrors(['action' => 'Vous ne pouvez pas changer votre propre rôle.']);

        $this->assertTrue($adam->fresh()->aLeRole(Role::ADMINISTRATEUR));
    }

    /**
     * Multi-établissement : le rôle super_admin voit tous les établissements. Un
     * administrateur ne doit pas pouvoir le donner, même en envoyant son id à la
     * main (il n'est pas dans le menu). Seule la commande artisan --super le crée.
     */
    public function test_un_administrateur_ne_peut_pas_donner_le_role_super_admin(): void
    {
        $adam = $this->utilisateur('admin@lycee.test');
        $emma = $this->utilisateur('eleve@lycee.test');
        $idRoleSuperAdmin = Role::where('nom', Role::SUPER_ADMIN)->value('id');

        $this->actingAs($adam)
            ->patch('/comptes/' . $emma->id . '/role', ['role_id' => $idRoleSuperAdmin])
            ->assertSessionHasErrors(['role_id' => 'Ce rôle n\'existe pas.']);

        $this->actingAs($adam)->post('/comptes', [
            'nom' => 'Pirate', 'prenom' => 'Test', 'email' => 'pirate@lycee.test', 'role_id' => $idRoleSuperAdmin,
        ])->assertSessionHasErrors('role_id');

        $this->assertTrue($emma->fresh()->aLeRole(Role::ELEVE));
        $this->assertDatabaseMissing('utilisateurs', ['email' => 'pirate@lycee.test']);
        $this->actingAs($adam)->get('/comptes/' . $emma->id . '/modifier')->assertDontSee('Super-administrateur');
    }

    /**
     * 5.7 : « Rôle d'un élève changé pendant qu'il est connecté → Sa session est fermée » (RA-06).
     *
     * Pendant les tests, Laravel garde l'utilisateur connecté en mémoire : on ne
     * peut pas simuler « Emma recharge sa page ». On vérifie donc la cause de la
     * déconnexion : sa ligne dans la table sessions est supprimée, et seulement la sienne.
     */
    public function test_changer_le_role_d_un_eleve_ferme_ses_sessions_et_seulement_les_siennes(): void
    {
        $adam = $this->utilisateur('admin@lycee.test');
        $emma = $this->utilisateur('eleve@lycee.test');
        $bruno = $this->utilisateur('eleve2@lycee.test');
        $this->creerSession('session-emma', $emma->id);
        $this->creerSession('session-bruno', $bruno->id);

        $this->actingAs($adam)
            ->patch('/comptes/' . $emma->id . '/role', ['role_id' => Role::where('nom', Role::ENSEIGNANT)->value('id')])
            ->assertSessionHasNoErrors();

        $this->assertTrue($emma->fresh()->aLeRole(Role::ENSEIGNANT));
        $this->assertDatabaseMissing('sessions', ['user_id' => $emma->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'session-bruno', 'user_id' => $bruno->id]);
        $this->assertDatabaseHas('journal', ['action' => Journal::ROLE_CHANGE, 'utilisateur_id' => $adam->id]);
    }

    /** 5.7 : « Compte désactivé tente de se connecter → Connexion refusée, message générique ». */
    public function test_un_compte_desactive_ne_peut_pas_se_connecter_et_le_message_est_generique(): void
    {
        $bruno = $this->utilisateur('eleve2@lycee.test');
        $bruno->actif = false;
        $bruno->save();

        // Bon mot de passe, mais compte désactivé.
        $this->post('/connexion', ['email' => 'eleve2@lycee.test', 'mot_de_passe' => 'Eleve2-Test-2026'])
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
        $this->assertGuest();

        // Même message qu'un e-mail inconnu (ES-04) : on ne peut pas deviner que le compte existe.
        $this->post('/connexion', ['email' => 'personne@lycee.test', 'mot_de_passe' => 'nimporte-quoi'])
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
        $this->assertGuest();
    }

    // ─── Tests en plus ─────────────────────────────────────────────────────

    /** Un élève qui tape l'adresse d'une page de gestion reçoit 403 (RA-01, RA-02). */
    public function test_un_eleve_recoit_403_sur_les_pages_de_gestion(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');

        foreach (['/comptes', '/comptes/nouveau', '/journal', '/materiels/nouveau'] as $adresse) {
            $this->actingAs($emma)->get($adresse)->assertForbidden();
        }

        $this->actingAs($emma)
            ->post('/materiels', ['nom' => 'Pirate', 'categorie' => 'X', 'numero_inventaire' => 'H-1', 'etat' => 'disponible'])
            ->assertForbidden();
        $this->assertDatabaseMissing('materiels', ['numero_inventaire' => 'H-1']);
    }

    /**
     * Anti-IDOR (étape 6) : un prêt inexistant et le prêt d'un autre donnent
     * EXACTEMENT la même réponse. On ne peut donc pas deviner quels prêts existent.
     */
    public function test_pret_inexistant_et_pret_d_un_autre_donnent_la_meme_reponse(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');
        $pretDeBruno = $this->pretDe($this->utilisateur('eleve2@lycee.test'));

        $reponsePretDeBruno = $this->actingAs($emma)->get('/prets/' . $pretDeBruno->id);
        $reponsePretInexistant = $this->actingAs($emma)->get('/prets/999999');

        $reponsePretDeBruno->assertForbidden();
        $reponsePretInexistant->assertForbidden();
        $this->assertSame($reponsePretDeBruno->getContent(), $reponsePretInexistant->getContent());
    }

    /** RA-05, 2e partie : un administrateur ne peut pas désactiver son propre compte. */
    public function test_un_administrateur_ne_peut_pas_se_desactiver(): void
    {
        $adam = $this->utilisateur('admin@lycee.test');

        $this->actingAs($adam)
            ->patch('/comptes/' . $adam->id . '/desactiver')
            ->assertSessionHasErrors(['action' => 'Vous ne pouvez pas désactiver votre propre compte.']);

        $this->assertTrue($adam->fresh()->actif);
    }

    /**
     * Cas AUTORISÉS. Sans eux, une application qui refuserait tout passerait
     * tous les tests de refus ci-dessus.
     */
    public function test_les_validations_autorisees_fonctionnent(): void
    {
        $eric = $this->utilisateur('enseignant@lycee.test');
        $gaelle = $this->utilisateur('gestionnaire@lycee.test');
        $pretDEmma = $this->pretDe($this->utilisateur('eleve@lycee.test'));
        $pretDEric = $this->pretDe($eric);

        // L'enseignant voit et valide la demande d'une élève de sa classe.
        $this->actingAs($eric)->get('/prets/' . $pretDEmma->id)->assertOk();
        $this->actingAs($eric)->patch('/prets/' . $pretDEmma->id . '/valider')->assertRedirect();
        $this->assertSame(Pret::VALIDE, $pretDEmma->fresh()->statut);
        $this->assertSame($eric->id, $pretDEmma->fresh()->validateur_id);

        // La demande de l'enseignant est validée par un gestionnaire (RA-04).
        $this->actingAs($gaelle)->patch('/prets/' . $pretDEric->id . '/valider')->assertRedirect();
        $this->assertSame(Pret::VALIDE, $pretDEric->fresh()->statut);
    }

    /** Crée une ligne dans la table sessions, comme si l'utilisateur était connecté ailleurs. */
    private function creerSession(string $idSession, int $idUtilisateur): void
    {
        DB::table('sessions')->insert([
            'id' => $idSession,
            'user_id' => $idUtilisateur,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => time(),
        ]);
    }
}
