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
use App\Http\Controllers\JournalController;
use App\Http\Controllers\MaterielController;
use App\Http\Controllers\PretController;
use App\Http\Controllers\TableauDeBordController;
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
    // Tableau de bord adapté au rôle (BF-30).
    Route::get('/tableau-de-bord', [TableauDeBordController::class, 'afficher'])->name('tableau-de-bord');

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

    // Prêts (BF-20 à BF-24). {id} : un nombre de 1 à 9 chiffres (un nombre plus long dépasserait
    // la taille d'un entier PHP et provoquerait une erreur).
    // Les actions sur un prêt précis sont contrôlées par PretPolicy dans le contrôleur :
    // prêt inexistant ou hors périmètre → même erreur 403 (protection contre l'IDOR).
    Route::middleware('can:pret.voir')->group(function () {
        Route::get('/prets', [PretController::class, 'index'])->name('prets.index');
        Route::get('/prets/{id}', [PretController::class, 'show'])->where('id', '[0-9]{1,9}')->name('prets.show');
    });

    Route::middleware('can:pret.demander')->group(function () {
        Route::get('/prets/nouveau', [PretController::class, 'create'])->name('prets.create');
        Route::post('/prets', [PretController::class, 'store'])->name('prets.store');
    });

    Route::patch('/prets/{id}/valider', [PretController::class, 'valider'])->where('id', '[0-9]{1,9}')->name('prets.valider');
    Route::patch('/prets/{id}/refuser', [PretController::class, 'refuser'])->where('id', '[0-9]{1,9}')->name('prets.refuser');
    Route::patch('/prets/{id}/annuler', [PretController::class, 'annuler'])->where('id', '[0-9]{1,9}')->name('prets.annuler');
    Route::patch('/prets/{id}/sortie', [PretController::class, 'sortir'])->where('id', '[0-9]{1,9}')->name('prets.sortir');
    Route::patch('/prets/{id}/retour', [PretController::class, 'rendre'])->where('id', '[0-9]{1,9}')->name('prets.rendre');

    // Journal de sécurité (BF-31) : lecture seule, une seule route en GET.
    Route::get('/journal', [JournalController::class, 'index'])
        ->middleware('can:journal.voir')->name('journal');

    // Page provisoire : la gestion des comptes (BF-04) n'est pas encore construite.
    Route::view('/comptes', 'a-venir', ['titre' => 'Gestion des comptes'])
        ->middleware('can:compte.gerer')->name('comptes');
});
