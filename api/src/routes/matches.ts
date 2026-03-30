import express from 'express'
import { createMatch, getAllMatches, getMatchById, getMatchesByTeam, getRecentMatches, searchMatches } from '../controllers/matches.js'
import { authenticateToken } from '../auth.middleware.js'

const router = express.Router()

router.get('/search', searchMatches)
router.post('/', authenticateToken, createMatch)
router.get('/recent', getRecentMatches)
router.get('/team/:teamId', getMatchesByTeam)
router.get('/:id', getMatchById)
router.get('/', getAllMatches)

export default router
