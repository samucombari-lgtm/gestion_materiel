<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du middleware EnTetesSecurite (correction de l'audit OWASP ZAP).
 *
 * Vérifie que les en-têtes de sécurité sont présents sur une page publique,
 * une page connectée et une page d'erreur, et que HSTS n'est envoyé qu'en HTTPS.
 *
 * Lancer : php artisan test --filter=EnTetesSecuriteTest
 */
class EnTetesSecuriteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const EN_TETES_ATTENDUS = [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'same-origin',
    ];

    public function test_les_en_tetes_sont_presents_sur_toutes_les_sortes_de_pages(): void
    {
        $emma = $this->utilisateur('eleve@lycee.test');

        $reponses = [
            'page publique' => $this->get('/connexion'),
            'page connectée' => $this->actingAs($emma)->get('/materiels'),
            'page 403' => $this->actingAs($emma)->get('/journal'),
            'redirection' => $this->get('/'),
        ];

        foreach ($reponses as $sorteDePage => $reponse) {
            foreach (self::EN_TETES_ATTENDUS as $nom => $valeur) {
                $this->assertSame($valeur, $reponse->headers->get($nom), "$nom absent ou faux sur : $sorteDePage");
            }

            $csp = $reponse->headers->get('Content-Security-Policy');
            $this->assertNotNull($csp, "CSP absente sur : $sorteDePage");
            $this->assertStringContainsString("script-src 'self'", $csp);
            $this->assertStringContainsString("frame-ancestors 'none'", $csp);
            $this->assertStringNotContainsString('unsafe-inline', $csp);

            $this->assertFalse($reponse->headers->has('X-Powered-By'), "X-Powered-By présent sur : $sorteDePage");
        }
    }

    public function test_hsts_n_est_envoye_qu_en_https(): void
    {
        $this->get('http://localhost/connexion')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/connexion')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_aucune_vue_ne_contient_de_javascript_ou_de_css_dans_le_html(): void
    {
        // La CSP bloquerait ce code : on vérifie qu'aucune vue n'en contient.
        $vues = glob(resource_path('views/{,*/}*.blade.php'), GLOB_BRACE);
        $this->assertNotEmpty($vues);

        foreach ($vues as $vue) {
            $contenu = file_get_contents($vue);
            $nom = basename(dirname($vue)) . '/' . basename($vue);

            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $contenu, "Gestionnaire onxxx= dans $nom");
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\ssrc=)/i', $contenu, "<script> sans src dans $nom");
            $this->assertDoesNotMatchRegularExpression('/<style|\sstyle\s*=/i', $contenu, "CSS dans le HTML de $nom");
        }
    }
}
