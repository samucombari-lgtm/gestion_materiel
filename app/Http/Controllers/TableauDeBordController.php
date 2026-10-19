<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Pret;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord adapté au rôle (BF-30).
 *
 * Chaque bloc n'est calculé que si l'utilisateur a la permission correspondante.
 * Sinon il vaut null et la vue ne l'affiche pas. Ainsi, aucune donnée n'est
 * lue en base pour un rôle qui n'a pas le droit de la voir.
 *
 *  - Mes prêts en cours      : pret.demander
 *  - Demandes à valider      : pret.valider (périmètre de Pret::visiblesPar, sauf les siennes : RA-04)
 *  - Sorties à enregistrer   : pret.sortie_retour
 *  - Retards                 : pret.voir (dans son périmètre)
 *  - Sécurité des 24 h       : journal.voir
 */
class TableauDeBordController extends Controller
{
    private const NOMBRE_MAX_PAR_BLOC = 10;

    public function afficher(Request $request): View
    {
        $utilisateur = $request->user();
        $aujourdhui = now()->toDateString();

        $mesPretsEnCours = null;
        if ($utilisateur->can('pret.demander')) {
            $mesPretsEnCours = $utilisateur->pretsDemandes()
                ->whereIn('statut', Pret::STATUTS_EN_COURS)
                ->with('materiel')
                ->orderBy('date_debut')
                ->get();
        }

        $demandesAValider = null;
        if ($utilisateur->can('pret.valider')) {
            $demandesAValider = Pret::visiblesPar($utilisateur)
                ->where('statut', Pret::EN_ATTENTE)
                ->where('demandeur_id', '!=', $utilisateur->id)
                ->with(['materiel', 'demandeur'])
                ->orderBy('date_debut')
                ->limit(self::NOMBRE_MAX_PAR_BLOC)
                ->get();
        }

        $sortiesAEnregistrer = null;
        if ($utilisateur->can('pret.sortie_retour')) {
            $sortiesAEnregistrer = Pret::where('statut', Pret::VALIDE)
                ->whereDate('date_debut', '<=', $aujourdhui)
                ->with(['materiel', 'demandeur'])
                ->orderBy('date_debut')
                ->limit(self::NOMBRE_MAX_PAR_BLOC)
                ->get();
        }

        $retards = null;
        if ($utilisateur->can('pret.voir')) {
            $retards = Pret::visiblesPar($utilisateur)
                ->where('statut', Pret::SORTI)
                ->whereDate('date_fin_prevue', '<', $aujourdhui)
                ->with(['materiel', 'demandeur'])
                ->orderBy('date_fin_prevue')
                ->limit(self::NOMBRE_MAX_PAR_BLOC)
                ->get();
        }

        $securite = null;
        if ($utilisateur->can('journal.voir')) {
            $depuis = now()->subDay();
            $securite = [
                'connexionsEchouees' => Journal::where('action', Journal::CONNEXION_ECHOUEE)->where('date_heure', '>=', $depuis)->count(),
                'accesRefuses' => Journal::where('action', Journal::ACCES_REFUSE)->where('date_heure', '>=', $depuis)->count(),
                'derniersEvenements' => Journal::with('utilisateur')->orderByDesc('date_heure')->orderByDesc('id')
                    ->limit(self::NOMBRE_MAX_PAR_BLOC)->get(),
            ];
        }

        return view('tableau-de-bord', [
            'mesPretsEnCours' => $mesPretsEnCours,
            'demandesAValider' => $demandesAValider,
            'sortiesAEnregistrer' => $sortiesAEnregistrer,
            'retards' => $retards,
            'securite' => $securite,
        ]);
    }
}
