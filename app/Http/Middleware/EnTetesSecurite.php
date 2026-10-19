<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajoute les en-têtes de sécurité HTTP à TOUTES les réponses de l'application
 * (pages, redirections, pages d'erreur). Corrige les alertes de l'audit OWASP ZAP.
 *
 * Un en-tête HTTP est une information envoyée par le serveur AVANT la page :
 * l'utilisateur ne le voit pas, mais le navigateur le lit et applique les règles.
 * On les voit avec F12 → onglet Réseau → clic sur une requête → En-têtes de réponse.
 *
 * Enregistré dans bootstrap/app.php avec $middleware->append() : il s'exécute
 * pour chaque requête, après que le contrôleur a fabriqué la réponse.
 *
 * Les fichiers de public/ (CSS, JS) ne passent pas par Laravel : en production,
 * Apache doit leur ajouter les mêmes en-têtes (voir README, « Mise en production »).
 */
class EnTetesSecurite
{
    /**
     * Politique de sécurité du contenu (CSP) : d'où le navigateur a le droit de
     * charger chaque type de ressource. 'self' = uniquement depuis notre serveur.
     * Tout ce qui n'est pas listé est interdit.
     *
     * Volontairement ABSENT : 'unsafe-inline'. Le navigateur refuse donc tout script
     * ou style écrit directement dans le HTML (<script>…</script>, onclick="…",
     * style="…"). Même si un attaquant réussissait à injecter du HTML (XSS), son
     * script ne serait pas exécuté. Nos scripts sont dans des fichiers à part
     * (public/js/confirmation.js).
     */
    private const CONTENT_SECURITY_POLICY = [
        "default-src 'self'",       // règle par défaut pour tout ce qui n'est pas précisé
        "script-src 'self'",        // JavaScript : seulement nos fichiers .js
        "style-src 'self'",         // CSS : seulement nos fichiers .css
        "img-src 'self'",           // images : seulement depuis notre serveur
        "font-src 'self'",          // polices : idem
        "connect-src 'self'",       // requêtes faites par JavaScript : idem
        "object-src 'none'",        // aucun plugin (Flash, applets…)
        "base-uri 'self'",          // empêche de détourner les liens relatifs avec <base>
        "form-action 'self'",       // les formulaires ne peuvent envoyer qu'à notre serveur
        "frame-ancestors 'none'",   // personne ne peut afficher nos pages dans un cadre
    ];

    /** HSTS : 31 536 000 secondes = 1 an. */
    private const DUREE_HSTS_EN_SECONDES = 31536000;

    public function handle(Request $request, Closure $next): Response
    {
        $reponse = $next($request);

        // Clickjacking : interdit d'afficher nos pages dans un cadre (<iframe>) sur un
        // autre site, où un bouton invisible ferait cliquer l'utilisateur à son insu.
        // (frame-ancestors dans la CSP fait la même chose pour les navigateurs récents.)
        $reponse->headers->set('X-Frame-Options', 'DENY');

        // Le navigateur doit croire le type annoncé par le serveur (text/css, text/html…)
        // et ne pas « deviner » : un fichier texte ne sera jamais exécuté comme un script.
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');

        // Quand on clique un lien vers un AUTRE site, le navigateur n'envoie pas
        // l'adresse d'où l'on vient (ex. /prets/12). Entre nos pages, elle reste envoyée.
        $reponse->headers->set('Referrer-Policy', 'same-origin');

        if ($this->doitAjouterLaCsp($reponse)) {
            $reponse->headers->set('Content-Security-Policy', implode('; ', self::CONTENT_SECURITY_POLICY));
        }

        // HSTS : « ce site, c'est TOUJOURS en HTTPS, pendant 1 an ». Le navigateur
        // refusera ensuite de s'y connecter en HTTP, même si on tape http://.
        // Seulement si la requête est déjà en HTTPS : en HTTP (développement local),
        // l'en-tête serait inutile, et en HTTPS mal configuré il bloquerait le site.
        // Pas de « includeSubDomains » : le domaine n'est pas encore connu, et cette
        // option forcerait le HTTPS sur tous les autres sites du même domaine.
        if ($request->isSecure()) {
            $reponse->headers->set('Strict-Transport-Security', 'max-age=' . self::DUREE_HSTS_EN_SECONDES);
        }

        // X-Powered-By: PHP/8.4.26 est ajouté par PHP lui-même : il révèle la
        // technologie et sa version exacte, ce qui aide un attaquant à chercher des
        // failles connues. header_remove() le retire avant l'envoi de la réponse.
        // En production, mettre aussi « expose_php = Off » dans php.ini.
        header_remove('X-Powered-By');
        $reponse->headers->remove('X-Powered-By');

        return $reponse;
    }

    /**
     * En développement (APP_DEBUG=true), la page d'erreur détaillée de Laravel
     * (erreur 500) a besoin de scripts écrits dans la page : avec la CSP, elle
     * serait illisible et on ne pourrait plus déboguer. On ne met donc pas de CSP
     * sur CETTE page-là, et seulement en développement. En production
     * (APP_DEBUG=false), la CSP est sur toutes les réponses.
     */
    private function doitAjouterLaCsp(Response $reponse): bool
    {
        $estUnePageDErreurDeDeveloppement = config('app.debug') && $reponse->getStatusCode() >= 500;

        return ! $estUnePageDErreurDeDeveloppement;
    }
}
