# 🚀 Guide Swagger - Installation et Utilisation

## ✅ Prérequis vérifiés

- ✅ `swagger-ui-express` - Déjà dans package.json v5.0.1
- ✅ `yamljs` - Déjà dans package.json v0.3.0

Pas besoin d'installer quoi que ce soit de plus !

## 📂 Structure des fichiers

```
api/
├── src/
│   ├── index.ts                  ← Configuration Swagger ajoutée
│   ├── routes/
│   ├── controllers/
│   └── client.ts
├── prisma/
│   └── schema.prisma
├── swagger.yaml                  ← NOUVEAU - Documentation OpenAPI
├── package.json
├── tsconfig.json
└── Dockerfile

/
└── API_DOCUMENTATION.md          ← NOUVEAU - Guide complet
```

## 🔧 Modifications apportées

### 1️⃣ Fichier `api/src/index.ts`

Ajouté :
```typescript
import { fileURLToPath } from 'url'
import * as path from 'path'
import swaggerUi from 'swagger-ui-express'
import YAML from 'yamljs'

// Charger la documentation Swagger
const swaggerDocument = YAML.load(path.join(__dirname, '..', 'swagger.yaml'))

// Route Swagger UI
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerDocument))

// Servir le fichier swagger.yaml
app.get('/swagger.yaml', (_req: Request, res: Response) => {
  res.setHeader('Content-Type', 'application/yaml')
  res.sendFile(path.join(__dirname, '..', 'swagger.yaml'))
})
```

### 2️⃣ Fichier `api/swagger.yaml`

Créé : Documentation OpenAPI v3.0.0 complète avec :
- ✅ Tous les endpoints (Teams, Players, Matches, Auth)
- ✅ Tous les modèles Prisma (Team, Player, Match, User)
- ✅ Paramètres, requêtes et réponses documentées
- ✅ Exemples de requêtes/réponses
- ✅ Codes HTTP et erreurs

### 3️⃣ Fichier `API_DOCUMENTATION.md`

Créé : Guide pour utiliser l'API avec exemples curl

## 🎯 Comment utiliser

### Démarrer l'API

**En local** :
```bash
cd api
npm install
npm run dev
```

**Avec Docker** :
```bash
docker-compose up -d
```

### Accéder à Swagger UI

Ouverture dans le navigateur :
```
http://localhost:3070/api-docs
```

Vous verrez l'interface interactive Swagger UI avec :
- 📋 Liste de tous les endpoints
- 🧪 Interface pour tester les endpoints
- 📖 Documentation détaillée de chaque route
- 📝 Exemples de requêtes/réponses

## 📡 Points de terminaison disponibles

### 🔍 Documentation
```
GET /              - Racine API (redirects vers /api-docs)
GET /api-docs      - Interface Swagger UI
GET /swagger.yaml  - Fichier OpenAPI en YAML
```

### 🏥 Santé
```
GET /api/health    - Vérifie API + DB
```

### 🔐 Authentification
```
POST /api/auth/sign-up
POST /api/auth/sign-in
```

### 👥 Teams
```
GET  /api/teams
GET  /api/teams/:id
GET  /api/teams/search
```

### 🎮 Players
```
GET  /api/players
GET  /api/players/:id
GET  /api/players/team/:teamId
GET  /api/players/search
```

### 🎯 Matches
```
GET  /api/matches
GET  /api/matches/:id
GET  /api/matches/recent
GET  /api/matches/team/:teamId
GET  /api/matches/search
```

## 🧪 Tester dans Swagger UI

1. **Ouvrir** : http://localhost:3070/api-docs

2. **Chercher une endpoint** : Par exemple "teams"

3. **Cliquer sur "Try it out"**

4. **Remplir les paramètres** (si nécessaire)

5. **Cliquer sur "Execute"**

6. **Voir la réponse** : Code HTTP + JSON

## 🔑 Authentification dans Swagger

Pour les endpoints qui nécessitent une authentification JWT :

1. D'abord, **s'inscrire** avec l'endpoint `/auth/sign-up`
2. Récupérer le `token` dans la réponse
3. Cliquer sur le bouton **"Authorize"** (en haut à droite de Swagger)
4. Coller : `Bearer TOKEN_ICI`
5. Cliquer sur "Authorize"

À partir de là, tous les endpoints incluront automatiquement le header JWT.

## 📊 Exemple de réponse API

```json
{
  "data": [
    {
      "pageid": "1",
      "objectname": "fnatic",
      "name": "Fnatic",
      "region": "eu",
      "logourl": "https://liquipedia.net/commons/images/fnatic_logo.png",
      "textlesslogourl": "https://liquipedia.net/commons/images/fnatic_textless_logo.png",
      "status": "Active",
      "earnings": "500000"
    }
  ],
  "pagination": {
    "skip": 0,
    "take": 20,
    "total": 15
  }
}
```

## 🔗 Fichiers à consulter

- **Documentation complète** : Lire `API_DOCUMENTATION.md`
- **Spécification OpenAPI** : Consulter `api/swagger.yaml`
- **Code de configuration** : Voir `api/src/index.ts`
- **Schéma BD** : Voir `api/prisma/schema.prisma`

## 🐛 Dépannage

### Swagger UI ne s'affiche pas ?

1. Vérifier que l'API tourne :
```bash
curl http://localhost:3070/api/health
```

2. Vérifier les logs Docker :
```bash
docker logs sae-web-but2-groupe7_api
```

3. Vérifier que `swagger.yaml` existe :
```bash
ls -la api/swagger.yaml
```

### Erreur "Cannot find module 'yamljs'" ?

```bash
cd api
npm install
```

### Les endpoints affichent des erreurs timeout ?

Vérifier que la base de données PostgreSQL est opérationnelle :
```bash
curl http://localhost:3070/api/health
```

## 📚 Pour plus d'informations

- [Swagger/OpenAPI Docs](https://swagger.io/)
- [swagger-ui-express NPM](https://www.npmjs.com/package/swagger-ui-express)
- [Express.js Docs](https://expressjs.com/)
- [Prisma Docs](https://www.prisma.io/docs/)

## ✨ Prochaines étapes (optionnelles)

1. **Générer automatiquement** la docs depuis le code avec decorators
2. **Ajouter des tags** pour filtrer les endpoints
3. **Documenter les schemes** de pagination
4. **Créer des exemples complets** pour chaque route

Enjoy ! 🚀
