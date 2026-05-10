-- ============================================================
--  Gestion de Location de Voitures
--  Schéma complet — base de données : location
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLE : vehicles
-- ============================================================
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id`                    INT(11)        NOT NULL AUTO_INCREMENT,
  `numero`                VARCHAR(50)    NOT NULL,
  `immatriculation`       VARCHAR(20)    DEFAULT NULL,
  `marque`                VARCHAR(100)   DEFAULT NULL,
  `modele`                VARCHAR(100)   DEFAULT NULL,
  `annee`                 INT(11)        DEFAULT NULL,
  `couleur`               VARCHAR(50)    DEFAULT NULL,
  `nb_places`             INT(11)        DEFAULT 5,
  `categorie`             ENUM('économique','berline','SUV','premium','utilitaire') DEFAULT 'berline',
  `kilometrage`           INT(11)        DEFAULT 0,
  `statut`                ENUM('disponible','loué','maintenance','indisponible') DEFAULT 'disponible',
  `prix_jour`             DECIMAL(10,2)  DEFAULT 0.00,
  `caution`               DECIMAL(10,2)  DEFAULT 0.00,
  `type_vidange`          VARCHAR(100)   DEFAULT NULL,
  `intervalle_vidange`    INT(11)        DEFAULT 10000,
  `derniere_vidange_km`   INT(11)        DEFAULT NULL,
  `date_derniere_vidange` DATE           DEFAULT NULL,
  `created_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TABLE : clients
-- ============================================================
CREATE TABLE IF NOT EXISTS `clients` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `nom`               VARCHAR(100) NOT NULL,
  `prenom`            VARCHAR(100) NOT NULL,
  `email`             VARCHAR(150) DEFAULT NULL,
  `telephone`         VARCHAR(20)  DEFAULT NULL,
  `adresse`           TEXT         DEFAULT NULL,
  `cin`               VARCHAR(30)  DEFAULT NULL,
  `permis_numero`     VARCHAR(50)  DEFAULT NULL,
  `permis_categorie`  VARCHAR(20)  DEFAULT 'B',
  `permis_expiration` DATE         DEFAULT NULL,
  `type_client`       ENUM('particulier','entreprise') DEFAULT 'particulier',
  `entreprise`        VARCHAR(150) DEFAULT NULL,
  `statut`            ENUM('actif','suspendu','liste_noire') DEFAULT 'actif',
  `notes`             TEXT         DEFAULT NULL,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cin` (`cin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TABLE : users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `nom`        VARCHAR(100) DEFAULT NULL,
  `prenom`     VARCHAR(100) DEFAULT NULL,
  `username`   VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','operateur') DEFAULT 'operateur',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TABLE : reservations
-- ============================================================
CREATE TABLE IF NOT EXISTS `reservations` (
  `id`                   INT(11)       NOT NULL AUTO_INCREMENT,
  `reference`            VARCHAR(30)   DEFAULT NULL,
  `client_id`            INT(11)       NOT NULL,
  `vehicle_id`           INT(11)       NOT NULL,
  `statut`               ENUM('en attente','confirmée','en cours','terminée','annulée') DEFAULT 'en attente',
  `date_debut`           DATETIME      NOT NULL,
  `date_fin_prevue`      DATETIME      NOT NULL,
  `date_retour_effectif` DATETIME      DEFAULT NULL,
  `lieu_depart`          VARCHAR(150)  DEFAULT NULL,
  `lieu_retour`          VARCHAR(150)  DEFAULT NULL,
  `km_depart`            INT(11)       DEFAULT NULL,
  `km_retour`            INT(11)       DEFAULT NULL,
  `prix_jour`            DECIMAL(10,2) DEFAULT NULL,
  `nb_jours`             INT(11)       DEFAULT NULL,
  `caution`              DECIMAL(10,2) DEFAULT 0.00,
  `montant_total`        DECIMAL(10,2) DEFAULT NULL,
  `frais_extra`          DECIMAL(10,2) DEFAULT 0.00,
  `commentaire`          TEXT          DEFAULT NULL,
  `created_by`           INT(11)       DEFAULT NULL,
  `created_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `client_id` (`client_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `res_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`),
  CONSTRAINT `res_vehicle_fk` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`),
  CONSTRAINT `res_user_fk`    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TABLE : paiements
-- ============================================================
CREATE TABLE IF NOT EXISTS `paiements` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `reservation_id`        INT(11)       NOT NULL,
  `montant`               DECIMAL(10,2) NOT NULL,
  `type_paiement`         ENUM('espèces','carte bancaire','virement','chèque') DEFAULT 'espèces',
  `type`                  ENUM('acompte','solde','caution','remboursement','frais extra') DEFAULT 'solde',
  `reference_transaction` VARCHAR(100)  DEFAULT NULL,
  `date_paiement`         DATE          NOT NULL,
  `notes`                 TEXT          DEFAULT NULL,
  `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `pai_res_fk` FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TABLE : sinistres
