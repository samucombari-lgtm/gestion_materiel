<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
