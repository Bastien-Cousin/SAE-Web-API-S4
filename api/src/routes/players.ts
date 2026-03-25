import express from 'express'
import { getAllPlayers, getPlayerById, getPlayersByTeam, searchPlayers } from '../controllers/players.js'

const router = express.Router()

router.get('/search', searchPlayers)
router.get('/team/:teamId', getPlayersByTeam)
router.get('/:id', getPlayerById)
router.get('/', getAllPlayers)

export default router
