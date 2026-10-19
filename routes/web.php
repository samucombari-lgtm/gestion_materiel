<?php

/**
 * Routes web de l'application.
 *
 * Deux groupes :
 *  - "guest" : pages réservées aux visiteurs non connectés (la connexion).
 *    Un utilisateur déjà connecté est renvoyé vers le tableau de bord.
 *  - "auth" : tout le reste. Un visiteur est renvoyé vers /connexion.
 * Les adresses de redirection sont réglées dans bootstrap/app.php.
 */

use App\Http\Controllers\Auth\ConnexionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tableau-de-bord');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [ConnexionController::class, 'afficherFormulaire'])->name('connexion');

    // throttle:10,1 = au maximum 10 tentatives par minute depuis une même adresse IP.
    Route::post('/connexion', [ConnexionController::class, 'connecter'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    // En POST (avec @csrf) et pas en GET : un simple lien piégé ne peut pas déconnecter quelqu'un.
    Route::post('/deconnexion', [ConnexionController::class, 'deconnecter'])->name('deconnexion');

    Route::view('/tableau-de-bord', 'tableau-de-bord')->name('tableau-de-bord');
});
