# Cahier de tests — Gestion du matériel du lycée

| | |
| --- | --- |
| **Application** | Gestion du matériel du lycée Saint-Joseph |
| **Auteur** | Samuel Combari — BTS SIO option SLAM, épreuve E5 |
| **Version testée** | branche `multi-etablissement`, après le commit « Multi-établissement, étape 5 : test complet d'isolation » |
| **Référence** | [Cahier des charges](cahier-des-charges.md) : besoins BF-xx (section 4), règles RA-xx (section 5.4), exigences ES-xx (section 6) |
| **Documents liés** | [Architecture](architecture.md) · [Autorisations](autorisations.md) |

Ce cahier liste **tous les tests de l'application** :

- les **tests automatiques** (PHPUnit), lancés par une commande ;
- les **tests manuels**, à faire dans le navigateur ou dans MariaDB, avec le résultat attendu.

Pour chaque test manuel, noter le **résultat obtenu**, cocher **OK** ou **KO**, et joindre une **capture d'écran** pour les tests marqués 📷 (preuves pour le jury).

---

## Sommaire

1. [Préparation](#1-préparation)
2. [Tests automatiques](#2-tests-automatiques)
3. [Connexion et sessions](#3-connexion-et-sessions-bf-01-bf-02-es-01-à-es-04-es-08)
4. [Mot de passe temporaire](#4-mot-de-passe-temporaire-section-56-es-02)
5. [Autorisations et pages d'erreur](#5-autorisations-et-pages-derreur-ra-01-ra-02-ra-08)
6. [Inventaire du matériel](#6-inventaire-du-matériel-bf-10-à-bf-12)
7. [Prêts](#7-prêts-bf-20-à-bf-24-ra-03-ra-04-ra-07)
8. [Tableau de bord](#8-tableau-de-bord-bf-30)
9. [Journal de sécurité](#9-journal-de-sécurité-bf-31-ra-08)
10. [Gestion des comptes](#10-gestion-des-comptes-bf-04-ra-05-ra-06)
11. [Isolation entre établissements](#11-isolation-entre-établissements-multi-établissement)
12. [Sécurité (OWASP)](#12-sécurité-owasp)
13. [Installation et exploitation](#13-installation-et-exploitation)
14. [Fonctions non développées](#14-fonctions-non-développées)
15. [Synthèse](#15-synthèse)
16. [Fiche d'anomalie](#16-fiche-danomalie)

---

## 1. Préparation

### 1.1 Environnement

| Élément | Version |
| --- | --- |
| Système | Debian (poste de développement) |
| PHP | 8.4 |
| Laravel | 12 |
| MariaDB | 11.8 |
| Navigateurs | Firefox et Chrome récents (BF : affichage correct sur téléphone à vérifier en test 11.10) |

### 1.2 Remettre la base dans l'état de départ

**Avant chaque série de tests** (⚠️ efface la base de développement) :

```bash
php artisan migrate:fresh --seed
php artisan cache:clear
php artisan serve
```

Puis ouvrir http://127.0.0.1:8000.

Pour se connecter à MariaDB (requêtes de vérification) :

```bash
mariadb -u materiel_app -p gestion_materiel
```

### 1.3 Jeu de données de test

Les seeders remplissent **deux lycées** (`database/seeders/EtablissementTestSeeder.php`) :

| N° | Établissement | Remarque |
| --- | --- | --- |
| 1 | **Lycée Saint-Joseph** (lycée A) | Établissement par défaut, créé par la migration. Sauf mention contraire, les tests de ce cahier se font dans ce lycée |
| 2 | **Lycée Jean-Moulin (test)** (lycée B) | Lycée fictif, pour les tests d'isolation (section 11) |

Les numéros (id) ci-dessous sont ceux obtenus après `php artisan migrate:fresh --seed`.

**Comptes** (mots de passe publics, développement uniquement) :

| Id | Nom dans ce cahier | Lycée | Rôle | E-mail | Mot de passe | Classe |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | **Emma** | A | Élève | eleve@lycee.test | Eleve-Test-2026 | 1re STMG 2 |
| 2 | **Bruno** | A | Élève | eleve2@lycee.test | Eleve2-Test-2026 | 2nde 3 |
| 3 | **Eric** | A | Enseignant | enseignant@lycee.test | Enseignant-Test-2026 | 1re STMG 2 (classe d'Emma) |
| 4 | **Gaëlle** | A | Gestionnaire | gestionnaire@lycee.test | Gestionnaire-Test-2026 | — |
| 5 | **Adam** | A | Administrateur | admin@lycee.test | Admin-Test-2026 | — |
| 6 | **Léa** | B | Élève | eleve@lycee-b.test | Eleve-B-Test-2026 | 1re STMG 2 (celle du lycée B) |
| 7 | **Hugo** | B | Enseignant | enseignant@lycee-b.test | Enseignant-B-Test-2026 | 1re STMG 2 (celle du lycée B) |
| 8 | **Inès** | B | Gestionnaire | gestionnaire@lycee-b.test | Gestionnaire-B-Test-2026 | — |
| 9 | **Basile** | B | Administrateur | admin@lycee-b.test | Admin-B-Test-2026 | — |
| 10 | **Sacha** | aucun | Super-administrateur | superadmin@academie.test | Super-Admin-Test-2026 | — |

**Classes** : n° 1 « 1re STMG 2 » (A), n° 2 « 2nde 3 » (A), n° 3 « 1re STMG 2 » (B). Le même nom existe dans les deux lycées.

**Matériels** :

| N° | Lycée | N° d'inventaire | Nom | État |
| --- | --- | --- | --- | --- |
| 1 | A | INV-PC-001 | Ordinateur portable Dell Latitude | Disponible |
| 2 | A | INV-PC-002 | Ordinateur portable HP ProBook | Disponible |
| 3 | A | INV-VP-001 | Vidéoprojecteur Epson EB-W51 | Disponible |
| 4 | A | INV-VP-002 | Vidéoprojecteur BenQ MW560 | En panne |
| 5 | A | INV-CAM-001 | Caméra Canon Legria HF G70 | En réparation |
| 6 | A | INV-CAB-001 | Câble HDMI 5 m | Disponible |
| 7 | B | INV-PC-001 | Ordinateur portable Lenovo ThinkPad | Disponible |
| 8 | B | INV-VP-001 | Vidéoprojecteur Optoma W400 | Disponible |
| 9 | B | INV-TAB-001 | Tablette Samsung Galaxy Tab A9 | Disponible |

INV-PC-001 et INV-VP-001 existent dans les deux lycées : le numéro d'inventaire est unique **par établissement**.

**Demandes de prêt** (toutes « En attente », du J+7 au J+14, J = jour du `migrate:fresh --seed`) :

| Prêt n° | Lycée | Demandeur | Matériel |
| --- | --- | --- | --- |
| 1 | A | Emma | INV-PC-001 (Dell, n° 1) |
| 2 | A | Bruno | INV-VP-001 (Epson, n° 3) |
| 3 | A | Eric | INV-PC-002 (n° 2) |
| 4 | B | Léa | INV-PC-001 (Lenovo, n° 7) |
| 5 | B | Hugo | INV-VP-001 (Optoma, n° 8) |

### 1.4 Astuces pour tester

- **Deux utilisateurs en même temps** : une fenêtre normale + une **fenêtre privée** (deux sessions séparées).
- **Contourner les contrôles du navigateur** (champ obligatoire, longueur minimale…) pour vérifier que **le serveur** refuse aussi : clic droit sur le champ → *Inspecter* → supprimer l'attribut `required`, `minlength` ou `min`.
- **Voir le code HTTP** (403, 404, 419, 429) : outils du navigateur (F12) → onglet *Réseau*.
- **Débloquer un compte** après les tests de blocage :
  ```sql
  UPDATE utilisateurs SET echecs_connexion = 0, bloque_jusqua = NULL;
  ```
  et `php artisan cache:clear` pour les e-mails inconnus et la limite par IP.

### 1.5 Légende

| Symbole | Signification |
| --- | --- |
| ☐ OK ☐ KO | À cocher après le test |
| 📷 | Capture d'écran à joindre (preuve pour le jury) |
| 🤖 | Aussi couvert par un test automatique (section 2) |

---

## 2. Tests automatiques

**Commande** :

```bash
php artisan test
```

Les tests tournent sur une base SQLite en mémoire : ils ne modifient pas la base MariaDB.

| N° | Test | Exigences | Fichier |
| --- | --- | --- | --- |
| A-01 | Un visiteur qui ouvre /prets est redirigé vers la connexion | RA-01, 5.7 | `AutorisationsTest` |
| A-02 | Un élève qui ouvre le prêt d'un autre élève reçoit 403, et c'est journalisé | RA-03, RA-08, 5.7 | `AutorisationsTest` |
| A-03 | Un élève ne peut valider aucun prêt | RA-02, 5.7 | `AutorisationsTest` |
| A-04 | Un enseignant ne peut pas valider un élève d'une autre classe | RA-03, 5.7 | `AutorisationsTest` |
| A-05 | Un gestionnaire ne peut pas valider sa propre demande | RA-04, 5.7 | `AutorisationsTest` |
| A-06 | Un administrateur ne peut pas retirer son propre rôle | RA-05, 5.7 | `AutorisationsTest` |
| A-07 | Changer le rôle d'un élève ferme ses sessions, et seulement les siennes | RA-06, 5.7 | `AutorisationsTest` |
| A-08 | Un compte désactivé ne peut pas se connecter, message générique | ES-04, 5.7 | `AutorisationsTest` |
| A-09 | Un élève reçoit 403 sur les pages de gestion | RA-01, RA-02 | `AutorisationsTest` |
| A-10 | Prêt inexistant et prêt d'un autre donnent la même réponse | RA-03 (IDOR) | `AutorisationsTest` |
| A-11 | Un administrateur ne peut pas se désactiver | RA-05 | `AutorisationsTest` |
| A-12 | Les validations autorisées fonctionnent (enseignant → élève de sa classe, gestionnaire → enseignant) | RA-04 | `AutorisationsTest` |
| A-13 | La liste et le détail des prêts donnent toujours la même réponse | RA-03 | `CoherenceVisibilitePretsTest` |
| A-14 | La commande crée un administrateur avec un mot de passe temporaire | Mise en production | `CreerAdministrateurTest` |
| A-15 | La commande refuse un e-mail déjà utilisé ou invalide | ES-10 | `CreerAdministrateurTest` |
| A-16 | `that true is true` (exemple fourni par Laravel) | — | `Unit/ExampleTest` |
| A-17 | Les en-têtes de sécurité sont présents sur toutes les sortes de pages, sans `X-Powered-By` | Audit ZAP | `EnTetesSecuriteTest` |
| A-18 | HSTS n'est envoyé qu'en HTTPS | ES-09 | `EnTetesSecuriteTest` |
| A-19 | Aucune vue ne contient de JavaScript ou de CSS dans le HTML (sinon la CSP le bloquerait) | Audit ZAP | `EnTetesSecuriteTest` |
| A-20 | Un administrateur ne peut pas donner le rôle super_admin (menu et requête fabriquée) | Isolation, RA-05 | `AutorisationsTest` |
| A-21 | L'option `--super` crée un super-administrateur sans établissement | Isolation | `CreerAdministrateurTest` |
| A-22 | Les listes du lycée A ne montrent rien du lycée B (catalogue, prêts, tableau de bord, comptes, classes) | Isolation | `IsolationEtablissementsTest` |
| A-23 | 14 adresses directes vers le lycée B (lecture et actions) donnent la même réponse qu'un id inexistant, la base ne change pas | Isolation, RA-03 | `IsolationEtablissementsTest` |
| A-24 | Pour chaque compte du lycée A, aucun modèle ne renvoie de donnée d'un autre lycée ou sans établissement | Isolation | `IsolationEtablissementsTest` |
| A-25 | La connexion marche pour les deux lycées et le super-administrateur, y compris au rechargement depuis la session | Isolation, BF-01 | `IsolationEtablissementsTest` |
| A-26 | Le super-administrateur voit les deux lycées | Isolation | `IsolationEtablissementsTest` |
| A-27 | Un élève ne peut pas demander un matériel d'un autre lycée | Isolation, ES-10 | `IsolationEtablissementsTest` |
| A-28 | Un administrateur ne peut pas mettre un compte dans une classe d'un autre lycée | Isolation, ES-10 | `IsolationEtablissementsTest` |
| A-29 | Le numéro d'inventaire est unique par établissement | Isolation, BF-11 | `IsolationEtablissementsTest` |
| A-30 | Le journal est isolé par établissement ; le super-administrateur voit tout | Isolation, BF-31 | `IsolationEtablissementsTest` |

**Résultat** :

| Date | Exécuté par | Résultat | Statut |
| --- | --- | --- | --- |
| 19/10/2026 | Claude Code (développement) | `Tests: 19 passed (173 assertions)` | ✅ OK |
| 29/10/2026 | Claude Code (développement, multi-établissement) | `Tests: 30 passed (329 assertions)` | ✅ OK |
| | Samuel Combari | | ☐ OK ☐ KO 📷 |

**Vérification que les tests détectent les erreurs** (sabotage volontaire, puis restauration du code) :

| Règle retirée du code | Test qui doit échouer | Obtenu (19/10/2026) |
| --- | --- | --- |
| RA-04 dans `PretPolicy::valider` | A-05 | ✅ A-05 a échoué |
| Élèves des classes dans `Pret::visiblesPar` | A-13 | ✅ A-13 a échoué, avec les prêts concernés |
| RA-05 dans `UtilisateurPolicy::changerRole` | A-06 | ✅ A-06 a échoué |

| Protection retirée du code (multi-établissement) | Test qui doit échouer | Obtenu (29/10/2026) |
| --- | --- | --- |
| `EtablissementScope` désactivé (aucun filtre) | A-22, A-23, A-24, A-30 | ✅ ces 4 tests ont échoué |
| Liste blanche des rôles dans `CompteController` | A-20 | ✅ A-20 a échoué |
| Filtre établissement de `Rule::exists` sur le matériel (`PretController`) | A-27 | ✅ A-27 a échoué |
| Filtre établissement de `Rule::exists` sur les classes (`CompteController`) | A-28 | ✅ A-28 a échoué |
| Filtre établissement de `Rule::unique` sur le numéro d'inventaire | A-29 | ✅ A-29 a échoué |
| Isolation du journal (scope et établissement de l'auteur) | A-24, A-30 | ✅ A-24 et A-30 ont échoué |
| `Auth::hasUser()` remplacé par `Auth::user()` dans le scope | A-25 | ⚠️ A-25 **ne se termine jamais** (boucle infinie), arrêté au bout de 120 s : le problème est détecté, mais par un blocage et pas par un échec |

---

## 3. Connexion et sessions (BF-01, BF-02, ES-01 à ES-04, ES-08)

| ID | Exigence | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-CON-01 | BF-01 | Ouvrir http://127.0.0.1:8000 sans être connecté | Redirection vers `/connexion`. La page affiche le formulaire et la mention RGPD. 🤖 | | ☐ OK ☐ KO |
| T-CON-02 | BF-01 | Se connecter avec Emma (bons identifiants) | Arrivée sur le tableau de bord : « Bonjour Emma Eleve ». Menu : Tableau de bord, Matériel, Prêts, Mot de passe, Se déconnecter. 📷 | | ☐ OK ☐ KO |
| T-CON-03 | ES-04 | E-mail `eleve@lycee.test`, mauvais mot de passe | Retour au formulaire, message « Identifiants incorrects. », l'e-mail est conservé, le mot de passe est vidé | | ☐ OK ☐ KO |
| T-CON-04 | ES-04 | E-mail inventé `personne@lycee.test`, n'importe quel mot de passe | **Exactement le même message** qu'en T-CON-03 🤖 📷 | | ☐ OK ☐ KO |
| T-CON-05 | ES-10 | Supprimer `required` des deux champs (§ 1.4), envoyer vide | « L'adresse e-mail est obligatoire. » et « Le mot de passe est obligatoire. » | | ☐ OK ☐ KO |
| T-CON-06 | ES-10 | Supprimer `type="email"`, saisir `pas-un-email` | « L'adresse e-mail n'est pas valide. » | | ☐ OK ☐ KO |
| T-CON-07 | ES-03 | 5 mauvais mots de passe pour Gaëlle, puis le **bon** mot de passe | Au 6e essai : « Trop de tentatives. Réessayez dans quelques minutes. », connexion refusée. 📷 | | ☐ OK ☐ KO |
| T-CON-08 | ES-03 | Après T-CON-07 : `SELECT email, echecs_connexion, bloque_jusqua FROM utilisateurs WHERE email LIKE 'gest%';` | `bloque_jusqua` = heure du 5e échec + 15 minutes (heure de Paris), `echecs_connexion` = 0 | | ☐ OK ☐ KO |
| T-CON-09 | ES-03, ES-04 | 6 essais avec l'e-mail inventé `pirate@lycee.test` | Au 6e essai : même message « Trop de tentatives… » qu'en T-CON-07 (on ne peut pas savoir si le compte existe) | | ☐ OK ☐ KO |
| T-CON-10 | ES-03 | `php artisan cache:clear`, puis 11 envois du formulaire en moins d'une minute (e-mails différents) | Au 11e : page « Trop de tentatives » en français, code **429** | | ☐ OK ☐ KO |
| T-CON-11 | 5.7 | `UPDATE utilisateurs SET actif = 0 WHERE email = 'eleve2@lycee.test';` puis connexion de Bruno avec le bon mot de passe | « Identifiants incorrects. » (message générique), pas de connexion. Remettre `actif = 1` après. 🤖 | | ☐ OK ☐ KO |
| T-CON-12 | BF-02 | Connecté, cliquer « Se déconnecter » | Retour à `/connexion`. Taper ensuite `/tableau-de-bord` → redirection vers `/connexion` | | ☐ OK ☐ KO |
| T-CON-13 | BF-02 | Mettre `SESSION_LIFETIME=1` dans `.env`, se connecter, attendre 2 minutes sans rien faire, recharger | Redirection vers `/connexion`. **Remettre `SESSION_LIFETIME=30` après.** | | ☐ OK ☐ KO |
| T-CON-14 | ES-01 | `SELECT email, LEFT(mot_de_passe_hash, 7) FROM utilisateurs;` | Tous les hachages commencent par `$2y$12$` (bcrypt), aucun mot de passe en clair | | ☐ OK ☐ KO 📷 |
| T-CON-15 | ES-08 | F12 → *Stockage* → *Cookies* : noter la valeur du cookie de session avant et après la connexion | La valeur **change** à la connexion (nouvel identifiant de session) | | ☐ OK ☐ KO |
| T-CON-16 | ES-08 | Même écran : regarder les attributs du cookie de session | `HttpOnly` coché, `SameSite` = `Lax`. (`Secure` seulement en HTTPS, voir T-SEC-09) | | ☐ OK ☐ KO |
| T-CON-17 | ES-07 | Déconnexion forgée : ouvrir `/deconnexion` dans la barre d'adresse (requête GET) | La déconnexion **n'a pas lieu** (page « Action impossible », code 405) : elle n'est possible qu'en POST avec jeton CSRF | | ☐ OK ☐ KO |

---

## 4. Mot de passe temporaire (section 5.6, ES-02)

**Préparation** : `UPDATE utilisateurs SET doit_changer_mot_de_passe = 1 WHERE email = 'eleve@lycee.test';`

| ID | Exigence | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-MDP-01 | 5.6 | Se connecter avec Emma | Arrivée sur « Changer le mot de passe », avec le message bleu « Vous utilisez un mot de passe temporaire… ». 📷 | | ☐ OK ☐ KO |
| T-MDP-02 | 5.6, RA-02 | Taper `/tableau-de-bord`, puis `/prets`, puis `/materiels` | Chaque fois : retour sur la page de changement du mot de passe | | ☐ OK ☐ KO |
| T-MDP-03 | 5.6 | Cliquer « Se déconnecter » depuis cette page | La déconnexion fonctionne (l'utilisateur n'est pas bloqué) | | ☐ OK ☐ KO |
| T-MDP-04 | Sécurité | Mot de passe actuel faux | « Le mot de passe actuel est incorrect. » | | ☐ OK ☐ KO |
| T-MDP-05 | ES-02 | Nouveau mot de passe de 8 caractères (supprimer `minlength`, § 1.4) | « Le nouveau mot de passe doit contenir au moins 12 caractères. » | | ☐ OK ☐ KO |
| T-MDP-06 | ES-10 | Confirmation différente du nouveau mot de passe | « Les deux nouveaux mots de passe ne sont pas identiques. » | | ☐ OK ☐ KO |
| T-MDP-07 | Sécurité | Nouveau mot de passe = `Eleve-Test-2026` (l'actuel) | « Le nouveau mot de passe doit être différent de l'actuel. » | | ☐ OK ☐ KO |
| T-MDP-08 | 5.6 | Emma connectée aussi dans une fenêtre privée. Dans la 1re fenêtre : changement correct (ex. `Nouveau-Mdp-2026`) | 1re fenêtre : tableau de bord + « Votre mot de passe a été modifié. ». Fenêtre privée rechargée : retour à `/connexion`. 📷 | | ☐ OK ☐ KO |
| T-MDP-09 | ES-01 | Se reconnecter avec l'ancien, puis avec le nouveau mot de passe | Ancien refusé, nouveau accepté | | ☐ OK ☐ KO |

**Remise en état** : `php artisan db:seed --class=UtilisateurTestSeeder` (remet les mots de passe de test).

---

## 5. Autorisations et pages d'erreur (RA-01, RA-02, RA-08)

### 5.1 Menu par rôle (`@can`)

| ID | Compte | Résultat attendu (liens du menu) | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- |
| T-AUT-01 | Emma (élève) | Tableau de bord, Matériel, Prêts, Mot de passe | | ☐ OK ☐ KO |
| T-AUT-02 | Eric (enseignant) | Tableau de bord, Matériel, Prêts, Mot de passe | | ☐ OK ☐ KO |
| T-AUT-03 | Gaëlle (gestionnaire) | Tableau de bord, Matériel, Prêts, Mot de passe | | ☐ OK ☐ KO |
| T-AUT-04 | Adam (administrateur) | Tableau de bord, Matériel, **Comptes**, **Journal**, Mot de passe (**pas** de Prêts) | | ☐ OK ☐ KO 📷 |

### 5.2 Adresses tapées à la main (RA-02 : cacher un lien ne protège rien)

| ID | Compte | Adresse tapée | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-AUT-05 | Emma | `/comptes` | Page « Accès refusé », code **403** 🤖 📷 | | ☐ OK ☐ KO |
| T-AUT-06 | Emma | `/journal` | 403 🤖 | | ☐ OK ☐ KO |
| T-AUT-07 | Emma | `/materiels/nouveau` | 403 🤖 | | ☐ OK ☐ KO |
| T-AUT-08 | Eric | `/materiels/1/modifier` | 403 | | ☐ OK ☐ KO |
| T-AUT-09 | Gaëlle | `/comptes` et `/journal` | 403 (le gestionnaire n'est pas administrateur) | | ☐ OK ☐ KO |
| T-AUT-10 | Adam | `/prets` et `/prets/1` | 403 (l'administrateur n'a pas `pret.voir`) | | ☐ OK ☐ KO |
| T-AUT-11 | Adam | `/materiels/nouveau` | 403 (l'administrateur n'est pas gestionnaire) | | ☐ OK ☐ KO |

### 5.3 Pages d'erreur (RA-08, ES-11)

| ID | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- |
| T-AUT-12 | Regarder la page 403 de T-AUT-05 | Titre « Accès refusé », texte « Vous n'avez pas le droit d'accéder à cette page. », lien « Retour à l'accueil », **aucun détail technique** | | ☐ OK ☐ KO |
| T-AUT-13 | Connecté, ouvrir `/page-qui-nexiste-pas` | Page « Page introuvable » en français, code 404 | | ☐ OK ☐ KO |
| T-AUT-14 | Gaëlle : ouvrir `/materiels/9999/modifier` | Page « Page introuvable », code 404 | | ☐ OK ☐ KO |

---

## 6. Inventaire du matériel (BF-10 à BF-12)

| ID | Exigence | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- | --- |
| T-MAT-01 | BF-10 | Emma | Menu Matériel | Tableau avec nom, catégorie, n° d'inventaire, état (pastille de couleur), disponibilité. 6 matériels. **Aucun** bouton Ajouter / Modifier / Archiver. Lien « Emprunter » sur les matériels disponibles. 📷 | | ☐ OK ☐ KO |
| T-MAT-02 | BF-10 | Emma | Rechercher `vidéo` | Seuls les 2 vidéoprojecteurs | | ☐ OK ☐ KO |
| T-MAT-03 | BF-10 | Gaëlle | Menu Matériel | Mêmes colonnes + Modifier et Archiver sur chaque ligne, bouton « Ajouter un matériel », case « Afficher les archivés » | | ☐ OK ☐ KO |
| T-MAT-04 | BF-11 | Gaëlle | Ajouter : nom `Tablette Samsung`, catégorie `Tablette`, n° `INV-TAB-001`, état Disponible | Retour au catalogue, « Le matériel « Tablette Samsung » a été ajouté. », la tablette apparaît. (INV-TAB-001 existe déjà dans le lycée B : c'est autorisé) 🤖 | | ☐ OK ☐ KO |
| T-MAT-05 | ES-10 | Gaëlle | Ajouter un matériel avec le n° `INV-PC-001` | « Ce numéro d'inventaire est déjà utilisé dans votre établissement. », rien n'est créé 🤖 | | ☐ OK ☐ KO |
| T-MAT-06 | ES-10 | Gaëlle | Ajouter avec tous les champs vides (supprimer `required`) | Un message par champ : « Le nom est obligatoire. », « La catégorie est obligatoire. », « Le numéro d'inventaire est obligatoire. » | | ☐ OK ☐ KO |
| T-MAT-07 | BF-12 | Gaëlle | Modifier la tablette : état « En panne » | « … a été modifié. », pastille rouge « En panne », disponibilité « Non » | | ☐ OK ☐ KO |
| T-MAT-08 | BF-12 | Gaëlle | Dans le formulaire, ouvrir la liste des états | Seulement Disponible, En panne, En réparation (ni « Prêté », ni « Archivé ») | | ☐ OK ☐ KO |
| T-MAT-09 | BF-12 | Gaëlle | F12 : changer la valeur d'une option en `prete`, enregistrer | « Cet état ne peut pas être choisi ici. » (le serveur refuse) | | ☐ OK ☐ KO |
| T-MAT-10 | BF-11 | Gaëlle | Archiver la tablette (confirmer) | Confirmation demandée, puis « … a été archivé. », la tablette disparaît du catalogue | | ☐ OK ☐ KO |
| T-MAT-11 | BF-11 | Gaëlle | Cocher « Afficher les archivés » | La tablette réapparaît, pastille grise « Archivé », pas de bouton Archiver | | ☐ OK ☐ KO |
| T-MAT-12 | BF-11 | Emma | Ajouter `?archives=1` à l'adresse du catalogue | La tablette archivée reste **invisible** | | ☐ OK ☐ KO |
| T-MAT-13 | BF-11 | Gaëlle | Modifier la tablette archivée : état Disponible | Elle revient dans le catalogue normal | | ☐ OK ☐ KO |
| T-MAT-14 | BF-11 | Gaëlle | Matériel n° 1 (PC-001) : le prêt n° 1 d'Emma est en attente | **Pas** de bouton Archiver sur cette ligne | | ☐ OK ☐ KO |
| T-MAT-15 | BF-11 | MariaDB | `DELETE FROM materiels WHERE numero_inventaire = 'INV-PC-001';` | **Refusé** : erreur de clé étrangère (le matériel a un prêt) | | ☐ OK ☐ KO 📷 |
| T-MAT-16 | BF-11 | — | Chercher un bouton ou une adresse pour supprimer un matériel | Aucun : on archive, on ne supprime jamais | | ☐ OK ☐ KO |

---

## 7. Prêts (BF-20 à BF-24, RA-03, RA-04, RA-07)

**Préparation** : `php artisan migrate:fresh --seed` (prêts n° 1, 2, 3 en attente).

### 7.1 Voir les prêts (BF-24, RA-03)

| ID | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-PRE-01 | Emma | Menu Prêts | Seulement le prêt n° 1 (le sien) | | ☐ OK ☐ KO |
| T-PRE-02 | Bruno | Menu Prêts | Seulement le prêt n° 2 | | ☐ OK ☐ KO |
| T-PRE-03 | Eric | Menu Prêts | Prêts n° 1 (Emma, sa classe) et n° 3 (le sien). **Pas** le n° 2 (Bruno, autre classe) | | ☐ OK ☐ KO 📷 |
| T-PRE-04 | Gaëlle | Menu Prêts | Les 3 prêts | | ☐ OK ☐ KO |
| T-PRE-05 | Emma | Taper `/prets/2` (prêt de Bruno) | « Accès refusé », 403 🤖 📷 | | ☐ OK ☐ KO |
| T-PRE-06 | Emma | Taper `/prets/99999` (prêt inexistant) | **Exactement la même page** qu'en T-PRE-05 (403, pas 404) : on ne peut pas deviner quels prêts existent 🤖 | | ☐ OK ☐ KO |
| T-PRE-07 | Eric | Taper `/prets/2` | 403 (Bruno n'est pas dans sa classe) 🤖 | | ☐ OK ☐ KO |
| T-PRE-08 | Eric | Ouvrir le prêt n° 1 | Détail du prêt d'Emma, avec les boutons « Valider la demande » et « Refuser la demande » | | ☐ OK ☐ KO |
| T-PRE-09 | Emma | Filtre « Statut : Refusé » | « Aucun prêt. » | | ☐ OK ☐ KO |

### 7.2 Demander un prêt (BF-20, RA-07)

| ID | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-PRE-10 | Emma | Catalogue → « Emprunter » sur le câble HDMI | Formulaire avec le câble présélectionné, message « Vous avez 1 prêt(s) en cours sur 3 autorisés. » | | ☐ OK ☐ KO |
| T-PRE-11 | Emma | Dates : demain → dans 3 jours, envoyer | Page du nouveau prêt, statut « En attente », « Votre demande est enregistrée. Elle doit maintenant être validée. » 📷 | | ☐ OK ☐ KO |
| T-PRE-12 | ES-10 | Emma | Date de début = hier (supprimer `min`) | « La date de début ne peut pas être dans le passé. » | | ☐ OK ☐ KO |
| T-PRE-13 | ES-10 | Emma | Date de fin avant la date de début | « La date de fin doit être le même jour ou après la date de début. » | | ☐ OK ☐ KO |
| T-PRE-14 | BF-20 | Emma | F12 : remplacer l'id d'un matériel de la liste par `4` (vidéoprojecteur en panne) | « Ce matériel ne peut pas être emprunté. » | | ☐ OK ☐ KO |
| T-PRE-15 | RA-07 | Emma | Faire une 3e demande (accepté), puis une 4e | La 4e : « Vous avez déjà 3 prêts en cours : c'est le maximum autorisé. » 📷 | | ☐ OK ☐ KO |
| T-PRE-16 | RA-07 | Eric | Faire 4 demandes | Toutes acceptées : la limite ne concerne que les élèves | | ☐ OK ☐ KO |
| T-PRE-17 | RA-02 | Adam | Taper `/prets/nouveau` | 403 (l'administrateur n'a pas `pret.demander`) | | ☐ OK ☐ KO |

### 7.3 Valider et refuser (BF-21, RA-04)

| ID | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-PRE-18 | Emma | Ouvrir son prêt n° 1 | **Aucun** bouton Valider / Refuser. Bouton « Annuler le prêt » présent | | ☐ OK ☐ KO |
| T-PRE-19 | Eric | Ouvrir son propre prêt n° 3 | **Aucun** bouton Valider / Refuser (RA-04) 🤖 | | ☐ OK ☐ KO |
| T-PRE-20 | Gaëlle | Demander un prêt, puis ouvrir sa propre demande | **Aucun** bouton Valider / Refuser (RA-04) 🤖 📷 | | ☐ OK ☐ KO |
| T-PRE-21 | Eric | Prêt n° 1 (Emma) → « Valider la demande » | « La demande a été validée. », statut « Validé », « Validé par Eric Enseignant » 🤖 | | ☐ OK ☐ KO |
| T-PRE-22 | Gaëlle | Prêt n° 2 (Bruno) → « Refuser » avec le motif vide (supprimer `required`) | « Le motif du refus est obligatoire. » | | ☐ OK ☐ KO |
| T-PRE-23 | Gaëlle | Prêt n° 2 → Refuser avec le motif `Réservé pour un examen` | « La demande a été refusée. », statut « Refusé », motif affiché | | ☐ OK ☐ KO |
| T-PRE-24 | Bruno | Ouvrir son prêt n° 2 | Statut « Refusé », « Refusé par Gaelle Gestionnaire », motif visible | | ☐ OK ☐ KO |
| T-PRE-25 | Gaëlle | Prêt n° 3 (Eric) → Valider | « La demande a été validée. » (la demande d'un enseignant est validée par un gestionnaire) 🤖 | | ☐ OK ☐ KO |
| T-PRE-26 | Bruno | Demander le PC INV-PC-001 aux **mêmes dates** que le prêt n° 1 (validé en T-PRE-21) | « Ce matériel est déjà réservé sur une partie de ces dates. » | | ☐ OK ☐ KO |

### 7.4 Sortie, retour, annulation (BF-22)

| ID | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-PRE-27 | Eric | Prêt n° 1 (validé) | **Pas** de bouton de sortie (réservé au gestionnaire) | | ☐ OK ☐ KO |
| T-PRE-28 | Gaëlle | Prêt n° 1 → « Enregistrer la sortie du matériel » | « La sortie du matériel est enregistrée. », statut « Sorti ». Dans le catalogue, PC-001 = « Prêté » | | ☐ OK ☐ KO |
| T-PRE-29 | Emma | Ouvrir son prêt n° 1 (sorti) | **Pas** de bouton « Annuler le prêt » | | ☐ OK ☐ KO |
| T-PRE-30 | Gaëlle | Prêt n° 1 → Retour, état « En panne » | « Le retour du matériel est enregistré. », statut « Rendu », « Rendu le » = aujourd'hui, « État au retour : En panne ». Catalogue : PC-001 = « En panne » 📷 | | ☐ OK ☐ KO |
| T-PRE-31 | BF-22 | MariaDB : `SELECT id, statut, date_retour, etat_retour FROM prets WHERE id = 1;` | `rendu`, date du jour, `en_panne` | | ☐ OK ☐ KO |
| T-PRE-32 | Gaëlle | Valider une nouvelle demande sur un matériel « En panne » (mettre un matériel en panne entre la demande et la validation) | « Impossible de valider : le matériel est « En panne ». » | | ☐ OK ☐ KO |
| T-PRE-33 | Emma | Une de ses demandes en attente → « Annuler le prêt » (confirmer) | « Le prêt a été annulé. », statut « Annulé » | | ☐ OK ☐ KO |
| T-PRE-34 | Gaëlle | Une demande validée d'un autre utilisateur → Annuler | Accepté (le gestionnaire peut annuler toute demande pas encore sortie) | | ☐ OK ☐ KO |

---

## 8. Tableau de bord (BF-30)

**Préparation** : `php artisan migrate:fresh --seed`, puis Gaëlle valide et sort le prêt n° 1, puis dans MariaDB :
`UPDATE prets SET date_debut = CURDATE() - INTERVAL 5 DAY, date_fin_prevue = CURDATE() - INTERVAL 1 DAY WHERE id = 1;`

| ID | Compte | Résultat attendu (blocs affichés) | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- |
| T-TDB-01 | Emma | Bloc rouge « Retards (1) » avec le prêt n° 1, puis « Mes prêts en cours », bouton « Demander un prêt » 📷 | | ☐ OK ☐ KO |
| T-TDB-02 | Bruno | « Mes prêts en cours » seulement. **Pas** de bloc Retards (le retard d'Emma ne le concerne pas) | | ☐ OK ☐ KO |
| T-TDB-03 | Eric | « Retards (1) » (son élève Emma), « Demandes à valider » (vide : sa propre demande n'y est pas), « Mes prêts en cours » | | ☐ OK ☐ KO |
| T-TDB-04 | Gaëlle | « Retards (1) », « Demandes à valider » (prêts n° 2 et 3), « Sorties à enregistrer », « Mes prêts en cours » 📷 | | ☐ OK ☐ KO |
| T-TDB-05 | Adam | « Sécurité – dernières 24 heures » : nombre de connexions échouées et d'accès refusés, 10 derniers événements, lien « Voir tout le journal » | | ☐ OK ☐ KO |

---

## 9. Journal de sécurité (BF-31, RA-08)

**Préparation** : `php artisan migrate:fresh --seed`, puis réaliser dans l'ordre : une connexion avec un e-mail inventé, une connexion d'Emma avec un mauvais mot de passe, une connexion réussie d'Emma, Emma tape `/journal`, Eric valide le prêt n° 1, Gaëlle refuse le prêt n° 2 avec un motif.

| ID | Exigence | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-JOU-01 | BF-31 | Adam → menu Journal | Une ligne par action ci-dessus **sauf l'e-mail inventé** (voir T-JOU-02), la plus récente en haut, avec date et heure, utilisateur, action, objet, adresse IP 📷 | | ☐ OK ☐ KO |
| T-JOU-02 | Section 6, isolation | Ligne de l'e-mail inventé : la chercher chez Adam, puis chez Sacha (super-administrateur) | Adam : **absente** (un e-mail inconnu n'a pas d'établissement). Sacha : utilisateur « — », action « Connexion échouée », objet « Compte inconnu : … » | | ☐ OK ☐ KO |
| T-JOU-03 | Section 6 | Ligne du mauvais mot de passe | Utilisateur eleve@lycee.test, objet « Mauvais mot de passe : … ». **Le mot de passe tapé n'apparaît nulle part** | | ☐ OK ☐ KO |
| T-JOU-04 | RA-08 | Ligne du 403 | Action « Accès refusé (403) », objet `GET /journal` 🤖 | | ☐ OK ☐ KO |
| T-JOU-05 | Section 6 | Lignes des prêts | « Prêt validé » par Eric (Prêt n° 1…), « Prêt refusé » par Gaëlle avec le motif | | ☐ OK ☐ KO |
| T-JOU-06 | Fuseau | Comparer l'heure des lignes avec l'heure de l'ordinateur | Heure de Paris | | ☐ OK ☐ KO |
| T-JOU-07 | BF-31 | Filtre Action = « Accès refusé (403) » | Seulement les lignes de 403 | | ☐ OK ☐ KO |
| T-JOU-08 | BF-31 | Sacha (super-administrateur) : recherche `inconnu` ou l'e-mail inventé | La ligne « Compte inconnu » (chez Adam : « Aucun événement. ») | | ☐ OK ☐ KO |
| T-JOU-09 | BF-31 | Filtre « Du » = demain | « Aucun événement. » | | ☐ OK ☐ KO |
| T-JOU-10 | Lecture seule | Chercher un bouton Modifier / Supprimer dans le journal | Aucun | | ☐ OK ☐ KO |
| T-JOU-11 | Lecture seule | MariaDB : `UPDATE journal SET action = 'falsifie' WHERE id = 1;` | **Refusé** : « Le journal de securite est en lecture seule » 📷 | | ☐ OK ☐ KO |
| T-JOU-12 | Lecture seule | MariaDB : `DELETE FROM journal;` | **Refusé**, même message | | ☐ OK ☐ KO |
| T-JOU-13 | Lecture seule | MariaDB : `INSERT INTO journal (action) VALUES ('test');` | **Refusé** : `date_heure` n'a pas de valeur par défaut | | ☐ OK ☐ KO |

---

## 10. Gestion des comptes (BF-04, RA-05, RA-06)

**Préparation** : `php artisan migrate:fresh --seed`.

### 10.1 Créer et modifier

| ID | Exigence | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- | --- |
| T-CPT-01 | BF-04 | Adam | Menu Comptes | Liste des 5 comptes avec rôle, classes, statut. Sur la ligne d'Adam : « Votre compte » et **aucun** bouton 📷 | | ☐ OK ☐ KO |
| T-CPT-02 | BF-04 | Adam | Filtres : rôle Élève, puis statut Désactivés | Emma et Bruno, puis « Aucun compte trouvé. » | | ☐ OK ☐ KO |
| T-CPT-03 | BF-04, 5.6 | Adam | Créer un compte : Nina Nouveau, `nina@lycee.test`, rôle Enseignant, classe 1re STMG 2 | « Le compte de Nina Nouveau a été créé. » + encadré avec un mot de passe temporaire de 16 lettres et chiffres et « Il ne sera plus affiché. » 📷 | | ☐ OK ☐ KO |
| T-CPT-04 | 5.6 | Adam | Recharger la page | Le mot de passe temporaire a disparu | | ☐ OK ☐ KO |
| T-CPT-05 | ES-01 | MariaDB | `SELECT email, LEFT(mot_de_passe_hash, 4), doit_changer_mot_de_passe FROM utilisateurs WHERE email = 'nina@lycee.test';` | `$2y$` et `1` | | ☐ OK ☐ KO |
| T-CPT-06 | 5.6 | Nina | Fenêtre privée : se connecter avec le mot de passe temporaire | Obligée de changer son mot de passe (comme T-MDP-01) | | ☐ OK ☐ KO |
| T-CPT-07 | ES-10 | Adam | Créer un compte avec l'e-mail `ELEVE@lycee.test` | « Cette adresse e-mail est déjà utilisée par un autre compte. » | | ☐ OK ☐ KO |
| T-CPT-08 | BF-04 | Adam | Modifier Nina : e-mail `nina.n@lycee.test` | « Le compte a été modifié. » | | ☐ OK ☐ KO |
| T-CPT-09 | RA-05 | Adam | Taper `/comptes/<id d'Adam>/modifier` | 403 (pas de modification de son propre compte) | | ☐ OK ☐ KO |

### 10.2 Rôles et désactivation

| ID | Exigence | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- | --- |
| T-CPT-10 | RA-06 | Adam + Emma | Emma connectée en fenêtre privée. Adam : Modifier Emma → Rôle « Enseignant » → Changer le rôle | Adam : « Le rôle a été changé. La personne a été déconnectée. ». Fenêtre d'Emma rechargée : retour à `/connexion` 🤖 📷 | | ☐ OK ☐ KO |
| T-CPT-11 | RA-06 | Emma | Se reconnecter | Elle a maintenant le rôle Enseignant (bloc « Demandes à valider » sur le tableau de bord). Adam la remet ensuite en Élève | | ☐ OK ☐ KO |
| T-CPT-12 | RA-05 | Adam | Forcer un changement de son propre rôle (F12 : modifier l'adresse d'un formulaire de rôle avec l'id d'Adam) | « Vous ne pouvez pas changer votre propre rôle. » 🤖 | | ☐ OK ☐ KO |
| T-CPT-13 | RA-05 | Adam | Même chose avec le bouton Désactiver | « Vous ne pouvez pas désactiver votre propre compte. » 🤖 | | ☐ OK ☐ KO |
| T-CPT-14 | 5.6 | Gaëlle puis Adam | Gaëlle valide et sort le prêt n° 1 d'Emma. Adam essaie de désactiver Emma | « Ce compte a encore du matériel non rendu : il ne peut pas être désactivé. » | | ☐ OK ☐ KO |
| T-CPT-15 | RA-06 | Adam + Bruno | Bruno connecté en fenêtre privée (prêt n° 2 en attente). Adam désactive Bruno (confirmer) | « Le compte de Bruno Eleve a été désactivé. ». Fenêtre de Bruno rechargée : `/connexion`. Prêt n° 2 : « Annulé » | | ☐ OK ☐ KO |
| T-CPT-16 | 5.7 | Bruno | Se reconnecter | « Identifiants incorrects. » | | ☐ OK ☐ KO |
| T-CPT-17 | BF-04 | Adam | Réactiver Bruno | « … a été réactivé. », Bruno peut se reconnecter | | ☐ OK ☐ KO |
| T-CPT-18 | RA-05 | Adam | Passer Gaëlle en Administrateur. Gaëlle (reconnectée) retire le rôle d'Adam | Accepté (il reste un administrateur actif : Gaëlle). La session d'Adam est fermée. Gaëlle lui rend ensuite son rôle | | ☐ OK ☐ KO |
| T-CPT-19 | Journal | Adam | Journal, filtre « Rôle changé » | Lignes du type « eleve@lycee.test : Élève → Enseignant » (qui, ancien rôle, nouveau rôle) | | ☐ OK ☐ KO |

---

## 11. Isolation entre établissements (multi-établissement)

**Préparation** : `php artisan migrate:fresh --seed`. Les numéros utilisés ci-dessous sont ceux du § 1.3 (Léa = compte n° 6, Lenovo = matériel n° 7, prêt de Léa = n° 4, classe « 1re STMG 2 » du lycée B = n° 3, rôle super-administrateur = n° 5).

### 11.1 Chaque lycée ne voit que ses données

| ID | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-ETA-01 | Gaëlle (A) | Menu Matériel | 6 matériels. INV-PC-001 = **Dell Latitude**. Ni Lenovo, ni Optoma, ni Galaxy Tab 🤖 | | ☐ OK ☐ KO |
| T-ETA-02 | Inès (B) | Menu Matériel | 3 matériels. INV-PC-001 = **Lenovo ThinkPad** 📷 | | ☐ OK ☐ KO |
| T-ETA-03 | Gaëlle (A) | Menu Prêts, puis Tableau de bord | Prêts n° 1, 2, 3 seulement. Jamais les n° 4 et 5 (Léa, Hugo) 🤖 | | ☐ OK ☐ KO |
| T-ETA-04 | Adam (A) | Menu Comptes | Les 5 comptes du lycée A. Aucune adresse `@lycee-b.test`, pas de `superadmin@academie.test` 🤖 | | ☐ OK ☐ KO |
| T-ETA-05 | Basile (B) | Menu Comptes | Les 4 comptes du lycée B seulement | | ☐ OK ☐ KO |

### 11.2 Adresses tapées à la main (même réponse qu'une donnée inexistante)

| ID | Compte | Adresse tapée | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-ETA-06 | Gaëlle (A) | `/materiels/7/modifier` (Lenovo du lycée B), puis `/materiels/9999/modifier` | Les deux fois : « Page introuvable », code **404**. Impossible de savoir que le n° 7 existe 🤖 📷 | | ☐ OK ☐ KO |
| T-ETA-07 | Gaëlle (A) | `/prets/4` (prêt de Léa), puis `/prets/99999` | Les deux fois : « Accès refusé », code **403** (règle RA-03 : inexistant ou interdit → même 403) 🤖 | | ☐ OK ☐ KO |
| T-ETA-08 | Adam (A) | `/comptes/6/modifier` (Léa), puis `/comptes/9999/modifier` | Les deux fois : 404 🤖 | | ☐ OK ☐ KO |

### 11.3 Formulaires forgés (les filtres que le scope ne couvre pas)

| ID | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-ETA-09 | Emma (A) | Formulaire de demande de prêt : F12, changer la valeur d'une option du matériel en `7` (Lenovo du lycée B), envoyer | « Ce matériel ne peut pas être emprunté. », aucun prêt créé 🤖 | | ☐ OK ☐ KO |
| T-ETA-10 | Adam (A) | Modifier Emma : les cases « Classes » ne proposent que « 1re STMG 2 » et « 2nde 3 » du lycée A. F12 : changer la valeur d'une case en `3`, la cocher, enregistrer | « Une des classes choisies n'existe pas. » 🤖 | | ☐ OK ☐ KO |
| T-ETA-11 | Gaëlle (A) | Ajouter un matériel n° `INV-TAB-001` (existe dans le lycée B), puis un autre n° `INV-PC-001` | Le 1er est accepté. Le 2e : « Ce numéro d'inventaire est déjà utilisé dans votre établissement. » 🤖 | | ☐ OK ☐ KO |
| T-ETA-12 | Adam (A) | Modifier un compte : ouvrir le menu « Rôle ». Puis F12 : changer la valeur d'une option en `5`, envoyer | Pas de « Super-administrateur » dans le menu. Après envoi : « Ce rôle n'existe pas. » 🤖 📷 | | ☐ OK ☐ KO |

### 11.4 Journal et super-administrateur

| ID | Compte | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-ETA-13 | Fenêtre privée, puis Adam et Basile | Connexion avec `eleve@lycee-b.test` et un mauvais mot de passe. Adam ouvre le Journal, puis Basile | Adam (A) : la ligne **n'apparaît pas**. Basile (B) : « Connexion échouée », eleve@lycee-b.test 🤖 | | ☐ OK ☐ KO |
| T-ETA-14 | Sacha (super-admin) | Se connecter, puis Comptes, Matériel, Journal | Connexion OK. 10 comptes des 2 lycées, 9 matériels (les deux INV-PC-001), toutes les lignes du journal, y compris les e-mails inconnus 🤖 📷 | | ☐ OK ☐ KO |
| T-ETA-15 | Sacha (super-admin) | Modifier Léa (`/comptes/6/modifier`) | Le formulaire s'ouvre. Les classes proposées sont seulement celles du lycée B (une seule « 1re STMG 2 ») | | ☐ OK ☐ KO |

### 11.5 Contraintes en base (MariaDB)

| ID | Requête | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- |
| T-ETA-16 | `INSERT INTO materiels (etablissement_id, nom, categorie, numero_inventaire) VALUES (1, 'Doublon', 'Test', 'INV-PC-001');` | **Refusé** : *Duplicate entry '1-INV-PC-001'* (clé unique composite). Avec `etablissement_id = 2`, même refus ; le numéro existe déjà dans les deux lycées | | ☐ OK ☐ KO 📷 |
| T-ETA-17 | `DELETE FROM etablissements WHERE id = 2;` | **Refusé** : erreur de clé étrangère (le lycée B a encore des données) | | ☐ OK ☐ KO |

---

## 12. Sécurité (OWASP)

| ID | Exigence | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- | --- |
| T-SEC-01 | ES-06 (XSS) | Gaëlle : ajouter un matériel nommé `<script>alert('XSS')</script>` | **Aucune** fenêtre d'alerte. Le texte s'affiche tel quel dans le catalogue 📷 | | ☐ OK ☐ KO |
| T-SEC-02 | ES-06 (XSS) | Gaëlle : refuser un prêt avec le motif `<b>gras</b><img src=x onerror=alert(1)>` | Motif affiché comme du texte, rien n'est interprété | | ☐ OK ☐ KO |
| T-SEC-03 | ES-06 (XSS) | Connexion avec l'e-mail `"><script>alert(1)</script>@a.fr` (supprimer `type="email"`), puis Sacha (super-administrateur) ouvre le journal (un e-mail inconnu n'est visible que par lui) | Pas d'alerte, ni sur la page de connexion, ni dans le journal | | ☐ OK ☐ KO |
| T-SEC-04 | ES-05 (SQL) | Catalogue : rechercher `' OR '1'='1` | Aucune erreur, « Aucun matériel trouvé. » (le texte est cherché tel quel) 📷 | | ☐ OK ☐ KO |
| T-SEC-05 | ES-05 (SQL) | Connexion avec l'e-mail `admin@lycee.test' -- ` | « Identifiants incorrects. » (ou « n'est pas valide »), aucune erreur SQL | | ☐ OK ☐ KO |
| T-SEC-06 | ES-07 (CSRF) | F12 sur un formulaire (ex. Archiver) : supprimer le champ caché `_token`, envoyer | Page « Page expirée », code **419**, l'action n'est pas faite 📷 | | ☐ OK ☐ KO |
| T-SEC-07 | RA-02 | Emma : F12 sur le formulaire de demande, ajouter un champ `<input name="demandeur_id" value="2">`, envoyer | Le prêt est créé au nom d'**Emma**, pas de Bruno | | ☐ OK ☐ KO |
| T-SEC-08 | RA-03 (IDOR) | Voir T-PRE-05 et T-PRE-06 | — | | ☐ OK ☐ KO |
| T-SEC-09 | ES-08, ES-09 | Sur le serveur HTTPS (quand il existera) : vérifier le cadenas et l'attribut `Secure` du cookie | Cadenas présent, cookie `Secure` (**non testable en local**, HTTPS à préciser) | | ☐ OK ☐ KO ☐ Non testable |
| T-SEC-10 | ES-11 | Dans `.env` : `APP_DEBUG=false`, puis `php artisan config:clear`. Ouvrir `/prets/abc` et `/page-inconnue` | Pages d'erreur génériques en français, **aucune** trace technique (pas de nom de fichier, pas de ligne de code). Remettre `APP_DEBUG=true` après | | ☐ OK ☐ KO |
| T-SEC-11 | Section 6 | MariaDB : `SELECT COUNT(*) FROM sessions WHERE payload LIKE '%Eleve-Test-2026%';` après des connexions | `0` (sessions chiffrées, aucun mot de passe en clair) | | ☐ OK ☐ KO |
| T-SEC-12 | Audit | Analyse OWASP ZAP (*Automated Scan*) sur http://127.0.0.1:8000, connecté puis non connecté | Aucune alerte « High » d'injection SQL ou de XSS (critère d'acceptation, section 9). Joindre le rapport ZAP | | ☐ OK ☐ KO 📷 |
| T-SEC-13 | Affichage | Ouvrir l'application sur un téléphone (ou F12 → mode responsive) | Pages lisibles, menu qui passe à la ligne, formulaires utilisables | | ☐ OK ☐ KO |
| T-SEC-14 | Audit ZAP | F12 → *Réseau* → recharger → cliquer la 1re requête (le document) → *En-têtes de réponse* | Présents : `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `Content-Security-Policy: default-src 'self'; …`. **Absent** : `X-Powered-By`. **Absent en HTTP** : `Strict-Transport-Security` 🤖 📷 | | ☐ OK ☐ KO |
| T-SEC-15 | CSP | Gaëlle : catalogue → « Archiver » ; Emma : son prêt → « Annuler le prêt » ; Adam : « Désactiver » un compte. Chaque fois, cliquer **Annuler** dans la fenêtre de confirmation. Puis F12 → *Console* | La confirmation s'affiche, rien n'est fait après « Annuler ». **Aucune** erreur « Content Security Policy » dans la console |  | ☐ OK ☐ KO |

---

## 13. Installation et exploitation

| ID | Étapes | Résultat attendu | Résultat obtenu | Statut |
| --- | --- | --- | --- | --- |
| T-INS-01 | Suivre l'installation du README dans un autre dossier (`git clone`, `composer install`, `.env`, `migrate --seed`) | Application fonctionnelle, connexion possible avec les comptes de test | | ☐ OK ☐ KO |
| T-INS-02 | `mariadb -u materiel_app -p gestion_materiel < docs/base_gestion_materiel.sql`, puis `php artisan migrate:status` | Import sans erreur, toutes les migrations « Ran », connexion avec Adam possible | | ☐ OK ☐ KO |
| T-INS-03 | `APP_ENV=production` dans `.env`, puis `php artisan db:seed --class=UtilisateurTestSeeder` (Laravel demande une confirmation : répondre `yes`) | « Comptes de test refusés en production. », aucun compte créé. Remettre `APP_ENV=local` | | ☐ OK ☐ KO |
| T-INS-04 | `php artisan materiel:creer-admin` : saisir un prénom, un nom, un e-mail, choisir « Lycée Saint-Joseph » dans la liste, confirmer | Mot de passe temporaire affiché une fois. Connexion → changement obligatoire. Le nouvel administrateur ne voit que les comptes du lycée A. Ligne « Compte créé – créé en ligne de commande » dans le journal (visible par le super-administrateur : la commande n'a pas d'auteur connecté) 🤖 | | ☐ OK ☐ KO |
| T-INS-05 | Relancer la commande avec le même e-mail | « Cette adresse e-mail est déjà utilisée par un autre compte. » 🤖 | | ☐ OK ☐ KO |
| T-INS-06 | `php artisan materiel:creer-admin --super` : saisir un prénom, un nom, un e-mail, confirmer | **Aucune** question sur l'établissement. Question « Créer le compte SUPER-ADMINISTRATEUR … (accès à tous les établissements) ? ». Connexion → il voit les comptes des deux lycées 🤖 | | ☐ OK ☐ KO |

---

## 14. Fonctions non développées

Ces besoins du cahier des charges **ne sont pas testables** dans cette version :

| Besoin | Raison |
| --- | --- |
| BF-03 Réinitialisation du mot de passe par e-mail | Pas encore développé (l'administrateur peut créer un nouveau compte en attendant) |
| BF-23 E-mails de rappel (veille du retour, retards) | Pas encore développé (envoi d'e-mails et tâche planifiée à configurer) |
| `parametres.gerer` (réglages) | Pas d'écran de réglages : la limite RA-07 se règle dans `.env` |
| Demande de prêt d'un enseignant pour une classe | Pas encore développé |
| Création des classes, import CSV des comptes | Pas encore développé : les classes viennent des seeders |
| ES-09 HTTPS | Dépend de l'hébergement (à préciser) — voir T-SEC-09 |

**Limites connues**, sans incidence sur la sécurité :

- Le cookie `Secure` ne sera actif qu'une fois l'application en HTTPS (`SESSION_SECURE_COOKIE=true`).
- **Multi-établissement** :
  - aucun écran ni commande pour créer un établissement : un `INSERT` SQL pour l'instant (voir le README) ;
  - le super-administrateur ne peut pas encore créer de compte ou de matériel depuis le web : l'application refuse (erreur) plutôt que de créer une donnée sans établissement ;
  - les actions du super-administrateur et les lignes du journal d'avant le multi-établissement n'ont pas d'établissement : seul le super-administrateur les voit.

---

## 15. Synthèse

À remplir après la campagne de tests.

| Section | Nombre de tests | OK | KO | Non testables |
| --- | --- | --- | --- | --- |
| 2. Tests automatiques | 30 | | | |
| 3. Connexion et sessions | 17 | | | |
| 4. Mot de passe temporaire | 9 | | | |
| 5. Autorisations et erreurs | 14 | | | |
| 6. Inventaire | 16 | | | |
| 7. Prêts | 34 | | | |
| 8. Tableau de bord | 5 | | | |
| 9. Journal | 13 | | | |
| 10. Comptes | 19 | | | |
| 11. Isolation entre établissements | 17 | | | |
| 12. Sécurité | 15 | | | |
| 13. Installation | 6 | | | |
| **Total** | **195** | | | |

**Critères d'acceptation du cahier des charges (section 9)** :

| Critère | Tests concernés | Atteint ? |
| --- | --- | --- |
| Chaque besoin BF-xx démontrable en moins de 2 minutes | Sections 3 à 10 | ☐ Oui ☐ Non |
| Aucun accès non autorisé lors des tests de la section 5.7 | A-01 à A-13, sections 5 et 7.1 | ☐ Oui ☐ Non |
| Aucune donnée d'un autre établissement visible ou modifiable | A-20, A-22 à A-30, section 11 | ☐ Oui ☐ Non |
| Aucune faille d'injection SQL ou XSS trouvée avec OWASP ZAP | T-SEC-01 à T-SEC-05, T-SEC-12 | ☐ Oui ☐ Non |

| Date de la campagne | Testeur | Signature |
| --- | --- | --- |
| | Samuel Combari | |

---

## 16. Fiche d'anomalie

À copier pour chaque test **KO**.

| Champ | Valeur |
| --- | --- |
| **N° d'anomalie** | ANO-___ |
| **Test concerné** | T-___-___ |
| **Date** | |
| **Compte utilisé** | |
| **Étapes pour reproduire** | 1. … 2. … 3. … |
| **Résultat attendu** | |
| **Résultat obtenu** | |
| **Capture d'écran** | |
| **Gravité** | ☐ Bloquante ☐ Majeure ☐ Mineure |
| **Correction apportée** (commit) | |
| **Test rejoué le** | ☐ OK ☐ KO |
