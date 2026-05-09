# 🚌 Transport Management System

Une application web de gestion de flotte de transport développée en PHP, permettant la supervision complète des véhicules, des chauffeurs et des missions de transport.

---

## 📋 Description

**Transport Management System** est une solution web centralisée conçue pour simplifier et automatiser la gestion opérationnelle d'une organisation de transport. Elle permet aux administrateurs de suivre en temps réel l'ensemble des ressources : véhicules, chauffeurs, missions et historique des opérations.

---

## ✨ Fonctionnalités

- 🚗 **Gestion des véhicules** — Suivi et administration du parc automobile (ajout, modification, suppression)
- 👨‍✈️ **Gestion des chauffeurs** — Recensement et gestion du personnel de conduite
- 📋 **Gestion des missions** — Planification, assignation et suivi des trajets
- 📂 **Historique des opérations** — Traçabilité complète des activités passées
- ⚙️ **Configuration système** — Paramétrage centralisé de l'application

---

## 🛠️ Technologies utilisées

| Technologie | Rôle |
|-------------|------|
| PHP | Langage principal (back-end) |
| MySQL / SQL | Base de données relationnelle |
| CSS | Mise en forme et interface utilisateur |

---

## 📁 Structure du projet

```
transport/
├── chauffeurs/       # Module de gestion des chauffeurs
├── vehicles/         # Module de gestion des véhicules
├── missions/         # Module de gestion des missions
├── historique/       # Historique des opérations
├── models/           # Modèles de données
├── config/           # Fichiers de configuration
├── includes/         # Composants réutilisables (header, footer, etc.)
├── style.css         # Feuille de style principale
└── transport.sql     # Script de création de la base de données
```

---

## 🚀 Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/basbassi/transport.git
   cd transport
   ```

2. **Configurer la base de données**
   - Créer une base de données MySQL
   - Importer le fichier `transport.sql` :
     ```bash
     mysql -u root -p nom_de_la_base < transport.sql
     ```

3. **Configurer la connexion**
   - Modifier les paramètres de connexion dans le dossier `config/`

4. **Lancer l'application**
   - Déposer le projet dans le répertoire de votre serveur web (ex: `htdocs`, `www`)
   - Accéder à l'application via `http://localhost/transport`

---

## 📌 Prérequis

- PHP >= 7.4
- MySQL >= 5.7
- Serveur web Apache ou Nginx (ou XAMPP / WAMP pour le développement local)

---

## 👤 Auteur

**basbassi** — [GitHub](https://github.com/basbassi)

**Saman-hero** — [GitHub](https://github.com/Saman-hero)
---

## 📄 Licence

Ce projet est à usage personnel/éducatif. Tous droits réservés © 2024 basbassi & Saman-hero.
