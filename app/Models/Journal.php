<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Journal de sécurité (cahier des charges, section 6, BF-31).
 *
 * Une ligne = une action sensible : qui, quand, quoi, sur quel objet, depuis quelle IP.
 * Pour écrire une ligne, utiliser UNIQUEMENT Journal::enregistrer().
 *
 * Lecture seule, même pour l'administrateur :
 *  - ce modèle lance une erreur si du code essaie de modifier ou supprimer une ligne ;
 *  - en base, des triggers MariaDB refusent UPDATE et DELETE
 *    (migration 2026_10_19_100010_proteger_table_journal).
 */
class Journal extends Model
{
    public const CONNEXION_REUSSIE = 'connexion_reussie';
    public const CONNEXION_ECHOUEE = 'connexion_echouee';
    public const COMPTE_BLOQUE = 'compte_bloque';
    public const MOT_DE_PASSE_CHANGE = 'mot_de_passe_change';
    public const ACCES_REFUSE = 'acces_refuse';
    public const PRET_VALIDE = 'pret_valide';
    public const PRET_REFUSE = 'pret_refuse';
    public const PRET_ANNULE = 'pret_annule';
    public const PRET_SORTI = 'pret_sorti';
    public const PRET_RENDU = 'pret_rendu';
    // Prêtes pour l'écran de gestion des comptes (BF-04), pas encore construit.
    public const COMPTE_CREE = 'compte_cree';
    public const COMPTE_DESACTIVE = 'compte_desactive';
    public const ROLE_CHANGE = 'role_change';

    public const LIBELLES_ACTIONS = [
        self::CONNEXION_REUSSIE   => 'Connexion réussie',
        self::CONNEXION_ECHOUEE   => 'Connexion échouée',
        self::COMPTE_BLOQUE       => 'Compte bloqué (5 échecs)',
        self::MOT_DE_PASSE_CHANGE => 'Mot de passe changé',
        self::ACCES_REFUSE        => 'Accès refusé (403)',
        self::PRET_VALIDE         => 'Prêt validé',
        self::PRET_REFUSE         => 'Prêt refusé',
        self::PRET_ANNULE         => 'Prêt annulé',
        self::PRET_SORTI          => 'Sortie de matériel',
        self::PRET_RENDU          => 'Retour de matériel',
        self::COMPTE_CREE         => 'Compte créé',
        self::COMPTE_DESACTIVE    => 'Compte désactivé',
        self::ROLE_CHANGE         => 'Rôle changé',
    ];

    /** Taille de la colonne "objet" : un texte plus long est coupé, pour ne jamais faire échouer l'insertion. */
    private const LONGUEUR_MAX_OBJET = 255;

    protected $table = 'journal';

    /** Pas de created_at / updated_at : la date est dans date_heure. */
    public $timestamps = false;

    protected $fillable = ['date_heure', 'utilisateur_id', 'action', 'objet', 'ip'];

    protected function casts(): array
    {
        return [
            'date_heure' => 'datetime',
            'utilisateur_id' => 'integer',
        ];
    }

    /**
     * Interdit la modification et la suppression d'une ligne déjà enregistrée.
     * booted() est appelée une fois par Laravel ; updating / deleting sont
     * déclenchés juste avant un UPDATE ou un DELETE.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Le journal de sécurité est en lecture seule.');
        });

        static::deleting(function () {
            throw new LogicException('Le journal de sécurité est en lecture seule.');
        });
    }

    /**
     * Écrit une ligne dans le journal.
     *
     * @param string $action      une des constantes ci-dessus (ex. Journal::PRET_VALIDE)
     * @param string|null $objet  ce qui est visé, en texte court (ex. « Prêt n° 12 »)
     * @param Utilisateur|null $utilisateur  l'auteur ; par défaut l'utilisateur connecté
     *                                       (null si personne, ex. e-mail inconnu)
     */
    public static function enregistrer(string $action, ?string $objet = null, ?Utilisateur $utilisateur = null): void
    {
        $auteur = $utilisateur ?? auth()->user();

        self::create([
            // Date fournie par Laravel (fuseau Europe/Paris de config/app.php), pas par MariaDB.
            'date_heure' => now(),
            'utilisateur_id' => $auteur?->id,
            'action' => $action,
            'objet' => $objet === null ? null : mb_substr($objet, 0, self::LONGUEUR_MAX_OBJET),
            'ip' => request()->ip(),
        ]);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function libelleAction(): string
    {
        return self::LIBELLES_ACTIONS[$this->action] ?? $this->action;
    }
}
