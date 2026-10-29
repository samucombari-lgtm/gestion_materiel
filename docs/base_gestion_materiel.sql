-- =============================================================================
-- Base de données « gestion_materiel » : structure + jeu de données de test
-- Projet : Gestion du matériel des lycées, multi-établissement (BTS SIO SLAM, épreuve E5)
-- =============================================================================
--
-- ⚠️ MÉTHODE RECOMMANDÉE : les migrations Laravel (php artisan migrate --seed).
--    Ce fichier est une ALTERNATIVE, fournie comme livrable (cahier des
--    charges, section 9). Il est généré avec mysqldump à partir de la base
--    créée par les migrations et les seeders ; il peut être en retard si les
--    migrations évoluent. Pour le régénérer, voir le README.
--
-- Contenu :
--   - les 18 tables (10 tables du projet + 8 tables techniques de Laravel) ;
--   - les 2 triggers qui rendent le journal de sécurité en lecture seule ;
--   - la table « migrations » remplie : Laravel considère ensuite que toutes les
--     migrations ont déjà été exécutées ;
--   - les données de test : 2 établissements (Lycée Saint-Joseph et le lycée
--     fictif Jean-Moulin), 5 rôles (dont super_admin), 11 permissions et la
--     matrice rôles / permissions, 10 comptes de test (5 du lycée A, 4 du
--     lycée B, 1 super-administrateur), 3 classes, 9 matériels (INV-PC-001 et
--     INV-VP-001 existent dans les deux lycées), 5 demandes de prêt
--     (du 05/11/2026 au 12/11/2026, dates fixées le jour de la génération) ;
--   - le journal de sécurité est créé VIDE ;
--   - les tables sessions, cache, jobs… sont créées VIDES (aucune donnée privée).
--
-- ⚠️ DÉVELOPPEMENT ET DÉMONSTRATION UNIQUEMENT : les mots de passe des comptes
--    de test sont publics (voir le README). Ne jamais importer ce fichier sur
--    le serveur de production.
--
-- Utilisation (la base et l'utilisateur materiel_app doivent déjà exister,
-- voir README, « Installation », étape 3) :
--
--     mariadb -u materiel_app -p gestion_materiel < docs/base_gestion_materiel.sql
--
-- ⚠️ Le fichier commence par DROP TABLE IF EXISTS : il REMPLACE les tables
--    existantes et efface leurs données.
--
-- Généré avec mysqldump (MariaDB 11.8), sans clause DEFINER sur les triggers :
-- ils peuvent être importés par n'importe quel utilisateur qui a le droit
-- TRIGGER sur la base.
-- =============================================================================

/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: gestion_materiel
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classe_utilisateur`
--

DROP TABLE IF EXISTS `classe_utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classe_utilisateur` (
  `classe_id` bigint(20) unsigned NOT NULL,
  `utilisateur_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`classe_id`,`utilisateur_id`),
  KEY `classe_utilisateur_utilisateur_id_foreign` (`utilisateur_id`),
  CONSTRAINT `classe_utilisateur_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classe_utilisateur_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classe_utilisateur`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `classe_utilisateur` WRITE;
