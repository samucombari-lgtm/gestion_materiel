<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de la table "materiels" : l'inventaire (BF-10 à BF-12).
 *
 * Qui change l'état d'un matériel ?
 *  - disponible, en_panne, en_reparation : le gestionnaire, dans le formulaire ;
 *  - prete : uniquement le cycle de prêt (sortie / retour, étape 6), pour que
 *    l'état reste cohérent avec la table prets ;
 *  - archive : le bouton « Archiver », si aucun prêt n'est en cours.
 * On ne supprime jamais un matériel (BF-11) : on l'archive.
 *
 * Un matériel appartient à un établissement : le numéro d'inventaire est unique
 * dans un établissement, pas entre établissements.
 */
class Materiel extends Model
{
    use AppartientAUnEtablissement;

    public const DISPONIBLE = 'disponible';
    public const PRETE = 'prete';
    public const EN_PANNE = 'en_panne';
    public const EN_REPARATION = 'en_reparation';
    public const ARCHIVE = 'archive';

    /** Les mêmes valeurs que l'ENUM de la colonne "etat", avec leur libellé à afficher. */
    public const LIBELLES_ETATS = [
        self::DISPONIBLE    => 'Disponible',
        self::PRETE         => 'Prêté',
        self::EN_PANNE      => 'En panne',
        self::EN_REPARATION => 'En réparation',
        self::ARCHIVE       => 'Archivé',
    ];

    /** États que le gestionnaire peut choisir dans le formulaire. */
    public const ETATS_CHOISISSABLES = [self::DISPONIBLE, self::EN_PANNE, self::EN_REPARATION];

    protected $table = 'materiels';

    protected $fillable = ['nom', 'categorie', 'numero_inventaire', 'etat'];

    public function prets(): HasMany
    {
        return $this->hasMany(Pret::class, 'materiel_id');
    }

    public function libelleEtat(): string
    {
        return self::LIBELLES_ETATS[$this->etat];
    }

    public function estDisponible(): bool
    {
        return $this->etat === self::DISPONIBLE;
    }

    public function estPrete(): bool
    {
        return $this->etat === self::PRETE;
    }

    public function estArchive(): bool
    {
        return $this->etat === self::ARCHIVE;
    }

    /**
     * On n'archive pas un matériel qui a un prêt en attente, validé ou sorti :
     * ce prêt deviendrait impossible à honorer.
     */
    public function peutEtreArchive(): bool
    {
        $aDesPretsEnCours = $this->prets()
            ->whereIn('statut', [Pret::EN_ATTENTE, Pret::VALIDE, Pret::SORTI])
            ->exists();

        return ! $this->estPrete() && ! $this->estArchive() && ! $aDesPretsEnCours;
    }
}
