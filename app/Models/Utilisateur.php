<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Modèle Eloquent de la table "utilisateurs" (cahier des charges, section 7.2).
 *
 * Il hérite de la classe "User" de Laravel (renommée Authenticatable ici) : c'est
 * elle qui permet à Laravel de connecter un utilisateur avec Auth::login().
 *
 * Notifiable : servira à envoyer les e-mails (réinitialisation du mot de passe,
 * rappels de retour).
 */
class Utilisateur extends Authenticatable
{
    use Notifiable;

    /** Blocage du compte après 5 échecs, pendant 15 minutes (ES-03). */
    public const NOMBRE_MAX_ECHECS = 5;
    public const DUREE_BLOCAGE_MINUTES = 15;

    /** Nom de table écrit explicitement, comme demandé en section 8 du cahier des charges. */
    protected $table = 'utilisateurs';

    /**
     * Nom de la colonne du mot de passe haché. Laravel attend "password" par défaut ;
     * getAuthPassword() et la mise à jour automatique du hachage lisent cette propriété.
     */
    protected $authPasswordName = 'mot_de_passe_hash';

    /** Chaîne vide = pas de « se souvenir de moi » (pas de colonne remember_token). */
    protected $rememberTokenName = '';

    /**
     * Colonnes qu'on peut remplir d'un coup avec create() ou update().
     * Volontairement absents : mot_de_passe_hash, echecs_connexion, bloque_jusqua.
     * Ils ne peuvent être modifiés que par du code écrit exprès, jamais par un
     * formulaire envoyé tel quel (protection contre l'« assignation de masse »).
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'role_id',
        'actif',
        'doit_changer_mot_de_passe',
    ];

    /** Le hachage n'apparaît jamais si l'utilisateur est converti en tableau ou en JSON. */
    protected $hidden = [
        'mot_de_passe_hash',
    ];

    /** Conversions automatiques : "1" en base devient true en PHP, une date devient un objet Carbon. */
    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
            'actif' => 'boolean',
            'doit_changer_mot_de_passe' => 'boolean',
            'echecs_connexion' => 'integer',
            'bloque_jusqua' => 'datetime',
        ];
    }

    /** Un utilisateur a un seul rôle (association AVOIR). */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Les classes de l'élève ou de l'enseignant (association APPARTENIR). */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'classe_utilisateur', 'utilisateur_id', 'classe_id');
    }

    /** Les prêts demandés par cet utilisateur (association DEMANDER). */
    public function pretsDemandes(): HasMany
    {
        return $this->hasMany(Pret::class, 'demandeur_id');
    }

    /** Section 5.6 : un compte qui a du matériel sorti ne peut pas être désactivé. */
    public function aDuMaterielNonRendu(): bool
    {
        return $this->pretsDemandes()->where('statut', Pret::SORTI)->exists();
    }

    /**
     * RA-05 : vrai si ce compte est le seul administrateur actif.
     * On ne peut alors ni le désactiver, ni lui retirer le rôle administrateur.
     */
    public function estDernierAdministrateurActif(): bool
    {
        if (! $this->actif || ! $this->aLeRole(Role::ADMINISTRATEUR)) {
            return false;
        }

        $nombreAdministrateursActifs = Utilisateur::where('actif', true)
            ->whereHas('role', function ($role) {
                $role->where('nom', Role::ADMINISTRATEUR);
            })
            ->count();

        return $nombreAdministrateursActifs <= 1;
    }

    /** Seuls les élèves et les enseignants appartiennent à des classes (association APPARTENIR). */
    public function peutAvoirDesClasses(): bool
    {
        return $this->aLeRole(Role::ELEVE) || $this->aLeRole(Role::ENSEIGNANT);
    }

    /**
     * Le rôle de l'utilisateur possède-t-il cette permission dans role_permission ?
     * Utilisé par les Gates (AppServiceProvider).
     *
     * Un compte désactivé n'a plus aucune permission, même si sa session est
     * encore ouverte : c'est une sécurité en plus de RA-06.
     *
     * Le rôle et ses permissions sont chargés depuis la base une seule fois par
     * requête : Eloquent les garde ensuite en mémoire dans l'objet.
     */
    public function aLaPermission(string $nomPermission): bool
    {
        if (! $this->actif) {
            return false;
        }

        return $this->role->permissions->contains('nom', $nomPermission);
    }

    public function aLeRole(string $nomRole): bool
    {
        return $this->role->nom === $nomRole;
    }

    /**
     * Cet utilisateur est-il un enseignant d'une classe de cet élève ?
     * Sert à « l'enseignant voit et valide les prêts des élèves de ses classes ».
     */
    public function enseigneA(Utilisateur $eleve): bool
    {
        if (! $this->aLeRole(Role::ENSEIGNANT)) {
            return false;
        }

        return $this->idsElevesDeSesClasses()->contains($eleve->id);
    }

    /**
     * Identifiants des élèves qui sont dans au moins une classe de cet utilisateur.
     * Seule définition de « élèves de ses classes » : utilisée par enseigneA()
     * (donc PretPolicy) et par Pret::visiblesPar() (la liste des prêts).
     */
    public function idsElevesDeSesClasses(): Collection
    {
        $idsDeSesClasses = $this->classes()->pluck('classes.id');

        return Utilisateur::query()
            ->whereHas('role', function ($role) {
                $role->where('nom', Role::ELEVE);
            })
            ->whereHas('classes', function ($classe) use ($idsDeSesClasses) {
                $classe->whereIn('classes.id', $idsDeSesClasses);
            })
            ->pluck('id');
    }

    /** Nombre de prêts en attente, validés ou sortis : sert à la limite RA-07. */
    public function nombrePretsEnCours(): int
    {
        return $this->pretsDemandes()->whereIn('statut', Pret::STATUTS_EN_COURS)->count();
    }

    public function estBloque(): bool
    {
        return $this->bloque_jusqua !== null && $this->bloque_jusqua->isFuture();
    }

    /**
     * Ajoute un échec. Au 5e, le compte est bloqué 15 minutes et le compteur
     * repart de zéro : à la fin du blocage, l'utilisateur a de nouveau 5 essais.
     */
    public function enregistrerEchecConnexion(): void
    {
        $this->echecs_connexion++;

        if ($this->echecs_connexion >= self::NOMBRE_MAX_ECHECS) {
            $this->bloque_jusqua = now()->addMinutes(self::DUREE_BLOCAGE_MINUTES);
            $this->echecs_connexion = 0;
        }

        $this->save();
    }

    public function reinitialiserEchecsConnexion(): void
    {
        $this->echecs_connexion = 0;
        $this->bloque_jusqua = null;
        $this->save();
    }

    /** Enregistre le nouveau mot de passe haché (ES-01) et lève l'obligation de le changer. */
    public function changerMotDePasse(string $nouveauMotDePasse): void
    {
        $this->mot_de_passe_hash = Hash::make($nouveauMotDePasse);
        $this->doit_changer_mot_de_passe = false;
        $this->save();
    }

    /**
     * Supprime les sessions ouvertes de l'utilisateur dans la table "sessions" :
     * il est déconnecté partout. $sauf permet de garder la session en cours.
     * Servira aussi pour RA-06 (changement de rôle ou désactivation).
     */
    public function fermerSessions(?string $sauf = null): void
    {
        $requete = DB::table('sessions')->where('user_id', $this->id);

        if ($sauf !== null) {
            $requete->where('id', '!=', $sauf);
        }

        $requete->delete();
    }
}
