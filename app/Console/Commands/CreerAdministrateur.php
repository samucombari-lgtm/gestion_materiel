<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Crée un compte administrateur depuis le terminal du serveur.
 *
 * Sert surtout à la mise en production : la base est vide, il faut un premier
 * administrateur pour créer tous les autres comptes depuis l'application.
 * Les seeders de test refusent de tourner en production, cette commande est
 * faite pour ça.
 *
 * Utilisation (sur le serveur, dans le dossier du projet) :
 *     php artisan materiel:creer-admin
 *
 * La commande demande le prénom, le nom et l'e-mail, puis :
 *  - génère un mot de passe temporaire (16 lettres et chiffres), haché en base (ES-01) ;
 *  - l'affiche UNE seule fois dans le terminal ;
 *  - oblige à le changer à la première connexion (doit_changer_mot_de_passe = true) ;
 *  - écrit la création dans le journal de sécurité.
 *
 * Seule une personne qui a accès au terminal du serveur peut la lancer : c'est
 * ce qui la protège (il n'existe aucune page web équivalente).
 */
class CreerAdministrateur extends Command
{
    private const LONGUEUR_MOT_DE_PASSE_TEMPORAIRE = 16;

    /** Nom de la commande, tapé après « php artisan ». */
    protected $signature = 'materiel:creer-admin';

    /** Description affichée par « php artisan list ». */
    protected $description = 'Crée un compte administrateur avec un mot de passe temporaire';

    public function handle(): int
    {
        $this->info('Création d\'un compte administrateur');

        $champs = [
            'prenom' => trim((string) $this->ask('Prénom')),
            'nom' => trim((string) $this->ask('Nom')),
            'email' => Str::lower(trim((string) $this->ask('Adresse e-mail (identifiant de connexion)'))),
        ];

        // Mêmes règles que le formulaire de création de comptes (ES-10).
        $validation = Validator::make($champs, [
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('utilisateurs', 'email')],
        ], [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
        ]);

        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $message) {
                $this->error($message);
            }
            return self::FAILURE;
        }

        $idRoleAdministrateur = Role::where('nom', Role::ADMINISTRATEUR)->value('id');
        if ($idRoleAdministrateur === null) {
            $this->error('Le rôle « administrateur » n\'existe pas. Lancez d\'abord : php artisan db:seed --class=RoleSeeder');
            return self::FAILURE;
        }

        if (! $this->confirm('Créer le compte administrateur ' . $champs['email'] . ' ?', true)) {
            $this->warn('Création annulée.');
            return self::FAILURE;
        }

        $motDePasseTemporaire = Str::password(self::LONGUEUR_MOT_DE_PASSE_TEMPORAIRE, symbols: false);

        DB::transaction(function () use ($champs, $idRoleAdministrateur, $motDePasseTemporaire) {
            $administrateur = new Utilisateur([
                'prenom' => $champs['prenom'],
                'nom' => $champs['nom'],
                'email' => $champs['email'],
                'role_id' => $idRoleAdministrateur,
                'actif' => true,
                'doit_changer_mot_de_passe' => true,
            ]);
            $administrateur->mot_de_passe_hash = Hash::make($motDePasseTemporaire);
            $administrateur->save();

            // Pas d'utilisateur connecté en ligne de commande : l'auteur reste vide,
            // l'objet indique d'où vient la création.
            Journal::enregistrer(
                Journal::COMPTE_CREE,
                $administrateur->email . ' (Administrateur) – créé en ligne de commande'
            );
        });

        $this->newLine();
        $this->info('Compte créé.');
        $this->line('Mot de passe temporaire : <comment>' . $motDePasseTemporaire . '</comment>');
        $this->warn('Notez-le maintenant : il ne sera plus jamais affiché.');
        $this->line('Il devra être changé à la première connexion.');

        return self::SUCCESS;
    }
}
