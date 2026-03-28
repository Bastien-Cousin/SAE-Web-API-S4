import express from 'express'
import { createTeam, getAllTeams, getTeamById, searchTeams } from '../controllers/teams.js'

const router = express.Router()

router.get('/search', searchTeams)
router.post('/', createTeam)
router.get('/:id', getTeamById)
router.get('/', getAllTeams)

export default router