/*!40000 ALTER TABLE `classe_utilisateur` DISABLE KEYS */;
INSERT INTO `classe_utilisateur` VALUES
(1,1),
(2,2),
(1,3),
(3,6),
(3,7);
/*!40000 ALTER TABLE `classe_utilisateur` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint(20) unsigned NOT NULL,
  `nom` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classes_etablissement_id_nom_unique` (`etablissement_id`,`nom`),
  CONSTRAINT `classes_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES
(1,1,'1re STMG 2'),
(2,1,'2nde 3'),
(3,2,'1re STMG 2');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `etablissements`
--

DROP TABLE IF EXISTS `etablissements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etablissements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `etablissements_nom_unique` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etablissements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etablissements` WRITE;
/*!40000 ALTER TABLE `etablissements` DISABLE KEYS */;
INSERT INTO `etablissements` VALUES
(2,'Lycée Jean-Moulin (test)'),
(1,'Lycée Saint-Joseph');
/*!40000 ALTER TABLE `etablissements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `journal`
--

DROP TABLE IF EXISTS `journal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint(20) unsigned DEFAULT NULL,
  `date_heure` datetime NOT NULL,
  `utilisateur_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `objet` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_utilisateur_id_foreign` (`utilisateur_id`),
  KEY `journal_date_heure_index` (`date_heure`),
  KEY `journal_action_index` (`action`),
  KEY `journal_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `journal_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`),
  CONSTRAINT `journal_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `journal` WRITE;
/*!40000 ALTER TABLE `journal` DISABLE KEYS */;
/*!40000 ALTER TABLE `journal` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER journal_interdire_modification
            BEFORE UPDATE ON journal
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le journal de securite est en lecture seule' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER journal_interdire_suppression
            BEFORE DELETE ON journal
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le journal de securite est en lecture seule' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `materiels`
--

DROP TABLE IF EXISTS `materiels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materiels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint(20) unsigned NOT NULL,
  `nom` varchar(150) NOT NULL,
  `categorie` varchar(100) NOT NULL,
  `numero_inventaire` varchar(50) NOT NULL,
  `etat` enum('disponible','prete','en_panne','en_reparation','archive') NOT NULL DEFAULT 'disponible',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materiels_etablissement_id_numero_inventaire_unique` (`etablissement_id`,`numero_inventaire`),
  CONSTRAINT `materiels_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materiels`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `materiels` WRITE;
/*!40000 ALTER TABLE `materiels` DISABLE KEYS */;
INSERT INTO `materiels` VALUES
(1,1,'Ordinateur portable Dell Latitude','Ordinateur portable','INV-PC-001','disponible','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(2,1,'Ordinateur portable HP ProBook','Ordinateur portable','INV-PC-002','disponible','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(3,1,'Vidéoprojecteur Epson EB-W51','Vidéoprojecteur','INV-VP-001','disponible','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(4,1,'Vidéoprojecteur BenQ MW560','Vidéoprojecteur','INV-VP-002','en_panne','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(5,1,'Caméra Canon Legria HF G70','Caméra','INV-CAM-001','en_reparation','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(6,1,'Câble HDMI 5 m','Câble','INV-CAB-001','disponible','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(7,2,'Ordinateur portable Lenovo ThinkPad','Ordinateur portable','INV-PC-001','disponible','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(8,2,'Vidéoprojecteur Optoma W400','Vidéoprojecteur','INV-VP-001','disponible','2026-10-29 19:33:00','2026-10-29 19:33:00'),
(9,2,'Tablette Samsung Galaxy Tab A9','Tablette','INV-TAB-001','disponible','2026-10-29 19:33:00','2026-10-29 19:33:00');
/*!40000 ALTER TABLE `materiels` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_10_19_100001_create_roles_table',1),
(5,'2026_10_19_100002_create_permissions_table',1),
(6,'2026_10_19_100003_create_role_permission_table',1),
(7,'2026_10_19_100004_create_utilisateurs_table',1),
(8,'2026_10_19_100005_create_classes_table',1),
(9,'2026_10_19_100006_create_classe_utilisateur_table',1),
(10,'2026_10_19_100007_create_materiels_table',1),
(11,'2026_10_19_100008_create_prets_table',1),
(12,'2026_10_19_100009_create_journal_table',1),
(13,'2026_10_19_100010_proteger_table_journal',1),
(14,'2026_10_29_100001_create_etablissements_table',1),
(15,'2026_10_29_100002_ajouter_etablissement_aux_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_nom_unique` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'materiel.voir','Consulter le catalogue'),
(2,'materiel.gerer','Ajouter, modifier, archiver un matériel'),
(3,'pret.demander','Créer une demande de prêt'),
(4,'pret.voir','Consulter des prêts'),
(5,'pret.valider','Accepter ou refuser une demande'),
(6,'pret.annuler','Annuler une demande pas encore sortie'),
(7,'pret.sortie_retour','Enregistrer la sortie et le retour du matériel'),
(8,'compte.gerer','Créer, modifier, désactiver des comptes'),
(9,'role.attribuer','Changer le rôle d\'un utilisateur'),
(10,'journal.voir','Consulter le journal de sécurité'),
(11,'parametres.gerer','Modifier les réglages (durée max d\'un prêt, etc.)');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `prets`
--

DROP TABLE IF EXISTS `prets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint(20) unsigned NOT NULL,
  `materiel_id` bigint(20) unsigned NOT NULL,
  `demandeur_id` bigint(20) unsigned NOT NULL,
  `validateur_id` bigint(20) unsigned DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin_prevue` date NOT NULL,
  `date_retour` date DEFAULT NULL,
  `statut` enum('en_attente','valide','refuse','sorti','rendu','annule') NOT NULL DEFAULT 'en_attente',
  `motif_refus` varchar(255) DEFAULT NULL,
  `etat_retour` enum('disponible','en_panne','en_reparation') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prets_materiel_id_foreign` (`materiel_id`),
  KEY `prets_demandeur_id_foreign` (`demandeur_id`),
  KEY `prets_validateur_id_foreign` (`validateur_id`),
  KEY `prets_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `prets_demandeur_id_foreign` FOREIGN KEY (`demandeur_id`) REFERENCES `utilisateurs` (`id`),
  CONSTRAINT `prets_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`),
  CONSTRAINT `prets_materiel_id_foreign` FOREIGN KEY (`materiel_id`) REFERENCES `materiels` (`id`),
  CONSTRAINT `prets_validateur_id_foreign` FOREIGN KEY (`validateur_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `prets` WRITE;
/*!40000 ALTER TABLE `prets` DISABLE KEYS */;
INSERT INTO `prets` VALUES
(1,1,1,1,NULL,'2026-11-05','2026-11-12',NULL,'en_attente',NULL,NULL,'2026-10-29 19:33:00','2026-10-29 19:33:00'),
(2,1,3,2,NULL,'2026-11-05','2026-11-12',NULL,'en_attente',NULL,NULL,'2026-10-29 19:33:00','2026-10-29 19:33:00'),
(3,1,2,3,NULL,'2026-11-05','2026-11-12',NULL,'en_attente',NULL,NULL,'2026-10-29 19:33:00','2026-10-29 19:33:00'),
(4,2,7,6,NULL,'2026-11-05','2026-11-12',NULL,'en_attente',NULL,NULL,'2026-10-29 19:33:00','2026-10-29 19:33:00'),
(5,2,8,7,NULL,'2026-11-05','2026-11-12',NULL,'en_attente',NULL,NULL,'2026-10-29 19:33:00','2026-10-29 19:33:00');
/*!40000 ALTER TABLE `prets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `role_permission`
--

DROP TABLE IF EXISTS `role_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permission` (
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `role_permission_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permission_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permission`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `role_permission` WRITE;
/*!40000 ALTER TABLE `role_permission` DISABLE KEYS */;
INSERT INTO `role_permission` VALUES
(1,1),
(2,1),
(3,1),
(4,1),
(5,1),
(3,2),
(1,3),
(2,3),
(3,3),
(1,4),
(2,4),
(3,4),
(2,5),
(3,5),
(1,6),
(2,6),
(3,6),
(3,7),
(4,8),
(5,8),
(4,9),
(5,9),
(4,10),
(5,10),
(4,11),
(5,11);
/*!40000 ALTER TABLE `role_permission` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_nom_unique` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(4,'administrateur'),
(1,'eleve'),
(2,'enseignant'),
(3,'gestionnaire'),
(5,'super_admin');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilisateurs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint(20) unsigned DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `echecs_connexion` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bloque_jusqua` datetime DEFAULT NULL,
  `doit_changer_mot_de_passe` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `utilisateurs_email_unique` (`email`),
  KEY `utilisateurs_role_id_foreign` (`role_id`),
  KEY `utilisateurs_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `utilisateurs_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`),
  CONSTRAINT `utilisateurs_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateurs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
INSERT INTO `utilisateurs` VALUES
(1,1,'Eleve','Emma','eleve@lycee.test','$2y$12$bx1NhMfFQ23pr5qjHIGRquDYZ54t7D3KrSDQDBIu6smnTUo1tJAjy',1,1,0,NULL,0,'2026-10-29 19:32:58','2026-10-29 19:32:58'),
(2,1,'Eleve','Bruno','eleve2@lycee.test','$2y$12$2s6yXmXdo74VdFSeba6s4e78gM04hfxh1SUdOEV/sgHSt29mO7d/C',1,1,0,NULL,0,'2026-10-29 19:32:58','2026-10-29 19:32:58'),
(3,1,'Enseignant','Eric','enseignant@lycee.test','$2y$12$PEQlW1fOohWy8l15luSyvuDujLV.5wk4CQEb8gMbW71Xrz/AUzdYi',2,1,0,NULL,0,'2026-10-29 19:32:58','2026-10-29 19:32:58'),
(4,1,'Gestionnaire','Gaelle','gestionnaire@lycee.test','$2y$12$Zpq63pvxALZy1iEKeoQusuqXJfWw91Xd8NlpISjs0IVqEX10aAzIu',3,1,0,NULL,0,'2026-10-29 19:32:59','2026-10-29 19:32:59'),
(5,1,'Admin','Adam','admin@lycee.test','$2y$12$FK6WzRK9FFmNDBDz9X8p1.GCRkN6qryPbcEhLyjypQap9BnqA/eDO',4,1,0,NULL,0,'2026-10-29 19:32:59','2026-10-29 19:32:59'),
(6,2,'Eleve','Lea','eleve@lycee-b.test','$2y$12$hk3aHciA1KyeupBHvcTIeOJWRpSz4KWOADEG8fL96zPLcf2FCKaoW',1,1,0,NULL,0,'2026-10-29 19:32:59','2026-10-29 19:32:59'),
(7,2,'Enseignant','Hugo','enseignant@lycee-b.test','$2y$12$s3WL.TiXmmH1eQxTXFi.Dev0VeI2mgUGtiS3ffzJm2fbfWqcXyOEu',2,1,0,NULL,0,'2026-10-29 19:32:59','2026-10-29 19:32:59'),
(8,2,'Gestionnaire','Ines','gestionnaire@lycee-b.test','$2y$12$nb5R5GfUiQ4WOZqKr4KEzu/OInkK5rGxf6AxYmP1IYjuaaMo1HNHO',3,1,0,NULL,0,'2026-10-29 19:32:59','2026-10-29 19:32:59'),
(9,2,'Admin','Basile','admin@lycee-b.test','$2y$12$0OUBa7cVZ/lIwU1cuxsffOuRztO59OCkDVqDHjDnUszQq33M9wtgq',4,1,0,NULL,0,'2026-10-29 19:33:00','2026-10-29 19:33:00'),
(10,NULL,'Superadmin','Sacha','superadmin@academie.test','$2y$12$JOE/5GX4R1tf9eGFUTqC5.TIRUcYor78ykhWX1HYELkG9T/bAp9h6',5,1,0,NULL,0,'2026-10-29 19:33:00','2026-10-29 19:33:00');
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed
