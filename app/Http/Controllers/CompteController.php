<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Journal;
use App\Models\Pret;
use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\View\View;

/**
 * Gestion des comptes par l'administrateur (BF-04, RA-05, RA-06, section 5.6).
 *
 * Contrôles :
 *  - toutes les routes : middleware can:compte.gerer (routes/web.php) ;
 *  - chaque compte précis : UtilisateurPolicy (jamais son propre compte,
 *    jamais le dernier administrateur actif, pas de matériel non rendu).
 *
 * RA-06 : un changement de rôle ou une désactivation ferme toutes les sessions
 * de la personne (fermerSessions) : elle est déconnectée à sa prochaine requête.
 *
 * Chaque action est écrite dans le journal de sécurité.
 *
 * {compte} dans l'adresse : Laravel cherche l'utilisateur par son id (404 s'il
 * n'existe pas). Pas de risque d'IDOR ici : l'administrateur voit tous les comptes.
 */
class CompteController extends Controller
{
    private const NOMBRE_PAR_PAGE = 20;
    private const LONGUEUR_MOT_DE_PASSE_TEMPORAIRE = 16;

    /** Liste des comptes, filtrable par rôle, par statut et par nom ou e-mail. */
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'role' => ['nullable', Rule::in(Role::TOUS)],
            'statut' => ['nullable', Rule::in(['actifs', 'desactives'])],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        $requete = Utilisateur::with(['role', 'classes'])->orderBy('nom')->orderBy('prenom');

        if (! empty($filtres['role'])) {
            $requete->whereHas('role', function ($role) use ($filtres) {
                $role->where('nom', $filtres['role']);
            });
        }

        if (! empty($filtres['statut'])) {
            $requete->where('actif', $filtres['statut'] === 'actifs');
        }

        if (! empty($filtres['recherche'])) {
            $texte = '%' . $filtres['recherche'] . '%';
            $requete->where(function ($sousRequete) use ($texte) {
                $sousRequete->where('nom', 'like', $texte)
                    ->orWhere('prenom', 'like', $texte)
                    ->orWhere('email', 'like', $texte);
            });
        }

