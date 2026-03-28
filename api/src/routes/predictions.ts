import express from 'express'
import {
    createPrediction,
    getPredictionById,
    getPredictionsByUser,
    getPredictionsByMatch,
    updatePrediction,
    deletePrediction,
} from '../controllers/predictions.js'

const router = express.Router()

router.post('/', createPrediction)
router.get('/:id', getPredictionById)
router.get('/user/:userId', getPredictionsByUser)
router.get('/match/:matchId', getPredictionsByMatch)
router.patch('/:id', updatePrediction)
router.delete('/:id', deletePrediction)

export default router
