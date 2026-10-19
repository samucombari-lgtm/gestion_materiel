<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
            'actif' => 'boolean',
            'doit_changer_mot_de_passe' => 'boolean',
            'echecs_connexion' => 'integer',
            'bloque_jusqua' => 'datetime',
        ];
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
