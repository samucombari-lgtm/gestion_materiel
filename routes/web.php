<?php

/**
 * Routes web de l'application.
 *
 * Trois groupes :
 *  - "guest" : pages réservées aux visiteurs non connectés (la connexion).
 *    Un utilisateur déjà connecté est renvoyé vers le tableau de bord.
 *  - "auth" : pages accessibles dès qu'on est connecté, même avec un mot de
 *    passe temporaire (changement du mot de passe, déconnexion).
 *  - "auth" + "mot-de-passe.change" : tout le reste. Un utilisateur qui doit
 *    changer son mot de passe temporaire est renvoyé vers /mot-de-passe (section 5.6).
 * Les adresses de redirection et le nom des middlewares sont dans bootstrap/app.php.
 */

use App\Http\Controllers\Auth\ConnexionController;
use App\Http\Controllers\Auth\MotDePasseController;
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

    Route::get('/mot-de-passe', [MotDePasseController::class, 'afficherFormulaire'])->name('mot-de-passe');
    Route::post('/mot-de-passe', [MotDePasseController::class, 'changer'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'mot-de-passe.change'])->group(function () {
    Route::view('/tableau-de-bord', 'tableau-de-bord')->name('tableau-de-bord');

    // can:xxx = le Gate "xxx" doit répondre oui, sinon Laravel renvoie une erreur 403
    // (page resources/views/errors/403.blade.php). Contrôle côté serveur : RA-02.
    // Pages provisoires : leur contenu arrive aux étapes 5 (matériel) et 7 (comptes, journal).
    Route::view('/materiels', 'a-venir', ['titre' => 'Catalogue du matériel'])
        ->middleware('can:materiel.voir')->name('materiels');
    Route::view('/comptes', 'a-venir', ['titre' => 'Gestion des comptes'])
        ->middleware('can:compte.gerer')->name('comptes');
    Route::view('/journal', 'a-venir', ['titre' => 'Journal de sécurité'])
        ->middleware('can:journal.voir')->name('journal');
});