        return view('comptes.index', [
            'comptes' => $requete->paginate(self::NOMBRE_PAR_PAGE)->withQueryString(),
            'filtres' => $filtres,
        ]);
    }

    public function create(): View
    {
        // Section 5.6 : on propose par défaut le rôle le plus faible.
        $compte = new Utilisateur(['role_id' => Role::where('nom', Role::ELEVE)->value('id')]);

        return view('comptes.formulaire', $this->donneesDuFormulaire($compte));
    }

    /**
     * Crée un compte avec un mot de passe temporaire généré par l'application.
     * Il est haché en base (ES-01) et affiché UNE seule fois à l'administrateur,
     * qui le transmet à la personne ; elle devra le changer à sa première connexion.
     */
    public function store(Request $request): RedirectResponse
    {
        // Créer un compte, c'est aussi lui donner un rôle.
        Gate::authorize('role.attribuer');

        $champs = $this->validerChamps($request, null, avecRole: true);
        // Lettres et chiffres seulement : il sera dicté ou recopié par une personne,
        // les symboles (& < : …) causent des erreurs. 16 caractères restent très solides.
        $motDePasseTemporaire = Str::password(self::LONGUEUR_MOT_DE_PASSE_TEMPORAIRE, symbols: false);

        $compte = DB::transaction(function () use ($champs, $motDePasseTemporaire) {
            $compte = new Utilisateur([
                'nom' => $champs['nom'],
                'prenom' => $champs['prenom'],
                'email' => $champs['email'],
                'role_id' => $champs['role_id'],
                'actif' => true,
                'doit_changer_mot_de_passe' => true,
            ]);
            $compte->mot_de_passe_hash = Hash::make($motDePasseTemporaire);
            $compte->save();

            $this->enregistrerClasses($compte, $champs['classes'] ?? []);

            Journal::enregistrer(Journal::COMPTE_CREE, $compte->email . ' (' . $compte->role->libelle() . ')');

            return $compte;
        });

        // with() : ces informations ne sont affichées qu'une fois, sur la page suivante.
        return redirect()->route('comptes.index')
            ->with('succes', 'Le compte de ' . $compte->prenom . ' ' . $compte->nom . ' a été créé.')
            ->with('motDePasseTemporaire', $motDePasseTemporaire)
            ->with('emailDuCompteCree', $compte->email);
    }

    public function edit(Utilisateur $compte): View
    {
        Gate::authorize('update', $compte);

        return view('comptes.formulaire', $this->donneesDuFormulaire($compte));
    }

    /** Modifie le nom, le prénom, l'e-mail et les classes (pas le rôle : voir changerRole). */
    public function update(Request $request, Utilisateur $compte): RedirectResponse
    {
        Gate::authorize('update', $compte);

        $champs = $this->validerChamps($request, $compte, avecRole: false);
        $ancienEmail = $compte->email;

        DB::transaction(function () use ($compte, $champs, $ancienEmail) {
            $compte->nom = $champs['nom'];
            $compte->prenom = $champs['prenom'];
            $compte->email = $champs['email'];
            $compte->save();

            $this->enregistrerClasses($compte, $champs['classes'] ?? []);

            // L'e-mail sert d'identifiant de connexion : son changement doit être traçable.
            $objet = $ancienEmail === $compte->email
                ? $compte->email
                : $ancienEmail . ' → ' . $compte->email;
            Journal::enregistrer(Journal::COMPTE_MODIFIE, $objet);
        });

        return redirect()->route('comptes.edit', $compte)->with('succes', 'Le compte a été modifié.');
    }

    /** Change le rôle (RA-05 dans la Policy, RA-06 : fermeture des sessions). */
    public function changerRole(Request $request, Utilisateur $compte): RedirectResponse
    {
        $decision = Gate::inspect('changerRole', $compte);
        if ($decision->denied()) {
            return redirect()->route('comptes.index')->withErrors(['action' => $decision->message()]);
        }

        $champs = $request->validate(
            ['role_id' => ['required', 'integer', $this->regleRoleAttribuable()]],
            ['role_id.required' => 'Choisissez un rôle.', 'role_id.exists' => 'Ce rôle n\'existe pas.']
        );

        $ancienRole = $compte->role->libelle();

        if ((int) $champs['role_id'] === $compte->role_id) {
            return redirect()->route('comptes.edit', $compte)->with('succes', 'Le rôle n\'a pas changé.');
        }

        DB::transaction(function () use ($compte, $champs, $ancienRole) {
            $compte->role_id = (int) $champs['role_id'];
            $compte->save();
            $compte->load('role');

            if (! $compte->peutAvoirDesClasses()) {
                $compte->classes()->detach();
            }

            $compte->fermerSessions();

            Journal::enregistrer(
                Journal::ROLE_CHANGE,
                $compte->email . ' : ' . $ancienRole . ' → ' . $compte->role->libelle()
            );
        });

        return redirect()->route('comptes.edit', $compte)
            ->with('succes', 'Le rôle a été changé. La personne a été déconnectée.');
    }

    /**
     * Désactive un compte (fin d'année, départ). L'historique est conservé.
     * Ses demandes pas encore sorties sont annulées : on ne doit pas remettre
     * de matériel à un compte désactivé.
     */
    public function desactiver(Utilisateur $compte): RedirectResponse
    {
        $decision = Gate::inspect('desactiver', $compte);
        if ($decision->denied()) {
            return redirect()->route('comptes.index')->withErrors(['action' => $decision->message()]);
        }

        DB::transaction(function () use ($compte) {
            $compte->actif = false;
            $compte->save();

            $pretsAAnnuler = $compte->pretsDemandes()
                ->whereIn('statut', [Pret::EN_ATTENTE, Pret::VALIDE])
                ->get();

            foreach ($pretsAAnnuler as $pret) {
                $pret->statut = Pret::ANNULE;
                $pret->save();
                Journal::enregistrer(Journal::PRET_ANNULE, 'Prêt n° ' . $pret->id . ' (compte ' . $compte->email . ' désactivé)');
            }

            $compte->fermerSessions();

            Journal::enregistrer(Journal::COMPTE_DESACTIVE, $compte->email);
        });

        return redirect()->route('comptes.index')
            ->with('succes', 'Le compte de ' . $compte->prenom . ' ' . $compte->nom . ' a été désactivé.');
    }

    public function reactiver(Utilisateur $compte): RedirectResponse
    {
        Gate::authorize('reactiver', $compte);

        DB::transaction(function () use ($compte) {
            $compte->actif = true;
            $compte->save();
            $compte->reinitialiserEchecsConnexion();

            Journal::enregistrer(Journal::COMPTE_REACTIVE, $compte->email);
        });

        return redirect()->route('comptes.index')
            ->with('succes', 'Le compte de ' . $compte->prenom . ' ' . $compte->nom . ' a été réactivé.');
    }

    /**
     * Validation commune à la création et à la modification (ES-10).
     * $compte vaut null pour une création. Le rôle n'est validé qu'à la création :
     * ensuite, il ne change que par changerRole() (règles RA-05 / RA-06).
     */
    private function validerChamps(Request $request, ?Utilisateur $compte, bool $avecRole): array
    {
        $regleEmailUnique = Rule::unique('utilisateurs', 'email');
        if ($compte !== null) {
            $regleEmailUnique->ignore($compte->id);
        }

        $regles = [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', $regleEmailUnique],
            'classes' => ['nullable', 'array'],
            'classes.*' => ['integer', Rule::exists('classes', 'id')],
        ];

        if ($avecRole) {
            $regles['role_id'] = ['required', 'integer', $this->regleRoleAttribuable()];
        }

        $champs = $request->validate($regles, [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 100 caractères.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'prenom.max' => 'Le prénom ne doit pas dépasser 100 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.max' => 'L\'adresse e-mail est trop longue.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'classes.*.exists' => 'Une des classes choisies n\'existe pas.',
            'role_id.required' => 'Choisissez un rôle.',
            'role_id.exists' => 'Ce rôle n\'existe pas.',
        ]);

        // Les e-mails sont toujours enregistrés en minuscules, comme à la connexion.
        $champs['email'] = Str::lower($champs['email']);

        return $champs;
    }

    /** Enregistre les classes du compte ; un gestionnaire ou un administrateur n'en a aucune. */
    private function enregistrerClasses(Utilisateur $compte, array $idsClasses): void
    {
        if (! $compte->peutAvoirDesClasses()) {
            $idsClasses = [];
        }

        // sync() : garde exactement ces classes, ajoute les nouvelles et retire les autres.
        $compte->classes()->sync($idsClasses);
    }

    /**
     * Le rôle envoyé doit exister ET être dans la liste blanche Role::ATTRIBUABLES_PAR_FORMULAIRE.
     * Sans le whereIn, un administrateur pourrait envoyer à la main l'id du rôle
     * super_admin (requête fabriquée) et donner l'accès à tous les établissements.
     */
    private function regleRoleAttribuable(): Exists
    {
        return Rule::exists('roles', 'id')->whereIn('nom', Role::ATTRIBUABLES_PAR_FORMULAIRE);
    }

    private function donneesDuFormulaire(Utilisateur $compte): array
    {
        return [
            'compte' => $compte,
            'roles' => Role::whereIn('nom', Role::ATTRIBUABLES_PAR_FORMULAIRE)->orderBy('id')->get(),
            'classes' => Classe::orderBy('nom')->get(),
            'idsClassesDuCompte' => $compte->exists ? $compte->classes()->pluck('classes.id')->all() : [],
        ];
    }
}
