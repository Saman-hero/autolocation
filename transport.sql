-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 08 mai 2026 à 17:39
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `transport`
--

-- --------------------------------------------------------

--
-- Structure de la table `chauffeurs`
--

CREATE TABLE `chauffeurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `matricule` varchar(50) DEFAULT NULL,
  `cine` varchar(50) DEFAULT NULL,
  `statut` enum('congé','en mission','disponible') DEFAULT 'disponible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `chauffeurs`
--

INSERT INTO `chauffeurs` (`id`, `nom`, `prenom`, `telephone`, `adresse`, `date_embauche`, `grade`, `matricule`, `cine`, `statut`, `created_at`) VALUES
(56, 'Yassine', 'Basbassi', '0666139836', 'Casablanca', '2024-01-10', 'sergent', 'CH-001', 'I548365A', 'en mission', '2026-05-06 22:38:18'),
(57, 'Ahmed', 'El Amrani', '0611223344', 'Rabat', '2023-06-15', 'caporal', 'CH-002', 'J112233B', 'en mission', '2026-05-06 22:38:18'),
(58, 'Omar', 'Zahidi', '0677889900', 'Marrakech', '2022-09-20', 'lieutenant', 'CH-003', 'K998877C', 'disponible', '2026-05-06 22:38:18'),
(59, 'Khalid', 'Nouri', '0655443322', 'Agadir', '2021-03-05', 'sergent', 'CH-004', 'L445566D', 'disponible', '2026-05-06 22:38:18'),
(60, 'Mehdi', 'Bennani', '0622334455', 'Fès', '2025-02-12', 'caporal', 'CH-005', 'M778899E', 'disponible', '2026-05-06 22:38:18');

-- --------------------------------------------------------

--
-- Structure de la table `missions`
--

CREATE TABLE `missions` (
  `id` int(11) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `missions`
--

INSERT INTO `missions` (`id`, `reference`, `description`, `statut`, `created_at`) VALUES
(12, '54787/qs/494', 'mission N1', 'en cours', '2026-05-06 22:40:15');

-- --------------------------------------------------------

--
-- Structure de la table `mission_affectations`
--

CREATE TABLE `mission_affectations` (
  `id` int(11) NOT NULL,
  `mission_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `chauffeur_id` int(11) DEFAULT NULL,
  `role` enum('chauffeur','chef') DEFAULT 'chauffeur',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `mission_affectations`
--

INSERT INTO `mission_affectations` (`id`, `mission_id`, `vehicle_id`, `chauffeur_id`, `role`, `created_at`) VALUES
(1, 12, 14, 56, 'chauffeur', '2026-05-06 22:40:15'),
(2, 12, 14, 57, 'chef', '2026-05-06 22:40:15');

-- --------------------------------------------------------

--
-- Structure de la table `mission_team`
--

CREATE TABLE `mission_team` (
  `id` int(11) NOT NULL,
  `mission_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `chauffeur_id` int(11) DEFAULT NULL,
  `role` enum('chauffeur','chef') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `trajets`
--

CREATE TABLE `trajets` (
  `id` int(11) NOT NULL,
  `mission_id` int(11) DEFAULT NULL,
  `ville_depart` varchar(100) DEFAULT NULL,
  `ville_arrivee` varchar(100) DEFAULT NULL,
  `date_depart` datetime DEFAULT NULL,
  `date_arrivee` datetime DEFAULT NULL,
  `ordre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `trajets`
--

INSERT INTO `trajets` (`id`, `mission_id`, `ville_depart`, `ville_arrivee`, `date_depart`, `date_arrivee`, `ordre`) VALUES
(9, 12, 'rabat', 'beni mellal ', '2026-05-07 00:39:00', '2026-05-08 00:39:00', 1);

-- --------------------------------------------------------

--
-- Structure de la table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `numero` varchar(50) NOT NULL,
  `marque` varchar(100) DEFAULT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `type` enum('VIP','camion','transport personnel') NOT NULL,
  `annee` int(11) DEFAULT NULL,
  `kilometrage` int(11) DEFAULT 0,
  `statut` enum('disponible','maintenance','en mission') DEFAULT 'disponible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vehicles`
--

INSERT INTO `vehicles` (`id`, `numero`, `marque`, `modele`, `type`, `annee`, `kilometrage`, `statut`, `created_at`) VALUES
(14, 'VH-001', 'Toyota', 'Hilux', 'camion', 2020, 120000, 'en mission', '2026-05-06 22:38:38'),
(15, 'VH-002', 'Hyundai', 'H1', 'transport personnel', 2019, 90000, 'disponible', '2026-05-06 22:38:38'),
(16, 'VH-003', 'Mercedes', 'Sprinter', 'VIP', 2023, 30000, 'disponible', '2026-05-06 22:38:38'),
(17, 'VH-004', 'Ford', 'Transit', 'camion', 2018, 150000, 'disponible', '2026-05-06 22:38:38'),
(18, 'VH-005', 'Toyota', 'Land Cruiser', 'VIP', 2022, 50000, 'disponible', '2026-05-06 22:38:38');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `chauffeurs`
--
ALTER TABLE `chauffeurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cine` (`cine`);

--
-- Index pour la table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mission_affectations`
--
ALTER TABLE `mission_affectations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mission_team`
--
ALTER TABLE `mission_team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`);

--
-- Index pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`);

--
-- Index pour la table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `chauffeurs`
--
ALTER TABLE `chauffeurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `mission_affectations`
--
ALTER TABLE `mission_affectations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `mission_team`
--
ALTER TABLE `mission_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `trajets`
--
ALTER TABLE `trajets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `mission_team`
--
ALTER TABLE `mission_team`
  ADD CONSTRAINT `mission_team_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD CONSTRAINT `trajets_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
