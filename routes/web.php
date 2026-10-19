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
use App\Http\Controllers\MaterielController;
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

    // Inventaire (BF-10 à BF-12). Pas de route de suppression : on archive (BF-11).
    Route::get('/materiels', [MaterielController::class, 'index'])
        ->middleware('can:materiel.voir')->name('materiels.index');

    Route::middleware('can:materiel.gerer')->group(function () {
        Route::get('/materiels/nouveau', [MaterielController::class, 'create'])->name('materiels.create');
        Route::post('/materiels', [MaterielController::class, 'store'])->name('materiels.store');
        Route::get('/materiels/{materiel}/modifier', [MaterielController::class, 'edit'])->name('materiels.edit');
        Route::put('/materiels/{materiel}', [MaterielController::class, 'update'])->name('materiels.update');
        Route::patch('/materiels/{materiel}/archiver', [MaterielController::class, 'archiver'])->name('materiels.archiver');
    });

    // Pages provisoires : leur contenu arrive à l'étape 7.
    Route::view('/comptes', 'a-venir', ['titre' => 'Gestion des comptes'])
        ->middleware('can:compte.gerer')->name('comptes');
    Route::view('/journal', 'a-venir', ['titre' => 'Journal de sécurité'])
        ->middleware('can:journal.voir')->name('journal');
});
