<?php

namespace Tests\Feature;

use App\Models\Pret;
use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * La règle « qui peut voir quel prêt » est écrite à deux endroits :
 *  - Pret::visiblesPar()  : en SQL, pour la LISTE des prêts ;
 *  - PretPolicy::view()   : en PHP, pour le DÉTAIL d'un prêt.
 *
 * Si quelqu'un modifie l'une sans l'autre, un prêt pourrait apparaître dans la
 * liste mais donner 403 au clic, ou pire : être visible en détail sans
 * apparaître dans la liste. Ce test vérifie, pour CHAQUE utilisateur et CHAQUE
 * prêt, que les deux répondent exactement la même chose.
 *
 * Lancer : php artisan test --filter=CoherenceVisibilitePretsTest
 */
class CoherenceVisibilitePretsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_la_liste_et_le_detail_donnent_toujours_la_meme_reponse(): void
    {
        $this->ajouterDesCasDifficiles();

        $utilisateurs = Utilisateur::with('role')->get();
        $prets = Pret::all();
        $ecarts = [];

        foreach ($utilisateurs as $utilisateur) {
            $idsVisiblesDansLaListe = Pret::visiblesPar($utilisateur)->pluck('id')->all();

            foreach ($prets as $pret) {
                $visibleDansLaListe = in_array($pret->id, $idsVisiblesDansLaListe, true);
                $visibleEnDetail = Gate::forUser($utilisateur)->allows('view', $pret);

                if ($visibleDansLaListe !== $visibleEnDetail) {
                    $ecarts[] = $utilisateur->email . ' / prêt n° ' . $pret->id
                        . ' : liste=' . ($visibleDansLaListe ? 'oui' : 'non')
                        . ', détail=' . ($visibleEnDetail ? 'oui' : 'non');
                }
            }
        }

        $this->assertSame([], $ecarts, "La liste et le détail ne sont pas d'accord :\n" . implode("\n", $ecarts));

        // Garde-fou : le test ne doit pas réussir « par hasard » parce que personne ne voit rien.
        $this->assertGreaterThan(0, Pret::visiblesPar($this->utilisateur('gestionnaire@lycee.test'))->count());
        $this->assertGreaterThan(0, Pret::visiblesPar($this->utilisateur('eleve@lycee.test'))->count());
    }

    /**
     * Ajoute des situations qui piègent facilement une règle de visibilité :
     *  - une élève dans DEUX classes (une avec Eric, une sans) ;
     *  - un 2e enseignant qui n'a que la classe de Bruno ;
     *  - des demandes faites par un enseignant et par un gestionnaire ;
     *  - une élève désactivée qui a un prêt (elle ne doit plus rien voir) ;
     *  - un prêt dans chaque statut.
     */
    private function ajouterDesCasDifficiles(): void
    {
        $chloe = $this->creerUtilisateur('chloe@lycee.test', Role::ELEVE, ['1re STMG 2', '2nde 3']);
        $this->creerUtilisateur('enseignant2@lycee.test', Role::ENSEIGNANT, ['2nde 3']);
        $desactivee = $this->creerUtilisateur('desactivee@lycee.test', Role::ELEVE, ['1re STMG 2']);

        foreach (array_keys(Pret::LIBELLES_STATUTS) as $statut) {
            $this->creerPret($chloe, $statut);
        }
        $this->creerPret($this->utilisateur('gestionnaire@lycee.test'));
        $this->creerPret($this->utilisateur('enseignant@lycee.test'), Pret::SORTI);
        $this->creerPret($desactivee);

        $desactivee->actif = false;
        $desactivee->save();
    }
}
