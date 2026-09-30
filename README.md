# 🎮 Plateforme e-sport Counter-Strike — Web & API

Application web full-stack consacrée à l'écosystème compétitif de **Counter-Strike**, permettant de consulter des informations sur les équipes, joueurs, matchs et tournois, ainsi que de réaliser des prédictions sur les rencontres à venir.

Le projet repose sur une architecture séparant une interface web en **PHP**, une **API REST Node.js / Express** et une base de données **PostgreSQL**, l'ensemble étant conteneurisé avec Docker.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white)
![Node.js](https://img.shields.io/badge/Node.js-339933?style=for-the-badge&logo=nodedotjs&logoColor=white)
![Express.js](https://img.shields.io/badge/Express.js-000000?style=for-the-badge&logo=express&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Prisma](https://img.shields.io/badge/Prisma-2D3748?style=for-the-badge&logo=prisma&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)

---

## 🌐 À propos du projet

L'application centralise différentes données issues de la scène compétitive **Counter-Strike / Counter-Strike 2**.

Elle permet notamment de consulter :

- les équipes professionnelles ;
- les joueurs ;
- les matchs passés et à venir ;
- les tournois ;
- différentes statistiques liées aux compétitions.

Les données sont stockées dans une base PostgreSQL et rendues accessibles au site web par l'intermédiaire d'une API REST.

Une partie du jeu de données peut également être importée automatiquement depuis **Liquipedia**.

---

## ✨ Fonctionnalités principales

### 👥 Équipes et joueurs

L'application permet de consulter les différentes équipes présentes dans la base ainsi que leurs joueurs.

Les informations disponibles comprennent notamment :

- le nom de l'équipe ;
- sa région ;
- son logo ;
- ses gains en compétition ;
- les joueurs qui lui sont associés ;
- la nationalité et différentes informations sur les joueurs.

### 🎯 Matchs

Les matchs peuvent être consultés avec différentes informations comme :

- les deux équipes participantes ;
- la date de la rencontre ;
- le résultat ;
- le vainqueur ;
- le tournoi associé ;
- le statut du match.

Il est également possible de consulter les matchs associés à une équipe particulière.

### 🏆 Tournois

L'application référence différents tournois de la scène Counter-Strike avec notamment :

- leur nom ;
- leurs dates ;
- leur bannière ;
- leur prize pool ;
- la version du jeu concernée.

### 🔮 Prédictions

Les utilisateurs peuvent réaliser des prédictions sur les matchs à venir.

Une prédiction peut contenir :

- l'équipe supposée gagnante ;
- un niveau de confiance ;
- des prédictions plus détaillées sur les différentes maps d'une rencontre ;
- les scores attendus ;
- la date de création de la prédiction.

Les prédictions sont associées à la fois au match concerné et à l'utilisateur qui les a créées.

---

## 📊 Logique de prédiction

Le projet utilise une approche statistique simple et explicable pour analyser les performances des équipes.

Plusieurs éléments peuvent être pris en compte :

- **historique global** — taux de victoire d'une équipe ;
- **confrontations directes** — résultats précédents entre deux équipes ;
- **performances par map** — taux de victoire sur une carte particulière ;
- **forme récente** — résultats obtenus lors des derniers matchs.

Ces informations permettent d'obtenir des indicateurs servant à comparer les équipes avant une rencontre.

L'objectif n'est pas d'utiliser un modèle de machine learning complexe, mais de construire une prédiction compréhensible à partir de statistiques existantes.

---

## 🔐 Authentification

L'application dispose également d'un système d'authentification permettant d'associer certaines actions à un utilisateur.

L'API utilise notamment une authentification basée sur **JWT**.

Une fois authentifié, un utilisateur peut accéder aux fonctionnalités nécessitant un compte, notamment la gestion de ses prédictions.

---

## 🔄 Import des données

Une partie des données utilisées par l'application est récupérée depuis **Liquipedia**.

Des scripts dédiés permettent notamment d'importer :

- les équipes ;
- les joueurs ;
- les matchs ;
- les tournois.

Le projet contient également des scripts permettant de récupérer et gérer différentes ressources associées, comme les logos d'équipes et les bannières de tournois.

---

## 🧱 Architecture

Le projet est divisé en trois services principaux :

```text
┌─────────────────────────────────────┐
│          Interface Web PHP          │
│                                     │
│ Équipes • Joueurs • Matchs          │
│ Tournois • Prédictions              │
└──────────────────┬──────────────────┘
                   │ HTTP
                   ▼
┌─────────────────────────────────────┐
│       API REST Node.js / Express    │
│                                     │
│ Routes • Contrôleurs • Auth JWT     │
│ Imports • Logique métier            │
└──────────────────┬──────────────────┘
                   │ Prisma ORM
                   ▼
┌─────────────────────────────────────┐
│          PostgreSQL                 │
│                                     │
│ Équipes • Joueurs • Matchs          │
│ Tournois • Utilisateurs             │
│ Prédictions                         │
└─────────────────────────────────────┘
```

Chaque partie est exécutée dans son propre conteneur Docker et communique avec les autres services à travers le réseau Docker.

---

## 🔌 API REST

Le backend est développé avec **Node.js, TypeScript et Express**.

L'API expose différents endpoints permettant notamment de travailler avec :

```text
/teams
/players
/matches
/tournaments
/predictions
```

Elle permet de consulter les données nécessaires au site et de réaliser les opérations liées aux différentes fonctionnalités de l'application.

Le projet utilise une organisation séparant :

- les **routes** ;
- les **contrôleurs** ;
- la logique d'accès aux données ;
- les scripts d'import ;
- l'authentification.

L'API dispose également d'une documentation **Swagger / OpenAPI**.

---

## 🗄️ Base de données

Les données sont stockées dans une base **PostgreSQL**.

L'accès à la base depuis l'API est réalisé avec **Prisma ORM**, qui permet de gérer :

- le schéma de données ;
- les relations entre les différentes entités ;
- les migrations ;
- l'accès aux données depuis TypeScript.

Parmi les principales entités du projet :

- `Teams`
- `Players`
- `Matches`
- `Tournaments`
- `Predictions`
- `PredictionGames`
- utilisateurs et données liées à l'authentification

---

## 🐳 Conteneurisation

L'ensemble du projet est conteneurisé avec **Docker et Docker Compose**.

Trois services principaux sont utilisés :

```text
web  → Serveur web PHP
api  → API Node.js / Express
db   → PostgreSQL
```

Cette architecture permet de lancer l'ensemble de l'environnement de développement de manière reproductible sans devoir installer séparément chaque service sur la machine.

---

## 🚀 Installation

### Prérequis

- Docker
- Docker Compose

### 1. Cloner le projet

```bash
git clone <url-du-repo>
cd <nom-du-projet>
```

### 2. Démarrer les conteneurs

```bash
docker compose up -d
```

### 3. Initialiser le projet

Lors du premier lancement :

```bash
docker compose exec api npm run setup
```

Cette commande initialise automatiquement différents éléments du projet :

- migrations de la base de données ;
- génération du client Prisma ;
- initialisation des données ;
- import des équipes ;
- import des joueurs ;
- import des matchs ;
- import des tournois ;
- récupération des ressources associées.

---

## ▶️ Accès aux services

Une fois les conteneurs lancés :

```text
Application web : http://localhost:8080
API             : http://localhost:3070
PostgreSQL      : localhost:5432
```

---

## 🛠️ Technologies utilisées

- **PHP** — interface web
- **HTML / CSS** — structure et mise en forme des pages
- **TypeScript** — développement de l'API
- **Node.js** — environnement d'exécution du backend
- **Express** — API REST
- **PostgreSQL** — base de données relationnelle
- **Prisma** — ORM et gestion des migrations
- **JWT** — authentification
- **Swagger / OpenAPI** — documentation de l'API
- **Liquipedia API** — récupération de données e-sport
- **Docker / Docker Compose** — conteneurisation des différents services

---

## 👥 Contexte du projet

Ce projet a été réalisé **en équipe de 4** durant le quatrième semestre de BUT Informatique.

Le développement s'est déroulé du **23 au 27 mars 2026**.

Il avait pour objectif de mettre en pratique la conception d'une application web complète reposant sur plusieurs services indépendants et communicants.

Ce projet m'a notamment permis de travailler sur :

- la conception d'une architecture full-stack ;
- le développement d'une API REST ;
- la communication entre un site web et une API ;
- la modélisation d'une base de données relationnelle ;
- l'utilisation d'un ORM avec Prisma ;
- la mise en place d'une authentification JWT ;
- l'import de données depuis une API externe ;
- la création de fonctionnalités basées sur des données statistiques ;
- la conteneurisation d'une application avec Docker ;
- la structuration d'un backend en routes et contrôleurs ;
- le travail en équipe avec Git et GitHub.

---

## 📅 Réalisation

**23 – 27 mars 2026** — Deuxième année de BUT Informatique, semestre 4.
