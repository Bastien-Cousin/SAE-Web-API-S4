import express from 'express'
import { createPlayer, getAllPlayers, getPlayerById, getPlayersByTeam, searchPlayers } from '../controllers/players.js'
import { authenticateToken } from '../auth.middleware.js'

const router = express.Router()

router.get('/search', searchPlayers)
router.post('/', authenticateToken, createPlayer)
router.get('/team/:teamId', getPlayersByTeam)
router.get('/:id', getPlayerById)
router.get('/', getAllPlayers)

export default router
