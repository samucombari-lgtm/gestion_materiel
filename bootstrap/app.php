<?php

use App\Models\Journal;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Visiteur non connecté sur une page protégée (middleware "auth") → page de connexion.
        $middleware->redirectGuestsTo('/connexion');
        // Utilisateur déjà connecté sur la page de connexion (middleware "guest") → tableau de bord.
        $middleware->redirectUsersTo('/tableau-de-bord');

        // En-têtes de sécurité HTTP sur TOUTES les réponses (audit OWASP ZAP).
        $middleware->append(\App\Http\Middleware\EnTetesSecurite::class);

        // Nom court pour utiliser nos middlewares dans routes/web.php.
        $middleware->alias([
            'mot-de-passe.change' => \App\Http\Middleware\ExigerChangementMotDePasse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // RA-08 : chaque accès refusé (403) est écrit dans le journal de sécurité.
        // Un seul endroit pour tous les 403 : middleware can:, Gate::authorize(), abort(403).
        // On renvoie null : Laravel affiche ensuite normalement la page errors/403.blade.php.
        $exceptions->render(function (HttpExceptionInterface $erreur, Request $request) {
            if ($erreur->getStatusCode() !== 403) {
                return null;
            }

            try {
                Journal::enregistrer(Journal::ACCES_REFUSE, $request->method() . ' /' . $request->path());
            } catch (Throwable $erreurJournal) {
                // Si l'écriture échoue (base indisponible…), on garde quand même la page 403
                // et on note le problème dans storage/logs/laravel.log.
                report($erreurJournal);
            }

            return null;
        });
    })->create();
