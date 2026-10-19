<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle de la table "prets" (associations DEMANDER, VALIDER, CONCERNER).
 *
 * Cycle d'un prêt (BF-20 à BF-22) :
 *   en_attente → valide ou refuse → sorti → rendu   (ou annule tant qu'il n'est pas sorti)
 *
 * Les droits sur un prêt précis sont dans app/Policies/PretPolicy.php.
 */
class Pret extends Model
{
    public const EN_ATTENTE = 'en_attente';
    public const VALIDE = 'valide';
    public const REFUSE = 'refuse';
    public const SORTI = 'sorti';
    public const RENDU = 'rendu';
    public const ANNULE = 'annule';

    public const LIBELLES_STATUTS = [
        self::EN_ATTENTE => 'En attente',
        self::VALIDE     => 'Validé',
        self::REFUSE     => 'Refusé',
        self::SORTI      => 'Sorti',
        self::RENDU      => 'Rendu',
        self::ANNULE     => 'Annulé',
    ];

    /** Prêts « en cours » : comptés pour la limite RA-07. */
    public const STATUTS_EN_COURS = [self::EN_ATTENTE, self::VALIDE, self::SORTI];

    /** Prêts qui bloquent le matériel sur leurs dates : on ne peut pas en valider un autre en même temps. */
    public const STATUTS_QUI_RESERVENT = [self::VALIDE, self::SORTI];

    /** États possibles du matériel au retour (colonne etat_retour, BF-22). */
    public const ETATS_RETOUR = [Materiel::DISPONIBLE, Materiel::EN_PANNE, Materiel::EN_REPARATION];

    protected $table = 'prets';

    /**
     * Volontairement absents : validateur_id, statut, motif_refus, date_retour,
     * etat_retour. Ils changent seulement par les actions valider / refuser /
     * sortir / rendre, jamais par un formulaire envoyé tel quel.
     * demandeur_id est rempli par le contrôleur avec l'utilisateur connecté.
     */
    protected $fillable = ['materiel_id', 'demandeur_id', 'date_debut', 'date_fin_prevue'];

    protected function casts(): array
    {
        // Les clés en entier : les Policies les comparent avec === à $utilisateur->id.
        return [
            'materiel_id' => 'integer',
            'demandeur_id' => 'integer',
            'validateur_id' => 'integer',
            'date_debut' => 'date',
            'date_fin_prevue' => 'date',
            'date_retour' => 'date',
        ];
    }

    public function materiel(): BelongsTo
    {
        return $this->belongsTo(Materiel::class, 'materiel_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'demandeur_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'validateur_id');
    }

    public function libelleStatut(): string
    {
        return self::LIBELLES_STATUTS[$this->statut];
    }

    /**
     * Requête qui ne renvoie que les prêts que cet utilisateur a le droit de voir.
     * C'est la MÊME règle que PretPolicy::view(), mais écrite en SQL pour la
     * liste (on ne peut pas appeler la Policy sur chaque ligne de la base).
     * Si l'une change, il faut changer l'autre.
     *
     *  - gestionnaire : tous les prêts ;
     *  - enseignant : les siens + ceux des élèves de ses classes ;
     *  - élève : les siens ;
     *  - autre rôle (administrateur) : aucun.
     */
    public static function visiblesPar(Utilisateur $utilisateur): Builder
    {
        $requete = self::query();

        if (! $utilisateur->aLaPermission('pret.voir')) {
            return $requete->whereRaw('1 = 0');
        }

        if ($utilisateur->aLeRole(Role::GESTIONNAIRE)) {
            return $requete;
        }

        if ($utilisateur->aLeRole(Role::ENSEIGNANT)) {
            $idsEleves = $utilisateur->idsElevesDeSesClasses();

            return $requete->where(function ($sousRequete) use ($utilisateur, $idsEleves) {
                $sousRequete->where('demandeur_id', $utilisateur->id)
                    ->orWhereIn('demandeur_id', $idsEleves);
            });
        }

        return $requete->where('demandeur_id', $utilisateur->id);
    }

    /**
     * Le matériel est-il déjà réservé (prêt validé ou sorti) sur une partie de ces dates ?
     *
     * Deux périodes se chevauchent si chacune commence avant que l'autre se termine.
     * $saufPretId : ne pas compter ce prêt-là (celui qu'on est en train de valider).
     */
    public static function materielDejaReserve(int $materielId, string $dateDebut, string $dateFin, ?int $saufPretId = null): bool
    {
        $requete = self::query()
            ->where('materiel_id', $materielId)
            ->whereIn('statut', self::STATUTS_QUI_RESERVENT)
            ->whereDate('date_debut', '<=', $dateFin)
            ->whereDate('date_fin_prevue', '>=', $dateDebut);

        if ($saufPretId !== null) {
            $requete->where('id', '!=', $saufPretId);
        }

        return $requete->exists();
    }
}
