<?php

namespace App\Http\Controllers;

use App\Models\Materiel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Inventaire du matériel (cahier des charges, BF-10 à BF-12).
 *
 * Les droits sont vérifiés par le middleware can: dans routes/web.php :
 *  - index : materiel.voir (tous les rôles connectés) ;
 *  - create, store, edit, update, archiver : materiel.gerer (gestionnaire).
 *
 * Il n'y a volontairement pas de méthode de suppression (BF-11).
 *
 * {materiel} dans l'adresse : Laravel cherche le matériel par son id et le donne
 * à la méthode. S'il n'existe pas, il renvoie une erreur 404 tout seul.
 */
class MaterielController extends Controller
{
    private const NOMBRE_PAR_PAGE = 20;

    /** Catalogue (BF-10), avec recherche par nom ou catégorie. */
    public function index(Request $request): View
    {
        $recherche = trim((string) $request->query('recherche', ''));

        // Les archivés ne sont visibles que par le gestionnaire, et seulement s'il le demande.
        $afficherArchives = $request->boolean('archives') && Gate::allows('materiel.gerer');

        $requete = Materiel::query()->orderBy('categorie')->orderBy('nom');

        if (! $afficherArchives) {
            $requete->where('etat', '!=', Materiel::ARCHIVE);
        }

        if ($recherche !== '') {
            // Eloquent prépare la requête : le texte tapé n'est jamais collé dans le SQL (ES-05).
            $requete->where(function ($sousRequete) use ($recherche) {
                $sousRequete->where('nom', 'like', '%' . $recherche . '%')
                    ->orWhere('categorie', 'like', '%' . $recherche . '%');
            });
        }

        return view('materiels.index', [
            'materiels' => $requete->paginate(self::NOMBRE_PAR_PAGE)->withQueryString(),
            'recherche' => $recherche,
            'afficherArchives' => $afficherArchives,
        ]);
    }

    public function create(): View
    {
        return view('materiels.formulaire', [
            'materiel' => new Materiel(['etat' => Materiel::DISPONIBLE]),
            'categories' => $this->categoriesExistantes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $champs = $this->validerChamps($request, null);

        Materiel::create($champs);

        return redirect()->route('materiels.index')
            ->with('succes', 'Le matériel « ' . $champs['nom'] . ' » a été ajouté.');
    }

    public function edit(Materiel $materiel): View
    {
        return view('materiels.formulaire', [
            'materiel' => $materiel,
            'categories' => $this->categoriesExistantes(),
        ]);
    }

    public function update(Request $request, Materiel $materiel): RedirectResponse
    {
        $champs = $this->validerChamps($request, $materiel);

        $materiel->update($champs);

        return redirect()->route('materiels.index')
            ->with('succes', 'Le matériel « ' . $materiel->nom . ' » a été modifié.');
    }

    /** Archivage (BF-11) : le matériel disparaît du catalogue mais reste en base avec son historique. */
    public function archiver(Materiel $materiel): RedirectResponse
    {
        if (! $materiel->peutEtreArchive()) {
            return redirect()->route('materiels.index')->withErrors([
                'archivage' => 'Impossible d\'archiver « ' . $materiel->nom
                    . ' » : il est déjà archivé, prêté, ou il a un prêt en attente ou validé.',
            ]);
        }

        $materiel->etat = Materiel::ARCHIVE;
        $materiel->save();

        return redirect()->route('materiels.index')
            ->with('succes', 'Le matériel « ' . $materiel->nom . ' » a été archivé.');
    }

    /**
     * Validation des champs du formulaire (ES-10), commune à l'ajout et à la modification.
     *
     * $materiel vaut null pour un ajout. Pour une modification :
     *  - le numéro d'inventaire doit rester unique, sauf par rapport à lui-même ;
     *  - si le matériel est prêté, on ne touche pas à son état : seul le retour
     *    du prêt (étape 6) peut le changer.
     */
    private function validerChamps(Request $request, ?Materiel $materiel): array
    {
        $regleNumeroUnique = Rule::unique('materiels', 'numero_inventaire');
        if ($materiel !== null) {
            $regleNumeroUnique->ignore($materiel->id);
        }

        $regles = [
            'nom' => ['required', 'string', 'max:150'],
            'categorie' => ['required', 'string', 'max:100'],
            'numero_inventaire' => ['required', 'string', 'max:50', $regleNumeroUnique],
        ];

        $etatModifiable = $materiel === null || ! $materiel->estPrete();
        if ($etatModifiable) {
            $regles['etat'] = ['required', Rule::in(Materiel::ETATS_CHOISISSABLES)];
        }

        return $request->validate($regles, [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 150 caractères.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'categorie.max' => 'La catégorie ne doit pas dépasser 100 caractères.',
            'numero_inventaire.required' => 'Le numéro d\'inventaire est obligatoire.',
            'numero_inventaire.max' => 'Le numéro d\'inventaire ne doit pas dépasser 50 caractères.',
            'numero_inventaire.unique' => 'Ce numéro d\'inventaire est déjà utilisé.',
            'etat.required' => 'L\'état est obligatoire.',
            'etat.in' => 'Cet état ne peut pas être choisi ici.',
        ]);
    }

    /** Catégories déjà utilisées, proposées comme suggestions dans le formulaire. */
    private function categoriesExistantes(): array
    {
        return Materiel::query()->distinct()->orderBy('categorie')->pluck('categorie')->all();
    }
}
