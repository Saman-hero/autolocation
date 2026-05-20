# AutoLocation

Système de gestion de flotte et de locations de véhicules développé en PHP/MySQL avec Bootstrap 5.

---

## Fonctionnalités

- **Locations** — Cycle de vie complet : création, démarrage, clôture, historique
- **Clients** — Gestion particuliers & entreprises, vérification permis, détection doublons
- **Véhicules** — Suivi flotte, catégories, kilométrage, statut en temps réel
- **Paiements** — Enregistrement multi-modes, suivi soldes, cautions
- **Maintenance** — Planification entretiens, alertes vidange
- **Sinistres** — Déclaration et suivi des incidents
- **Calendrier** — Vue calendrier des réservations (FullCalendar v6)
- **Contrat & Facture PDF** — Génération avec QR code intégré
- **Export CSV** — Listes clients et réservations
- **Journal d'audit** — Traçabilité complète des actions utilisateurs
- **Tableau de bord** — KPIs animés, graphiques revenus et utilisation flotte
- **Fiche état véhicule** — Rapport départ/retour (carburant, propreté, dommages)
- **Recherche AJAX** — Recherche en temps réel sur toutes les listes
- **Réinitialisation mot de passe** — Flux par token sécurisé
- **Emails** — Notifications automatiques via PHP mail()
- **Rôles** — Admin et Opérateur

---

## Technologies

| Technologie | Rôle |
|---|---|
| PHP 8+ | Back-end, logique métier |
| MySQL | Base de données relationnelle |
| Bootstrap 5.3 | Interface responsive |
| Chart.js | Graphiques dashboard |
| FullCalendar v6 | Calendrier réservations |
| qrcode.js | Génération QR code côté client |
| PDO | Accès base de données sécurisé |

---

## Structure

```
autolocation/
├── admin/              # Journal d'audit, setup DB
├── api/                # Endpoints AJAX (search, duplicate check, calendar…)
├── clients/            # Module clients (CRUD)
├── config/             # Connexion base de données + auth
├── cron/               # Scripts planifiés (rappels email)
├── etat-vehicule/      # Fiches état départ/retour
├── export/             # Export CSV / PDF listes
├── historique/         # Historique des locations
├── includes/           # Navbar, flash, audit, mailer
├── maintenance/        # Module maintenance
├── models/             # Modèles PDO (Client, Vehicle, Reservation, Paiement)
├── paiements/          # Module paiements
├── reservations/       # Module locations (CRUD + contrat + facture + calendrier)
├── sinistres/          # Module sinistres
├── users/              # Gestion utilisateurs (admin)
├── vehicles/           # Module véhicules (CRUD)
├── index.php           # Tableau de bord
├── login.php           # Authentification
├── forgot-password.php # Réinitialisation mot de passe
├── style.css           # Styles globaux + animations
└── location.sql        # Schéma complet + données de test
```

---

## Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/Saman-hero/autolocation.git
   cd autolocation
   ```

2. **Créer la base de données**
   ```bash
   mysql -u root -e "CREATE DATABASE location CHARACTER SET utf8mb4;"
   mysql -u root location < location.sql
   ```

3. **Configurer la connexion** dans `config/database.php`
   ```php
   private $host     = "localhost";
   private $db_name  = "location";
   private $username = "root";
   private $password = "";
   ```

4. **Déposer dans le répertoire web**
   ```
   htdocs/location/
   ```

5. **Initialiser les tables avancées** — Visiter une fois :
   ```
   http://localhost/location/admin/setup-db.php
   ```

6. **Accéder à l'application**
   ```
   http://localhost/location/
   ```

---

## Connexion par défaut

| Champ | Valeur |
|---|---|
| Identifiant | `admin` |
| Mot de passe | `password` |

---

## Prérequis

- PHP >= 8.0
- MySQL >= 5.7
- Apache (XAMPP / WAMP / Laragon)

---

## Auteur

**Saman-hero** — [GitHub](https://github.com/Saman-hero)

---

## Licence

Usage personnel et éducatif. Tous droits réservés © 2026 Saman-hero.
