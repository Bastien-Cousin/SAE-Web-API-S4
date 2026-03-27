import express from 'express'
import type { Request, Response } from 'express'
import { fileURLToPath } from 'url'
import * as path from 'path'
import swaggerUi from 'swagger-ui-express'
import YAML from 'yamljs'
import { prisma } from './client.js'
import teamsRouter from './routes/teams.js'
import playersRouter from './routes/players.js'
import matchesRouter from './routes/matches.js'
import { authRouter } from './auth.route.js'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Charger la documentation Swagger
const swaggerDocument = YAML.load(path.join(__dirname, '..', 'swagger.yaml'))

const app = express()
app.use(express.json())

// Route pour la documentation Swagger
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerDocument, {
  swaggerOptions: {
    urls: [
      {
        url: '/swagger.yaml',
        name: 'Esports API',
      },
    ],
  },
}))

// Servir le fichier swagger.yaml comme fichier statique
app.get('/swagger.yaml', (_req: Request, res: Response) => {
  res.setHeader('Content-Type', 'application/yaml')
  res.sendFile(path.join(__dirname, '..', 'swagger.yaml'))
})

// Route racine de l'API avec lien vers la documentation
app.get('/', (req: Request, res: Response) => {
  res.json({
    message: 'Esports API - SAE BUT2',
    documentation: 'http://localhost:3070/api-docs',
    version: '1.0.0',
  })
})

app.get('/api/health', async (_req: Request, res: Response) => {
  try {
    // Test simple de connexion PostgreSQL via Prisma
    await prisma.$queryRaw`SELECT 1`

    res.status(200).json({
      status: 'ok',
      api: 'up',
      database: 'up',
    })
  } catch (error) {
    console.error('Healthcheck failed:', error)

    res.status(503).json({
      status: 'error',
      api: 'up',
      database: 'down',
    })
  }
})

// Router d'authentification
app.use(authRouter);

// Routes API
app.use('/api/teams', teamsRouter)
app.use('/api/players', playersRouter)
app.use('/api/matches', matchesRouter)

// Root endpoint
app.get('/api', (req: Request, res: Response) => {
  res.json({
    message: 'Esports API',
    endpoints: {
      health: '/api/health',
      teams: '/api/teams',
      players: '/api/players',
      matches: '/api/matches',
    },
  })
})

const port = process.env.API_PORT || 3070
app.listen(port, () => {
  console.log(`API running on port ${port}`)
})