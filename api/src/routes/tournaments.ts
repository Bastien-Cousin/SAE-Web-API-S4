import express from 'express'
import {
    getAllTournaments,
    getTournamentById,
    searchTournaments,
    getTournamentsByYear,
    getTournamentStats,
} from '../controllers/tournaments.js'

const router = express.Router()

router.get('/search', searchTournaments)
router.get('/year/:year', getTournamentsByYear)
router.get('/stats/:id', getTournamentStats)
router.get('/:id', getTournamentById)
router.get('/', getAllTournaments)

export default router
