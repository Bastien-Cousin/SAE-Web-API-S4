import type { Request, Response } from 'express'
import { prisma } from '../client.js'
import { convertBigInt } from '../utils/convertBigInt.js'

export const getAllTournaments = async (req: Request, res: Response) => {
    try {
        const skip = parseInt(req.query.skip as string) || 0
        const take = parseInt(req.query.take as string) || 20

        const tournaments = await prisma.tournament.findMany({
            skip,
            take,
            orderBy: { startdate: 'desc' },
        })

        const total = await prisma.tournament.count()

        res.json({
            data: convertBigInt(tournaments),
            pagination: { total, skip, take },
        })
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getTournamentById = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        const tournament = await prisma.tournament.findUnique({
            where: { pageid: BigInt(id) },
        })

        if (!tournament) return res.status(404).json({ error: 'Tournoi non trouvé' })
        res.json(convertBigInt(tournament))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const searchTournaments = async (req: Request, res: Response) => {
    try {
        let q = req.query.q
        if (!q || typeof q !== 'string') return res.status(400).json({ error: 'Paramètre q requis' })

        const tournaments = await prisma.tournament.findMany({
            where: {
                OR: [
                    { name: { contains: q, mode: 'insensitive' } },
                    { objectname: { contains: q, mode: 'insensitive' } },
                    { shortname: { contains: q, mode: 'insensitive' } },
                ],
            },
            take: 20,
        })

        res.json(convertBigInt(tournaments))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getTournamentsByYear = async (req: Request, res: Response) => {
    try {
        const { year } = req.params
        if (!year || typeof year !== 'string') return res.status(400).json({ error: 'Année requise' })

        const startOfYear = new Date(`${year}-01-01`)
        const endOfYear = new Date(`${year}-12-31`)

        const tournaments = await prisma.tournament.findMany({
            where: {
                AND: [
                    { startdate: { gte: startOfYear } },
                    { startdate: { lte: endOfYear } },
                ],
            },
            orderBy: { startdate: 'asc' },
        })

        res.json(convertBigInt(tournaments))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getTournamentStats = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        const tournament = await prisma.tournament.findUnique({
            where: { pageid: BigInt(id) },
        })

        if (!tournament) return res.status(404).json({ error: 'Tournoi non trouvé' })

        // Récupérer les statistiques du tournoi
        const matchCount = await prisma.match.count()
        const teamCount = await prisma.team.count()
        const playerCount = await prisma.player.count()

        res.json({
            ...convertBigInt(tournament),
            stats: {
                totalMatches: matchCount,
                totalTeams: teamCount,
                totalPlayers: playerCount,
            },
        })
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}
