<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Changement du mot de passe par l'utilisateur connecté.
 *
 * Sert surtout à la première connexion (mot de passe temporaire, section 5.6),
 * mais un utilisateur peut aussi l'utiliser quand il veut changer son mot de passe.
 *
 * Sécurité :
 *  - le mot de passe actuel est redemandé : quelqu'un qui aurait volé la session
 *    ne peut pas prendre le contrôle du compte ;
 *  - 12 caractères minimum (ES-02), 72 maximum (au-delà, bcrypt ignore la fin) ;
 *  - hachage avec Hash::make() dans le modèle (ES-01) ;
 *  - nouvel identifiant de session et fermeture des autres sessions du compte.
 */
class MotDePasseController extends Controller
{
    public function afficherFormulaire(Request $request): View
    {
        return view('auth.changer-mot-de-passe', [
            'changementObligatoire' => $request->user()->doit_changer_mot_de_passe,
        ]);
    }

    public function changer(Request $request): RedirectResponse
    {
        $champs = $request->validate(
            [
                // current_password : Laravel compare avec le hachage de l'utilisateur connecté.
                'mot_de_passe_actuel' => ['required', 'string', 'current_password'],
                // confirmed : le champ nouveau_mot_de_passe_confirmation doit être identique.
                'nouveau_mot_de_passe' => [
                    'required', 'string', 'max:72', 'confirmed',
                    'different:mot_de_passe_actuel', Password::min(12),
                ],
            ],
            [
                'mot_de_passe_actuel.required' => 'Le mot de passe actuel est obligatoire.',
                'mot_de_passe_actuel.current_password' => 'Le mot de passe actuel est incorrect.',
                'nouveau_mot_de_passe.required' => 'Le nouveau mot de passe est obligatoire.',
                'nouveau_mot_de_passe.min' => 'Le nouveau mot de passe doit contenir au moins 12 caractères.',
                'nouveau_mot_de_passe.max' => 'Le nouveau mot de passe ne doit pas dépasser 72 caractères.',
                'nouveau_mot_de_passe.confirmed' => 'Les deux nouveaux mots de passe ne sont pas identiques.',
                'nouveau_mot_de_passe.different' => "Le nouveau mot de passe doit être différent de l'actuel.",
            ]
        );

        $utilisateur = $request->user();
        $utilisateur->changerMotDePasse($champs['nouveau_mot_de_passe']);

        $request->session()->regenerate();
        $utilisateur->fermerSessions(sauf: $request->session()->getId());

        Journal::enregistrer(Journal::MOT_DE_PASSE_CHANGE, $utilisateur->email);

        return redirect()
            ->route('tableau-de-bord')
            ->with('succes', 'Votre mot de passe a été modifié.');
    }
}
