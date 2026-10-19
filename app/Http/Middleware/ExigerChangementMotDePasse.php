<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Oblige l'utilisateur à changer son mot de passe temporaire (cahier des charges, section 5.6).
 *
 * Un middleware est un filtre exécuté avant le contrôleur. Celui-ci regarde la
 * colonne doit_changer_mot_de_passe de l'utilisateur connecté : si elle vaut true,
 * toute page est remplacée par une redirection vers /mot-de-passe.
 *
 * Il est placé dans routes/web.php sur toutes les routes, sauf la page de
 * changement elle-même et la déconnexion (sinon l'utilisateur serait coincé).
 */
class ExigerChangementMotDePasse
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if ($utilisateur !== null && $utilisateur->doit_changer_mot_de_passe) {
            return redirect()->route('mot-de-passe');
        }

        return $next($request);
    }
}
