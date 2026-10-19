<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Materiel;
use App\Models\Pret;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Prêts : demande, validation ou refus, annulation, sortie, retour (BF-20 à BF-24).
 *
 * Deux niveaux de contrôle :
 *  1. Le middleware can: dans routes/web.php (pret.voir, pret.demander).
 *  2. PretPolicy pour chaque prêt précis : est-ce le sien, un élève de ses
 *     classes, le bon statut, pas sa propre demande (RA-03, RA-04).
 *
 * Journal (section 6) : validation, refus, annulation, sortie et retour sont
 * écrits dans le journal de sécurité. Les refus d'accès (403) y sont écrits
 * automatiquement par bootstrap/app.php.
 *
 * Protection contre l'IDOR (RA-03) : toutes les actions sur un prêt passent par
 * trouverPretAutorise(). Un prêt qui n'existe pas et un prêt qui appartient à
 * quelqu'un d'autre donnent EXACTEMENT la même réponse (403). On ne peut donc pas
 * deviner quels numéros de prêt existent en essayant des adresses.
 */
class PretController extends Controller
{
    private const NOMBRE_PAR_PAGE = 20;

    /** Liste des prêts que l'utilisateur a le droit de voir (BF-24), avec filtre par statut. */
    public function index(Request $request): View
    {
        $requete = Pret::visiblesPar($request->user())
            ->with(['materiel', 'demandeur', 'validateur'])
            ->orderByDesc('created_at');

        $statutChoisi = (string) $request->query('statut', '');
        if (array_key_exists($statutChoisi, Pret::LIBELLES_STATUTS)) {
            $requete->where('statut', $statutChoisi);
        } else {
            $statutChoisi = '';
        }

        return view('prets.index', [
            'prets' => $requete->paginate(self::NOMBRE_PAR_PAGE)->withQueryString(),
            'statutChoisi' => $statutChoisi,
        ]);
    }

    /** Formulaire de demande (BF-20). ?materiel=12 présélectionne un matériel (bouton « Emprunter »). */
    public function create(Request $request): View
    {
        $utilisateur = $request->user();

        return view('prets.formulaire', [
            'materiels' => Materiel::whereIn('etat', [Materiel::DISPONIBLE, Materiel::PRETE])->orderBy('nom')->get(),
            'materielChoisi' => (int) $request->query('materiel'),
            'estEleve' => $utilisateur->aLeRole(Role::ELEVE),
            'nombrePretsEnCours' => $utilisateur->nombrePretsEnCours(),
            'maxPretsEnCours' => config('prets.max_prets_en_cours_eleve'),
        ]);
    }

    /** Enregistre une demande de prêt (BF-20). */
    public function store(Request $request): RedirectResponse
    {
        $champs = $request->validate(
            [
                // Le matériel doit exister et être disponible ou prêté (réservation pour plus tard).
                'materiel_id' => [
                    'required', 'integer',
                    Rule::exists('materiels', 'id')->whereIn('etat', [Materiel::DISPONIBLE, Materiel::PRETE]),
                ],
                'date_debut' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'date_fin_prevue' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_debut'],
            ],
            [
                'materiel_id.required' => 'Choisissez un matériel.',
                'materiel_id.exists' => 'Ce matériel ne peut pas être emprunté.',
                'date_debut.required' => 'La date de début est obligatoire.',
                'date_debut.date_format' => 'La date de début n\'est pas valide.',
                'date_debut.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
                'date_fin_prevue.required' => 'La date de fin est obligatoire.',
                'date_fin_prevue.date_format' => 'La date de fin n\'est pas valide.',
                'date_fin_prevue.after_or_equal' => 'La date de fin doit être le même jour ou après la date de début.',
            ]
        );

        // Le demandeur est TOUJOURS l'utilisateur connecté, jamais une valeur du formulaire.
        $demandeur = $request->user();

        // RA-07 : limite du nombre de prêts en cours pour un élève.
        $maxPretsEnCours = config('prets.max_prets_en_cours_eleve');
        if ($demandeur->aLeRole(Role::ELEVE) && $demandeur->nombrePretsEnCours() >= $maxPretsEnCours) {
            return back()->withInput()->withErrors([
                'materiel_id' => 'Vous avez déjà ' . $maxPretsEnCours . ' prêts en cours : c\'est le maximum autorisé.',
            ]);
        }

        if (Pret::materielDejaReserve($champs['materiel_id'], $champs['date_debut'], $champs['date_fin_prevue'])) {
            return back()->withInput()->withErrors([
                'date_debut' => 'Ce matériel est déjà réservé sur une partie de ces dates.',
            ]);
        }

        $pret = Pret::create([
            'materiel_id' => $champs['materiel_id'],
            'demandeur_id' => $demandeur->id,
            'date_debut' => $champs['date_debut'],
            'date_fin_prevue' => $champs['date_fin_prevue'],
        ]);

        return redirect()->route('prets.show', $pret->id)
            ->with('succes', 'Votre demande est enregistrée. Elle doit maintenant être validée.');
    }

    /** Détail d'un prêt (RA-03 : seulement s'il est dans le périmètre de l'utilisateur). */
    public function show(int $id): View
    {
        $pret = $this->trouverPretAutorise($id, 'view');
        $pret->load(['materiel', 'demandeur', 'validateur']);

        return view('prets.show', ['pret' => $pret]);
    }

