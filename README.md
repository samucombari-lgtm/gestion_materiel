# Gestion du matériel du lycée

![Statut](https://img.shields.io/badge/statut-en%20d%C3%A9veloppement-orange?style=for-the-badge)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-11.8-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![Tests](https://img.shields.io/badge/tests-19%20r%C3%A9ussis-brightgreen?style=for-the-badge)
![Licence](https://img.shields.io/badge/licence-propri%C3%A9taire-lightgrey?style=for-the-badge)

Application web qui permet au lycée de **prêter son matériel** (ordinateurs portables, vidéoprojecteurs, caméras, câbles…) en sachant à tout moment **qui a quoi, depuis quand, et qui a le droit de faire quoi**.
Chaque utilisateur ne voit et ne fait que ce que son rôle autorise, et toutes les actions sensibles sont enregistrées dans un journal de sécurité.

Projet réalisé pour l'épreuve **E5 du BTS SIO option SLAM**, avec un volet sécurité mis en avant.

> 📷 **Capture d'écran : à ajouter** (par exemple le tableau de bord du gestionnaire), dans `docs/captures/`.

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Comptes de test](#comptes-de-test)
- [Tests automatiques](#tests-automatiques)
- [Structure du projet](#structure-du-projet)
- [Mise en production](#mise-en-production)
- [Documentation](#documentation)
- [Équipe](#équipe)
- [Licence](#licence)

---

## Fonctionnalités

| Rôle | Ce qu'il peut faire |
| --- | --- |
| **Visiteur** (non connecté) | Voir uniquement la page de connexion |
| **Élève** | Consulter le catalogue, demander un prêt, suivre **ses** prêts |
| **Enseignant** | Comme l'élève, et valider les demandes des **élèves de ses classes** |
| **Gestionnaire** | Gérer l'inventaire, valider toutes les demandes (sauf les siennes), enregistrer les sorties et les retours |
| **Administrateur** | Gérer les comptes et les rôles, consulter le journal de sécurité |

Fonctions principales :

- **Connexion sécurisée** : mots de passe hachés, blocage 15 minutes après 5 échecs, message d'erreur identique quel que soit le problème, session qui expire après 30 minutes d'inactivité.
- **Mot de passe temporaire** à la création d'un compte, à changer obligatoirement à la première connexion.
- **Inventaire** : ajout, modification, archivage (un matériel n'est jamais supprimé).
- **Prêts** : demande → validation ou refus (motif obligatoire) → sortie → retour avec l'état du matériel.
- **Tableau de bord par rôle** : mes prêts en cours, demandes à valider, sorties à enregistrer, retards.
- **Journal de sécurité** en lecture seule : connexions, accès refusés, changements de rôle, décisions sur les prêts.

Le détail des besoins est dans le [cahier des charges](docs/cahier-des-charges.md).

---

## Technologies utilisées

| Élément | Version | Rôle |
| --- | --- | --- |
| [PHP](https://www.php.net/) | 8.4 | Langage côté serveur |
| [Laravel](https://laravel.com/) | 12 | Framework : routes, contrôleurs, Eloquent, authentification, autorisations |
| [Blade](https://laravel.com/docs/blade) | (inclus dans Laravel) | Gabarits HTML, avec échappement automatique contre le XSS |
| [MariaDB](https://mariadb.org/) | 11.8 | Base de données |
| [Composer](https://getcomposer.org/) | 2 | Installation des dépendances PHP |
| [PHPUnit](https://phpunit.de/) | (inclus dans Laravel) | Tests automatiques, sur une base SQLite en mémoire |
| CSS | — | Une seule feuille de style, `public/css/app.css`, **sans outil de compilation** |

Aucun package en plus de ceux fournis avec Laravel n'a été ajouté.

> ℹ️ Le fichier `package.json` (Vite, Tailwind) vient du squelette Laravel et **n'est pas utilisé** : il n'y a pas besoin de Node.js ni de `npm`.

---

## Installation

Ces étapes installent l'application sur un poste de développement (Linux Debian ou équivalent).

### 1. Prérequis

- PHP **8.2 minimum** (développé avec PHP 8.4), avec les extensions `pdo_mysql`, `mbstring`, `openssl`, `xml`, `bcmath`, `intl` ; plus `pdo_sqlite` pour lancer les tests ;
- Composer 2 ;
- MariaDB 10.6 ou plus (développé avec MariaDB 11.8) ;
- Git.

Vérifier :

```bash
php -v
php -m | grep -E "pdo_mysql|pdo_sqlite|mbstring"
composer --version
mariadb --version
```

### 2. Récupérer le code

```bash
git clone https://github.com/samucombari-lgtm/gestion_materiel.git
cd gestion_materiel
composer install
```

### 3. Créer la base et l'utilisateur dédié

L'application n'utilise **jamais** le compte `root` de MariaDB, mais un utilisateur `materiel_app` qui n'a des droits que sur la base `gestion_materiel`.

```bash
sudo mariadb
```

```sql
CREATE DATABASE gestion_materiel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'materiel_app'@'localhost' IDENTIFIED BY 'choisir-un-mot-de-passe-solide';
GRANT ALL PRIVILEGES ON gestion_materiel.* TO 'materiel_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

`ALL PRIVILEGES` sur cette base seulement : les migrations ont besoin de créer des tables et des *triggers* (protection du journal).

### 4. Configurer l'application

```bash
cp .env.example .env
php artisan key:generate
```

Dans `.env`, renseigner le mot de passe choisi à l'étape 3 :

```dotenv
DB_PASSWORD=choisir-un-mot-de-passe-solide
```

Réglages propres au projet, déjà présents dans `.env.example` :

| Variable | Valeur | Rôle |
| --- | --- | --- |
| `APP_TIMEZONE` | `Europe/Paris` | Dates des prêts et du journal à l'heure française |
| `SESSION_DRIVER` | `database` | Sessions en base : permet de fermer les sessions d'un utilisateur (RA-06) |
| `SESSION_LIFETIME` | `30` | Déconnexion après 30 minutes d'inactivité (BF-02) |
| `SESSION_ENCRYPT` | `true` | Contenu des sessions chiffré en base |
| `MAX_PRETS_EN_COURS_ELEVE` | `3` | Limite de prêts en cours par élève (RA-07) — **valeur à préciser avec le lycée** |

### 5. Créer les tables et les données de test

```bash
php artisan migrate --seed
```

Cette commande crée les tables, les 4 rôles, les 11 permissions, la matrice rôles / permissions, et des **données de test** (comptes, classes, matériels, demandes de prêt).

Pour repartir de zéro (⚠️ efface toute la base) :

```bash
php artisan migrate:fresh --seed
```

#### Alternative : importer le script SQL

> ✅ **Les migrations restent la méthode recommandée.** Elles sont toujours à jour avec le code, elles fonctionnent aussi pour la base SQLite des tests, et c'est par elles que les évolutions de la base seront livrées. Le script SQL est fourni comme livrable (cahier des charges, section 9) et pour un poste où l'on veut seulement une base de démonstration.

Le fichier [docs/base_gestion_materiel.sql](docs/base_gestion_materiel.sql) contient la **structure** (les 17 tables et les 2 triggers du journal) et le **même jeu de données de test** que les seeders. Il remplace les étapes `migrate --seed` ci-dessus, à condition que la base et l'utilisateur `materiel_app` existent déjà (étape 3) :

```bash
mariadb -u materiel_app -p gestion_materiel < docs/base_gestion_materiel.sql
```

À savoir :

- ⚠️ le script commence par `DROP TABLE IF EXISTS` : il **remplace** les tables existantes et efface leurs données ;
- la table `migrations` est remplie : `php artisan migrate:status` affiche toutes les migrations comme déjà exécutées ;
- les dates des 3 demandes de prêt sont celles du jour où le fichier a été généré ;
- ⚠️ **développement et démonstration uniquement** : il contient les comptes de test, dont les mots de passe sont publics.

Pour **régénérer** le fichier après une modification des migrations ou des seeders :

```bash
php artisan migrate:fresh --seed
mysqldump -u materiel_app -p --single-transaction --no-tablespaces --triggers --skip-dump-date \
  --ignore-table-data=gestion_materiel.sessions --ignore-table-data=gestion_materiel.cache \
  --ignore-table-data=gestion_materiel.cache_locks --ignore-table-data=gestion_materiel.jobs \
  --ignore-table-data=gestion_materiel.job_batches --ignore-table-data=gestion_materiel.failed_jobs \
  --ignore-table-data=gestion_materiel.password_reset_tokens \
  gestion_materiel | sed 's#/\*!50017 DEFINER=`[^`]*`@`[^`]*`\*/ ##' > docs/base_gestion_materiel.sql
```

- **Les options `--ignore-table-data`** : elles créent les tables techniques (sessions, cache…) **vides**, pour qu'aucune session ni aucun mot de passe temporaire ne se retrouve dans le fichier.
- **La commande `sed`** : elle retire la clause `DEFINER` des triggers. Sans ça, l'import échouerait avec un autre utilisateur MariaDB.
- **L'en-tête explicatif** au début du fichier est à recopier à la main.

### 6. Lancer l'application

```bash
php artisan serve
```

Puis ouvrir http://127.0.0.1:8000 : la page de connexion s'affiche.

---

## Comptes de test

> ⚠️ **Développement uniquement.** Ces mots de passe sont écrits dans le code (`database/seeders/UtilisateurTestSeeder.php`). Le seeder **refuse de s'exécuter** quand `APP_ENV=production`.

| Rôle | E-mail | Mot de passe | Classe |
| --- | --- | --- | --- |
| Élève | eleve@lycee.test | Eleve-Test-2026 | 1re STMG 2 |
| Élève (2e) | eleve2@lycee.test | Eleve2-Test-2026 | 2nde 3 |
| Enseignant | enseignant@lycee.test | Enseignant-Test-2026 | 1re STMG 2 |
| Gestionnaire | gestionnaire@lycee.test | Gestionnaire-Test-2026 | — |
| Administrateur | admin@lycee.test | Admin-Test-2026 | — |

L'enseignant est dans la classe du 1er élève mais pas dans celle du 2e : cela permet de tester les règles « l'enseignant voit et valide les élèves de **ses** classes ».

---

## Tests automatiques

```bash
php artisan test                                        # tous les tests (19)
php artisan test --filter=AutorisationsTest             # le tableau 5.7 du cahier des charges
php artisan test --filter=CoherenceVisibilitePretsTest  # liste et détail des prêts toujours d'accord
php artisan test --filter=CreerAdministrateurTest       # commande de création du premier administrateur
php artisan test --filter=EnTetesSecuriteTest           # en-têtes de sécurité HTTP (audit OWASP ZAP)
```

Les tests tournent sur une base **SQLite en mémoire** (voir `phpunit.xml`) : ils ne touchent jamais la base MariaDB.

Ils prouvent notamment que :

- un visiteur est redirigé vers la connexion ;
- un élève qui ouvre l'adresse du prêt d'un autre reçoit **403**, et la tentative est écrite dans le journal ;
- un prêt inexistant et le prêt d'un autre donnent **exactement la même réponse** (pas de fuite d'information) ;
- personne ne valide sa propre demande, un enseignant ne valide pas un élève d'une autre classe ;
- un administrateur ne peut ni changer son propre rôle ni se désactiver ;
- un changement de rôle ferme les sessions de la personne concernée ;
- un compte désactivé ne peut pas se connecter, avec un message générique.

---

## Structure du projet

```text
app/
├── Console/Commands/        Commande materiel:creer-admin (premier administrateur)
├── Http/
│   ├── Controllers/         Un contrôleur par écran (Pret, Materiel, Compte, Journal, TableauDeBord)
│   │   └── Auth/            Connexion, déconnexion, changement de mot de passe
│   └── Middleware/          ExigerChangementMotDePasse (mot de passe temporaire)
├── Models/                  Modèles Eloquent (Utilisateur, Role, Permission, Classe, Materiel, Pret, Journal)
├── Policies/                Règles de périmètre : PretPolicy, UtilisateurPolicy
└── Providers/               AppServiceProvider : un Gate par permission
bootstrap/app.php            Redirections, nom des middlewares, journalisation des erreurs 403
config/prets.php             Réglages des prêts (limite RA-07)
database/
├── migrations/              Création des tables (noms en français) et protection du journal
└── seeders/                 Rôles, permissions, données de test
docs/                        Cahier des charges, architecture, autorisations, cahier de tests, script SQL
public/css/app.css           Feuille de style
resources/views/             Vues Blade (un dossier par écran, errors/ pour 403, 404, 429)
routes/web.php               Toutes les routes, avec leurs middlewares
tests/Feature/               Tests d'autorisation
```

---

## Mise en production

Hébergement prévu : **machine virtuelle Debian du lycée, serveur web Apache** (à confirmer).

Liste de vérification avant la mise en ligne :

- [ ] `APP_ENV=production` et **`APP_DEBUG=false`** dans `.env` (ES-11 : aucune erreur technique affichée) ;
- [ ] HTTPS activé sur le serveur web, et `SESSION_SECURE_COOKIE=true` dans `.env` (ES-08, ES-09) ;
- [ ] `APP_URL` avec l'adresse définitive (nom de domaine **à préciser**) ;
- [ ] mot de passe solide pour `materiel_app`, différent de celui du développement ;
- [ ] `php artisan migrate --force` **sans** `--seed` : les comptes de test ne doivent pas exister en production ;
- [ ] créer le premier compte administrateur (voir ci-dessous) ;
- [ ] `php artisan config:cache` et `php artisan route:cache` ;
- [ ] PHP : `expose_php = Off` dans `php.ini` (ne pas révéler la version de PHP) ;
- [ ] Apache : `ServerTokens Prod` et `ServerSignature Off` (ne pas révéler la version d'Apache), et ajouter `Header always set X-Content-Type-Options "nosniff"` pour les fichiers de `public/` (CSS, JS), qui ne passent pas par Laravel ;
- [ ] vérifier que l'en-tête `Strict-Transport-Security` (HSTS) apparaît bien en HTTPS. Si Apache est placé derrière un autre serveur (proxy), configurer les *trusted proxies* de Laravel, sinon l'application croit être en HTTP et n'envoie pas HSTS ;
- [ ] configurer l'envoi d'e-mails (`MAIL_*`) pour les rappels de retour (BF-23, pas encore développé).

### Créer le premier administrateur

En production, la base ne contient aucun compte : les comptes de test ne sont pas créés. Le premier administrateur se crée **depuis le terminal du serveur** :

```bash
php artisan db:seed --class=RoleSeeder        # les 4 rôles
php artisan db:seed --class=PermissionSeeder  # les 11 permissions et la matrice rôles / permissions
php artisan materiel:creer-admin              # demande le prénom, le nom et l'e-mail
```

La commande affiche **une seule fois** un mot de passe temporaire de 16 caractères, à changer à la première connexion. La création est écrite dans le journal de sécurité. Cet administrateur crée ensuite tous les autres comptes depuis l'application (menu **Comptes**).

---

## Documentation

- 📐 [Architecture : schéma fonctionnel et schéma technique](docs/architecture.md)
- 🔐 [Autorisations : matrice rôles / permissions et règles RA-01 à RA-08](docs/autorisations.md)
- ✅ [Cahier de tests : tests automatiques et manuels, à remplir](docs/cahier-de-tests.md)
- 📋 [Cahier des charges](docs/cahier-des-charges.md)
- 🗄️ [Script SQL : structure + données de test](docs/base_gestion_materiel.sql) (alternative aux migrations, voir « Installation »)

---

## Équipe

| Nom | Rôle |
| --- | --- |
| Samuel Combari | Conception et développement (projet individuel, BTS SIO option SLAM) |

---

## Licence

**Propriété du lycée Saint-Joseph — tous droits réservés.**

Ce projet a été réalisé dans le cadre du BTS SIO. Il n'est pas distribué sous licence libre : toute réutilisation ou diffusion du code nécessite l'accord du lycée Saint-Joseph.

Les bibliothèques utilisées gardent leur propre licence (par exemple, le framework Laravel est sous licence [MIT](https://opensource.org/licenses/MIT)).
