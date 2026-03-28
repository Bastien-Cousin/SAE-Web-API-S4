import express from 'express'
import { createTeam, getAllTeams, getTeamById, searchTeams } from '../controllers/teams.js'
import { authenticateToken } from '../auth.middleware.js'

const router = express.Router()

router.get('/search', searchTeams)
router.post('/', authenticateToken, createTeam)
router.get('/:id', getTeamById)
router.get('/', getAllTeams)

export default router