    /** Valider une demande (BF-21, RA-04 vérifiée dans PretPolicy::valider). */
    public function valider(Request $request, int $id): RedirectResponse
    {
        $pret = $this->trouverPretAutorise($id, 'valider');
        $materiel = $pret->materiel;

        if (! in_array($materiel->etat, [Materiel::DISPONIBLE, Materiel::PRETE], true)) {
            return $this->retourAuPret($pret)->withErrors([
                'action' => 'Impossible de valider : le matériel est « ' . $materiel->libelleEtat() . ' ».',
            ]);
        }

        $dejaReserve = Pret::materielDejaReserve(
            $pret->materiel_id,
            $pret->date_debut->toDateString(),
            $pret->date_fin_prevue->toDateString(),
            $pret->id
        );
        if ($dejaReserve) {
            return $this->retourAuPret($pret)->withErrors([
                'action' => 'Impossible de valider : le matériel est déjà réservé sur ces dates par un autre prêt.',
            ]);
        }

        $pret->statut = Pret::VALIDE;
        $pret->validateur_id = $request->user()->id;
        $pret->save();

        Journal::enregistrer(Journal::PRET_VALIDE, $this->decrire($pret));

        return $this->retourAuPret($pret)->with('succes', 'La demande a été validée.');
    }

    /** Refuser une demande, avec un motif obligatoire (BF-21). Même droit que valider. */
    public function refuser(Request $request, int $id): RedirectResponse
    {
        $pret = $this->trouverPretAutorise($id, 'valider');

        $champs = $request->validate(
            ['motif_refus' => ['required', 'string', 'max:255']],
            [
                'motif_refus.required' => 'Le motif du refus est obligatoire.',
                'motif_refus.max' => 'Le motif ne doit pas dépasser 255 caractères.',
            ]
        );

        $pret->statut = Pret::REFUSE;
        $pret->validateur_id = $request->user()->id;
        $pret->motif_refus = $champs['motif_refus'];
        $pret->save();

        Journal::enregistrer(Journal::PRET_REFUSE, $this->decrire($pret) . ' – motif : ' . $pret->motif_refus);

        return $this->retourAuPret($pret)->with('succes', 'La demande a été refusée.');
    }

    /** Annuler un prêt pas encore sorti (règles dans PretPolicy::annuler). */
    public function annuler(int $id): RedirectResponse
    {
        $pret = $this->trouverPretAutorise($id, 'annuler');

        $pret->statut = Pret::ANNULE;
        $pret->save();

        Journal::enregistrer(Journal::PRET_ANNULE, $this->decrire($pret));

        return $this->retourAuPret($pret)->with('succes', 'Le prêt a été annulé.');
    }

    /** Enregistrer la sortie du matériel (BF-22) : le matériel passe à « prêté ». */
    public function sortir(int $id): RedirectResponse
    {
        $pret = $this->trouverPretAutorise($id, 'sortir');
        $materiel = $pret->materiel;

        if (! $materiel->estDisponible()) {
            return $this->retourAuPret($pret)->withErrors([
                'action' => 'Impossible d\'enregistrer la sortie : le matériel est « ' . $materiel->libelleEtat() . ' ».',
            ]);
        }

        // Transaction : les deux modifications réussissent ensemble, ou aucune n'est faite.
        DB::transaction(function () use ($pret, $materiel) {
            $pret->statut = Pret::SORTI;
            $pret->save();

            $materiel->etat = Materiel::PRETE;
            $materiel->save();

            Journal::enregistrer(Journal::PRET_SORTI, $this->decrire($pret));
        });

        return $this->retourAuPret($pret)->with('succes', 'La sortie du matériel est enregistrée.');
    }

    /** Enregistrer le retour (BF-22), avec l'état constaté du matériel (etat_retour). */
    public function rendre(Request $request, int $id): RedirectResponse
    {
        $pret = $this->trouverPretAutorise($id, 'rendre');

        $champs = $request->validate(
            ['etat_retour' => ['required', Rule::in(Pret::ETATS_RETOUR)]],
            [
                'etat_retour.required' => 'Indiquez l\'état du matériel au retour.',
                'etat_retour.in' => 'Cet état n\'est pas valide.',
            ]
        );

        DB::transaction(function () use ($pret, $champs) {
            $pret->statut = Pret::RENDU;
            $pret->date_retour = now()->toDateString();
            $pret->etat_retour = $champs['etat_retour'];
            $pret->save();

            $pret->materiel->etat = $champs['etat_retour'];
            $pret->materiel->save();

            Journal::enregistrer(
                Journal::PRET_RENDU,
                $this->decrire($pret) . ' – état au retour : ' . Materiel::LIBELLES_ETATS[$pret->etat_retour]
            );
        });

        return $this->retourAuPret($pret)->with('succes', 'Le retour du matériel est enregistré.');
    }

    /**
     * Cherche le prêt et vérifie que l'utilisateur a le droit de faire $action dessus
     * (méthode de PretPolicy : view, valider, annuler, sortir, rendre).
     *
     * Prêt inexistant OU interdit → même erreur 403 « Accès refusé ». Volontaire :
     * une erreur 404 pour un prêt inexistant révélerait que les autres existent.
     */
    private function trouverPretAutorise(int $id, string $action): Pret
    {
        $pret = Pret::find($id);

        if ($pret === null || Gate::denies($action, $pret)) {
            abort(403);
        }

        return $pret;
    }

    /** Texte court pour la colonne « objet » du journal, ex. « Prêt n° 12 (INV-PC-001, demandé par Emma Eleve) ». */
    private function decrire(Pret $pret): string
    {
        return 'Prêt n° ' . $pret->id
            . ' (' . $pret->materiel->numero_inventaire
            . ', demandé par ' . $pret->demandeur->prenom . ' ' . $pret->demandeur->nom . ')';
    }

    private function retourAuPret(Pret $pret): RedirectResponse
    {
        return redirect()->route('prets.show', $pret->id);
    }
}
