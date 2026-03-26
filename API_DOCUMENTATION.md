# Documentation Swagger - Esports API

## 📚 Accès à la documentation

Une fois l'API démarrée, la documentation Swagger est accessible à :

```
http://localhost:3070/api-docs
```

Ou en production :
```
https://sae-web-but2-groupe7.jrcan.dev/api-docs
```

## 🚀 Démarrage rapide

### En développement local :
```bash
cd api
npm install
npm run dev
```

L'API sera disponible sur : `http://localhost:3070`

### Avec Docker :
```bash
docker-compose up -d
```

L'API sera disponible sur : `http://localhost:3070`

## 📋 Routes principales

### 🏥 Health Check
```
GET /api/health
```
Vérifie que l'API et la base de données sont opérationnelles.

### 🔐 Authentification
```
POST /api/auth/sign-up        - Créer un compte
POST /api/auth/sign-in        - Se connecter
```

### 👥 Équipes
```
GET /api/teams                - Récupérer toutes les équipes (pagination)
GET /api/teams/:id            - Récupérer une équipe par ID
GET /api/teams/search         - Rechercher des équipes
```

### 🎮 Joueurs
```
GET /api/players              - Récupérer tous les joueurs (pagination)
GET /api/players/:id          - Récupérer un joueur par ID
GET /api/players/team/:teamId - Récupérer les joueurs d'une équipe
GET /api/players/search       - Rechercher des joueurs
```

### 🎯 Matchs
```
GET /api/matches              - Récupérer tous les matchs (pagination)
GET /api/matches/:id          - Récupérer un match par ID
GET /api/matches/recent       - Récupérer les matchs récents
GET /api/matches/team/:teamId - Récupérer les matchs d'une équipe
GET /api/matches/search       - Rechercher des matchs
```

## 🔑 Authentification JWT

1. **S'inscrire** :
```bash
curl -X POST http://localhost:3070/api/auth/sign-up \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "username": "username",
    "password": "password123"
  }'
```

2. **Se connecter** :
```bash
curl -X POST http://localhost:3070/api/auth/sign-in \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

3. **Utiliser le token reçu** dans les requêtes suivantes :
```bash
curl -H "Authorization: Bearer TOKEN_ICI" http://localhost:3070/api/teams
```

## 📊 Format des réponses

### Succès (200, 201)
```json
{
  "data": [...],
  "pagination": {
    "skip": 0,
    "take": 20,
    "total": 100
  }
}
```

### Erreur (4xx, 5xx)
```json
{
  "error": "Message d'erreur descriptif"
}
```

## 🎨 Modèles de données

### Team (Équipe)
```json
{
  "pageid": "1",
  "objectname": "fnatic",
  "name": "Fnatic",
  "region": "eu",
  "logourl": "https://...",
  "textlesslogourl": "https://...",
  "status": "Active",
  "earnings": "500000"
}
```

### Player (Joueur)
```json
{
  "pageid": "100",
  "objectname": "flusha",
  "name": "Freddy Wersén",
  "nationality": "se",
  "team_id": "1",
  "status": "Active",
  "earnings": 50000
}
```

### Match (Match)
```json
{
  "pageid": "200",
  "tournament": "ESL Pro League",
  "team1_id": "1",
  "team2_id": "2",
  "date": "2026-03-26T15:30:00Z",
  "status": "completed",
  "winner": "team1",
  "finished": true,
  "bestof": 3
}
```

### User (Utilisateur)
```json
{
  "id": 1,
  "username": "JohnDoe",
  "email": "john.doe@example.com"
}
```

## 🔧 Paramètres de pagination

La plupart des endpoints LIST supportent :
- `skip` : Nombre d'éléments à sauter (défaut: 0)
- `take` : Nombre d'éléments à retourner (défaut: 20, max: 100)

Exemple :
```
GET /api/teams?skip=20&take=10
```

## 🔗 Fichiers importants

- **Documentation OpenAPI** : `api/swagger.yaml`
- **Code principal** : `api/src/index.ts`
- **Routes** :
  - `api/src/routes/teams.ts`
  - `api/src/routes/players.ts`
  - `api/src/routes/matches.ts`
  - `api/src/auth.route.ts`
- **Controllers** : `api/src/controllers/`
- **Schéma DB** : `api/prisma/schema.prisma`

## 💡 Exemples pratiques

### Récupérer toutes les équipes de la région EU
```bash
curl "http://localhost:3070/api/teams/search?q=&region=eu"
```

### Chercher un joueur
```bash
curl "http://localhost:3070/api/players/search?q=flusha"
```

### Récupérer les matchs récents
```bash
curl "http://localhost:3070/api/matches/recent?limit=5"
```

### Récupérer les joueurs d'une équipe
```bash
curl "http://localhost:3070/api/players/team/1"
```

## 📝 Notes

- La documentation Swagger est générée automatiquement à partir du fichier `swagger.yaml`
- Les tokens JWT expirent après **7 jours**
- Les IDs BigInt doivent être traités comme des chaînes de caractères en frontend
- La pagination utilise skip/take (pas les numéros de page)

## 🐛 Support

En cas de problème, consultez les logs du conteneur API :
```bash
docker logs sae-web-but2-groupe7_api
```

Ou vérifiez le healthcheck :
```bash
curl http://localhost:3070/api/health
```
