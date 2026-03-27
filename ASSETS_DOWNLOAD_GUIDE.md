# 📥 Guide de Téléchargement des Assets

Ce guide explique comment télécharger et héberger localement les logos des équipes et les assets des tournois depuis Liquipedia.

## ⚠️ Pourquoi télécharger localement ?

Liquipedia n'autorise **pas le hotlinking** (accès direct aux images). Vous recevrez une erreur :
```
Sorry, hotlinking images is not allowed. liquipedia.net
```

La solution est de télécharger les images une fois et les héberger sur votre propre serveur.

---

## 📁 Structure des dossiers

```
api/
├── public/
│   ├── assets/
│   │   ├── teams/           ← Logos des équipes
│   │   │   ├── team_1_logo.png
│   │   │   ├── team_1_textless_logo.png
│   │   │   └── ...
│   │   └── tournaments/     ← Assets des tournois
│   │       ├── tournament_1_banner.png
│   │       ├── tournament_1_icon.png
│   │       └── ...
│   └── ...
└── ...
```

---

## 🚀 Utilisation

### 1️⃣ Télécharger les logos des équipes

```bash
docker compose exec api npm run download:team-logos
```

**Ce que ça fait :**
- Récupère tous les logos des équipes depuis la BDD
- Télécharge les images depuis Liquipedia
- Salut les fichiers localement dans `api/public/assets/teams/`
- Met à jour les URLs dans la BDD pour pointer vers les versions locales

**Exemple de résultat :**
```
🚀 Démarrage du téléchargement des logos des équipes...

📊 45 équipes trouvées

📥 Traitement: fnatic (ID: 12345)
✅ Téléchargé: team_12345_logo.png
✅ Mis à jour: /assets/teams/team_12345_logo.png
✅ Téléchargé: team_12345_textless_logo.png
✅ Mis à jour: /assets/teams/team_12345_textless_logo.png
...

📈 Résumé:
   Total téléchargés: 180/180
   Dossier: /app/public/assets/teams/
```

---

### 2️⃣ Télécharger les assets des tournois

```bash
docker compose exec api npm run download:tournament-assets
```

**Ce que ça fait :**
- Récupère tous les banners et icons des tournois
- Télécharge depuis Liquipedia
- Salut localement dans `api/public/assets/tournaments/`
- Met à jour les URLs en BDD

---

### 3️⃣ Télécharger TOUT (équipes + tournois)

```bash
docker compose exec api npm run download:all
```

---

### 4️⃣ Configuration complète (import + téléchargement)

Si c'est une nouvelle installation :
```bash
docker compose exec api npm run setup
```

Cela exécute :
1. Configuration BDD
2. Import des données (équipes, joueurs, matchs, tournois)
3. Téléchargement des assets

---

## 🔄 Accéder aux images dans l'API

Une fois téléchargées, les images sont accessibles via :

```
http://localhost:3070/assets/teams/team_12345_logo.png
http://localhost:3070/assets/tournaments/tournament_1000_banner.png
```

Les URLs sont **automatiquement mises à jour** en base de données.

---

## 💾 Serveur de fichiers statiques

Le serveur Express serve automatiquement les fichiers :

```typescript
app.use('/assets', express.static(path.join(__dirname, '..', 'public', 'assets')))
```

---

## 📊 Points clés

| Aspect | Détail |
|--------|--------|
| **Format** | PNG (auto-détecté depuis URL) |
| **Stockage** | `api/public/assets/` |
| **Mise en cache** | Fichiers existants ne sont pas retéléchargés |
| **BDD** | URLs mises à jour automatiquement |
| **Accessibilité** | Via `/assets/*` sur l'API |
| **Fair use** | Conformé aux conditions Liquipedia |

---

## ⚙️ Troubleshooting

### Erreur : "Permission denied"

Les dossiers peuvent ne pas être créés. Vérifiez :
```bash
docker compose exec api ls -la public/assets/
```

Si les dossiers manquent, les scripts les créeront.

### Erreur : "Cannot find module"

Assurez-vous que les scripts sont en TypeScript dans `src/scripts/` et compilé.

### Les images ne s'affichent pas

1. Vérifiez les logs du conteneur
2. Vérifiez que les fichiers existent : `docker compose exec api ls public/assets/teams/`
3. Testez l'URL directement : `http://localhost:3070/assets/teams/team_1_logo.png`

---

## 🎯 Prochaines étapes

- [ ] Télécharger les logos: `npm run download:team-logos`
- [ ] Télécharger les tournois: `npm run download:tournament-assets`
- [ ] Vérifier les URLs en BDD
- [ ] Tester l'affichage dans le frontend
- [ ] Ajouter les images au .gitignore (fichiers volumineux)

---

## 📄 Licensing & Fair Use

Les images sont utilisées sous les clauses **fair use** de Liquipedia.
Mentionnez la source si vous redistribuez les images.
