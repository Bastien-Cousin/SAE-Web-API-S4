import type { Request, Response } from 'express'
import { prisma } from '../client.js'
import { convertBigInt } from '../utils/convertBigInt.js'

export const getAllMatches = async (req: Request, res: Response) => {
    try {
        const skip = parseInt(req.query.skip as string) || 0
        const take = parseInt(req.query.take as string) || 20

        const matches = await prisma.match.findMany({
            skip,
            take,
            include: {
                team1: { select: { pageid: true, name: true, objectname: true } },
                team2: { select: { pageid: true, name: true, objectname: true } },
            },
            orderBy: { date: 'desc' },
        })

        const total = await prisma.match.count()

        res.json({
            data: convertBigInt(matches),
            pagination: { total, skip, take },
        })
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getMatchById = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        const match = await prisma.match.findUnique({
            where: { pageid: BigInt(id) },
            include: {
                team1: true,
                team2: true,
                matchplayers: {
                    include: { player: true },
                },
            },
        })

        if (!match) return res.status(404).json({ error: 'Match non trouvé' })
        res.json(match)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getMatchesByTeam = async (req: Request, res: Response) => {
    try {
        const { teamId } = req.params
        if (!teamId || typeof teamId !== 'string') return res.status(400).json({ error: 'ID équipe requis' })

        const team_id = BigInt(teamId)
        const matches = await prisma.match.findMany({
            where: {
                OR: [
                    { team1_id: team_id },
                    { team2_id: team_id },
                ],
            },
            include: {
                team1: { select: { pageid: true, name: true } },
                team2: { select: { pageid: true, name: true } },
            },
            orderBy: { date: 'desc' },
            take: 50,
        })

        res.json(matches)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getRecentMatches = async (req: Request, res: Response) => {
    try {
        const limit = parseInt(req.query.limit as string) || 20

        const matches = await prisma.match.findMany({
            where: { date: { not: null } },
            include: {
                team1: { select: { pageid: true, name: true } },
                team2: { select: { pageid: true, name: true } },
            },
            orderBy: { date: 'desc' },
            take: limit,
        })

        res.json(matches)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const searchMatches = async (req: Request, res: Response) => {
    try {
        let q = req.query.q
        if (!q || typeof q !== 'string') return res.status(400).json({ error: 'Paramètre q requis' })

        const matches = await prisma.match.findMany({
            where: {
                OR: [
                    { objectname: { contains: q, mode: 'insensitive' } },
                    { tournament: { contains: q, mode: 'insensitive' } },
                    { series: { contains: q, mode: 'insensitive' } },
                ],
            },
            include: {
                team1: { select: { pageid: true, name: true } },
                team2: { select: { pageid: true, name: true } },
            },
            orderBy: { date: 'desc' },
            take: 30,
        })

        res.json(matches)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}
