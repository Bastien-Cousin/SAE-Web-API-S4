import type { Request, Response } from 'express'
import { prisma } from '../client.js'
import { convertBigInt } from '../utils/convertBigInt.js'

export const createPrediction = async (req: Request, res: Response) => {
    try {
        const { user_id, match_id, predicted_winner, confidence, games } = req.body

        // Validation
        if (!user_id || !match_id || !predicted_winner || !games || !Array.isArray(games)) {
            return res.status(400).json({ error: 'Données manquantes ou invalides' })
        }

        if (games.length !== 5) {
            return res.status(400).json({ error: 'Exactement 5 jeux requis' })
        }

        // Créer la prediction + les 5 games en une seule transaction
        const prediction = await prisma.prediction.create({
            data: {
                user_id,
                match_id: BigInt(match_id),
                predicted_winner,
                confidence: confidence || null,
                prediction_games: {
                    createMany: {
                        data: games.map((game: any) => ({
                            game_number: game.game_number,
                            map: game.map || null,
                            team1_score: game.team1_score || null,
                            team2_score: game.team2_score || null,
                            predicted_winner: game.predicted_winner || null,
                        })),
                    },
                },
            },
            include: {
                prediction_games: true,
            },
        })

        res.status(201).json(convertBigInt(prediction))
    } catch (error: any) {
        console.error(error)
        if (error.code === 'P2002') {
            return res.status(409).json({ error: 'Vous avez déjà une prédiction pour ce match' })
        }
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getPredictionById = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        const prediction = await prisma.prediction.findUnique({
            where: { id: parseInt(id) },
            include: {
                user: { select: { id: true, username: true } },
                match: { select: { pageid: true, objectname: true, date: true } },
                prediction_games: true,
            },
        })

        if (!prediction) return res.status(404).json({ error: 'Prédiction non trouvée' })
        res.json(convertBigInt(prediction))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getPredictionsByUser = async (req: Request, res: Response) => {
    try {
        const { userId } = req.params
        if (!userId || typeof userId !== 'string') return res.status(400).json({ error: 'ID utilisateur requis' })

        const predictions = await prisma.prediction.findMany({
            where: { user_id: parseInt(userId) },
            include: {
                match: { select: { pageid: true, objectname: true, date: true, team1_id: true, team2_id: true } },
                prediction_games: true,
            },
            orderBy: { created_at: 'desc' },
        })

        res.json(convertBigInt(predictions))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getPredictionsByMatch = async (req: Request, res: Response) => {
    try {
        const { matchId } = req.params
        if (!matchId || typeof matchId !== 'string') return res.status(400).json({ error: 'ID match requis' })

        const predictions = await prisma.prediction.findMany({
            where: { match_id: BigInt(matchId) },
            include: {
                user: { select: { id: true, username: true } },
                prediction_games: true,
            },
            orderBy: { created_at: 'desc' },
        })

        res.json(convertBigInt(predictions))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const updatePrediction = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        const { predicted_winner, confidence, games } = req.body

        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        // Mettre à jour la prédiction
        const prediction = await prisma.prediction.update({
            where: { id: parseInt(id) },
            data: {
                predicted_winner: predicted_winner || undefined,
                confidence: confidence !== undefined ? confidence : undefined,
            },
            include: {
                prediction_games: true,
            },
        })

        // Mettre à jour les games si fournis
        if (games && Array.isArray(games)) {
            for (const game of games) {
                await prisma.prediction_game.upsert({
                    where: {
                        prediction_id_game_number: {
                            prediction_id: parseInt(id),
                            game_number: game.game_number,
                        },
                    },
                    update: {
                        map: game.map || null,
                        team1_score: game.team1_score || null,
                        team2_score: game.team2_score || null,
                        predicted_winner: game.predicted_winner || null,
                    },
                    create: {
                        prediction_id: parseInt(id),
                        game_number: game.game_number,
                        map: game.map || null,
                        team1_score: game.team1_score || null,
                        team2_score: game.team2_score || null,
                        predicted_winner: game.predicted_winner || null,
                    },
                })
            }
        }

        const updatedPrediction = await prisma.prediction.findUnique({
            where: { id: parseInt(id) },
            include: {
                prediction_games: true,
            },
        })

        res.json(convertBigInt(updatedPrediction))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const deletePrediction = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        await prisma.prediction.delete({
            where: { id: parseInt(id) },
        })

        res.json({ message: 'Prédiction supprimée' })
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}
