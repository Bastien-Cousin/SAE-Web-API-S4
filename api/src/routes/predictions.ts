import express from 'express'
import {
    createPrediction,
    getPredictionById,
    getPredictionsByUser,
    getPredictionsByMatch,
    updatePrediction,
    deletePrediction,
} from '../controllers/predictions.js'
import { authenticateToken } from '../auth.middleware.js'

const router = express.Router()

router.post('/', authenticateToken, createPrediction)
router.get('/:id', authenticateToken, getPredictionById)
router.get('/user/:userId', authenticateToken, getPredictionsByUser)
router.get('/match/:matchId', authenticateToken, getPredictionsByMatch)
router.patch('/:id', authenticateToken, updatePrediction)
router.delete('/:id', authenticateToken, deletePrediction)

export default router
