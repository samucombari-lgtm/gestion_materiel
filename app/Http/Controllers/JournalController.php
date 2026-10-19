<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Consultation du journal de sécurité (BF-31), réservée à l'administrateur
 * (permission journal.voir, vérifiée par le middleware can: dans routes/web.php).
 *
 * Lecture seule : ce contrôleur n'a qu'une méthode d'affichage. Il n'existe
 * aucune route pour modifier ou supprimer une ligne du journal.
 */
class JournalController extends Controller
{
    private const NOMBRE_PAR_PAGE = 50;

    /** Liste filtrable : par action, par texte (e-mail ou objet), par période. */
    public function index(Request $request): View
    {
        $filtres = $request->validate(
            [
                'action' => ['nullable', Rule::in(array_keys(Journal::LIBELLES_ACTIONS))],
                'recherche' => ['nullable', 'string', 'max:100'],
                'du' => ['nullable', 'date_format:Y-m-d'],
                'au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:du'],
            ],
            [
                'action.in' => 'Cette action n\'existe pas.',
                'recherche.max' => 'La recherche ne doit pas dépasser 100 caractères.',
                'du.date_format' => 'La date de début n\'est pas valide.',
                'au.date_format' => 'La date de fin n\'est pas valide.',
                'au.after_or_equal' => 'La date de fin doit être après la date de début.',
            ]
        );

        $requete = Journal::with('utilisateur')->orderByDesc('date_heure')->orderByDesc('id');

        if (! empty($filtres['action'])) {
            $requete->where('action', $filtres['action']);
        }

        if (! empty($filtres['recherche'])) {
            $texte = '%' . $filtres['recherche'] . '%';
            // Cherche dans l'objet (ex. e-mail d'une connexion échouée) ou dans l'e-mail de l'auteur.
            $requete->where(function ($sousRequete) use ($texte) {
                $sousRequete->where('objet', 'like', $texte)
                    ->orWhereHas('utilisateur', function ($utilisateur) use ($texte) {
                        $utilisateur->where('email', 'like', $texte);
                    });
            });
        }

        if (! empty($filtres['du'])) {
            $requete->whereDate('date_heure', '>=', $filtres['du']);
        }

        if (! empty($filtres['au'])) {
            $requete->whereDate('date_heure', '<=', $filtres['au']);
        }

        return view('journal.index', [
            'lignes' => $requete->paginate(self::NOMBRE_PAR_PAGE)->withQueryString(),
            'filtres' => $filtres,
        ]);
    }
}
