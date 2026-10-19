<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Connexion et déconnexion (BF-01, BF-02).
 *
 * Sécurité appliquée ici :
 *  - ES-01 : mots de passe vérifiés avec Hash::check() (bcrypt), jamais comparés en clair ;
 *  - ES-03 : blocage 15 minutes après 5 échecs ;
 *  - ES-04 : un seul message d'erreur, que l'e-mail ou le mot de passe soit faux ;
 *  - ES-08 : nouvel identifiant de session après la connexion ;
 *  - ES-10 : validation des champs reçus ;
 *  - section 6 : chaque connexion réussie ou échouée est écrite dans le journal
 *    (avec l'e-mail tapé et la raison, jamais le mot de passe).
 *
 * Point clé : un pirate ne doit pas pouvoir deviner si un e-mail a un compte.
 * C'est pourquoi un e-mail inconnu se comporte exactement comme un e-mail connu :
 * même message, même blocage après 5 essais, même temps de réponse.
 */
class ConnexionController extends Controller
{
    private const MESSAGE_IDENTIFIANTS_INCORRECTS = 'Identifiants incorrects.';
    private const MESSAGE_TROP_DE_TENTATIVES = 'Trop de tentatives. Réessayez dans quelques minutes.';

    /**
     * Hachage bcrypt d'un mot de passe qui n'existe nulle part. Vérifier un mot de
     * passe contre lui prend le même temps que pour un vrai compte (environ 0,2 s) :
     * sans ça, une réponse plus rapide trahirait un e-mail inconnu.
     */
    private const FAUX_HACHAGE = '$2y$12$7k2rRAuXOHNyonEW1moiveFgXfE7Bqk8etgc.9qxRQ8JHs1rRhJmi';

    public function afficherFormulaire(): View
    {
        return view('auth.connexion');
    }

    public function connecter(Request $request): RedirectResponse
    {
        $champs = $request->validate(
            [
                'email' => ['required', 'string', 'email', 'max:255'],
                'mot_de_passe' => ['required', 'string', 'max:255'],
            ],
            [
                'email.required' => "L'adresse e-mail est obligatoire.",
                'email.email' => "L'adresse e-mail n'est pas valide.",
                'email.max' => "L'adresse e-mail est trop longue.",
                'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
                'mot_de_passe.max' => 'Le mot de passe est trop long.',
            ]
        );

        $email = Str::lower($champs['email']);
        $motDePasse = $champs['mot_de_passe'];

        $utilisateur = Utilisateur::where('email', $email)->first();

        if ($utilisateur === null) {
            return $this->refuserEmailInconnu($email, $motDePasse);
        }

        // Pour un compte connu, la ligne du journal est rattachée au compte visé.
        // Le message à l'écran reste générique (ES-04) : seul l'admin voit la raison.
        if ($utilisateur->estBloque()) {
            Journal::enregistrer(Journal::CONNEXION_ECHOUEE, 'Compte bloqué : ' . $email, $utilisateur);
            return $this->refuser(self::MESSAGE_TROP_DE_TENTATIVES);
        }

        if (! Hash::check($motDePasse, $utilisateur->getAuthPassword())) {
            $utilisateur->enregistrerEchecConnexion();
            Journal::enregistrer(Journal::CONNEXION_ECHOUEE, 'Mauvais mot de passe : ' . $email, $utilisateur);

            if ($utilisateur->estBloque()) {
                Journal::enregistrer(Journal::COMPTE_BLOQUE, $email, $utilisateur);
            }

            return $this->refuser(self::MESSAGE_IDENTIFIANTS_INCORRECTS);
        }

        // Bon mot de passe mais compte désactivé : même message générique (section 5.7).
        if (! $utilisateur->actif) {
            Journal::enregistrer(Journal::CONNEXION_ECHOUEE, 'Compte désactivé : ' . $email, $utilisateur);
            return $this->refuser(self::MESSAGE_IDENTIFIANTS_INCORRECTS);
        }

        $utilisateur->reinitialiserEchecsConnexion();
        Auth::login($utilisateur);

        // Nouvel identifiant de session : un identifiant volé ou imposé avant la
        // connexion ne sert plus à rien (fixation de session, ES-08).
        $request->session()->regenerate();

        Journal::enregistrer(Journal::CONNEXION_REUSSIE, $email, $utilisateur);

        return redirect()->route('tableau-de-bord');
    }

    public function deconnecter(Request $request): RedirectResponse
    {
        Auth::logout();

        // On détruit la session et on change le jeton CSRF : rien de l'ancienne
        // session ne peut être réutilisé.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connexion');
    }

    /**
     * E-mail sans compte : pas de colonne echecs_connexion où compter les essais,
     * on les compte donc avec le RateLimiter de Laravel (stocké dans la table cache).
     * Résultat identique à un vrai compte : 5 essais, puis 15 minutes de blocage.
     */
    private function refuserEmailInconnu(string $email, string $motDePasse): RedirectResponse
    {
        $cle = 'connexion-email-inconnu:' . $email;

        // E-mail inconnu : pas d'auteur connu, la ligne du journal n'a pas d'utilisateur.
        Journal::enregistrer(Journal::CONNEXION_ECHOUEE, 'Compte inconnu : ' . $email);

        if (RateLimiter::tooManyAttempts($cle, Utilisateur::NOMBRE_MAX_ECHECS)) {
            return $this->refuser(self::MESSAGE_TROP_DE_TENTATIVES);
        }

        Hash::check($motDePasse, self::FAUX_HACHAGE);
        RateLimiter::hit($cle, Utilisateur::DUREE_BLOCAGE_MINUTES * 60);

        return $this->refuser(self::MESSAGE_IDENTIFIANTS_INCORRECTS);
    }

    /** Retour au formulaire avec le message ; on garde l'e-mail saisi, jamais le mot de passe. */
    private function refuser(string $message): RedirectResponse
    {
        return back()
            ->withErrors(['email' => $message])
            ->onlyInput('email');
    }
}