-- ============================================================
CREATE TABLE IF NOT EXISTS `sinistres` (
  `id`               INT(11)       NOT NULL AUTO_INCREMENT,
  `reference`        VARCHAR(30)   DEFAULT NULL,
  `reservation_id`   INT(11)       DEFAULT NULL,
  `vehicle_id`       INT(11)       NOT NULL,
  `client_id`        INT(11)       DEFAULT NULL,
  `type`             ENUM('accident','dommage','vol','panne','autre') DEFAULT 'dommage',
  `description`      TEXT          DEFAULT NULL,
  `cout_reparation`  DECIMAL(10,2) DEFAULT NULL,
  `prise_en_charge`  ENUM('client','assurance','société') DEFAULT 'client',
  `date_sinistre`    DATE          DEFAULT NULL,
  `statut`           ENUM('ouvert','en cours','clôturé') DEFAULT 'ouvert',
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `sin_res_fk`    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE SET NULL,
  CONSTRAINT `sin_vehicle_fk` FOREIGN KEY (`vehicle_id`)    REFERENCES `vehicles`(`id`),
  CONSTRAINT `sin_client_fk`  FOREIGN KEY (`client_id`)     REFERENCES `clients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TABLE : maintenance
-- ============================================================
CREATE TABLE IF NOT EXISTS `maintenance` (
  `id`                       INT(11)       NOT NULL AUTO_INCREMENT,
  `vehicle_id`               INT(11)       NOT NULL,
  `type_maintenance`         VARCHAR(100)  DEFAULT NULL,
  `description`              TEXT          DEFAULT NULL,
  `date_maintenance`         DATE          DEFAULT NULL,
  `kilometrage_intervention` INT(11)       DEFAULT NULL,
  `cout`                     DECIMAL(10,2) DEFAULT NULL,
  `technicien`               VARCHAR(100)  DEFAULT NULL,
  `statut`                   ENUM('planifiée','en cours','terminée') DEFAULT 'planifiée',
  `created_at`               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `maint_vehicle_fk` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- DONNÉES DE TEST
-- ============================================================

-- Véhicules
INSERT INTO `vehicles` (`numero`, `immatriculation`, `marque`, `modele`, `annee`, `couleur`, `nb_places`, `categorie`, `kilometrage`, `statut`, `prix_jour`, `caution`, `intervalle_vidange`, `derniere_vidange_km`) VALUES
('VH-001', '12345-A-1',  'Dacia',   'Sandero',    2022, 'Blanc',   5, 'économique', 45000, 'disponible', 250.00, 3000.00, 10000, 40000),
('VH-002', '67890-B-2',  'Toyota',  'Corolla',    2023, 'Gris',    5, 'berline',    22000, 'disponible', 400.00, 5000.00, 10000, 15000),
('VH-003', '11223-C-3',  'Hyundai', 'Tucson',     2023, 'Noir',    5, 'SUV',        18000, 'disponible', 600.00, 7000.00, 10000, 12000),
('VH-004', '44556-D-4',  'Mercedes','Classe E',   2022, 'Argent',  5, 'premium',    35000, 'disponible', 900.00, 10000.00, 10000, 30000),
('VH-005', '77889-E-5',  'Renault', 'Master',     2021, 'Blanc',   9, 'utilitaire', 60000, 'maintenance', 500.00, 6000.00, 10000, 55000);

-- Clients
INSERT INTO `clients` (`nom`, `prenom`, `email`, `telephone`, `adresse`, `cin`, `permis_numero`, `permis_categorie`, `permis_expiration`, `type_client`, `statut`) VALUES
('Alami',    'Karim',   'k.alami@email.ma',   '0661234567', 'Casablanca', 'BE123456', 'P-00123', 'B', '2028-06-15', 'particulier', 'actif'),
('Benali',   'Fatima',  'f.benali@email.ma',  '0622334455', 'Rabat',      'JE456789', 'P-00456', 'B', '2027-03-20', 'particulier', 'actif'),
('TransMaro','',        'contact@transm.ma',  '0537001122', 'Casablanca', 'RC-55432', 'E-00789', 'C', '2029-01-01', 'entreprise',  'actif');

-- Utilisateur admin (mot de passe : admin123)
INSERT INTO `users` (`nom`, `prenom`, `username`, `password`, `role`) VALUES
('Admin', 'Système', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Réservation de test
INSERT INTO `reservations` (`reference`, `client_id`, `vehicle_id`, `statut`, `date_debut`, `date_fin_prevue`, `lieu_depart`, `lieu_retour`, `km_depart`, `prix_jour`, `nb_jours`, `caution`, `montant_total`, `created_by`) VALUES
('LOC-2026-001', 1, 1, 'en cours', '2026-05-08 09:00:00', '2026-05-12 09:00:00', 'Agence Casablanca', 'Agence Casablanca', 45000, 250.00, 4, 3000.00, 1000.00, 1);

COMMIT;
