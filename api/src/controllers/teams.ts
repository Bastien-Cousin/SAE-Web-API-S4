import type { Request, Response } from 'express'
import { prisma } from '../client.js'
import { convertBigInt } from '../utils/convertBigInt.js'

export const getAllTeams = async (req: Request, res: Response) => {
    try {
        const skip = parseInt(req.query.skip as string) || 0
        const take = parseInt(req.query.take as string) || 20

        const teams = await prisma.team.findMany({
            skip,
            take,
            include: {
                players: {
                    select: {
                        pageid: true,
                        name: true,
                        objectname: true,
                    },
                },
            },
            orderBy: { name: 'asc' },
        })

        const total = await prisma.team.count()

        res.json({
            data: convertBigInt(teams),
            pagination: { total, skip, take },
        })
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getTeamById = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        const team = await prisma.team.findUnique({
            where: { pageid: BigInt(id) },
            include: {
                players: true,
                matchesAsteam1: { take: 10 },
                matchesAsteam2: { take: 10 },
            },
        })

        if (!team) return res.status(404).json({ error: 'Équipe non trouvée' })
        res.json(team)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const searchTeams = async (req: Request, res: Response) => {
    try {
        let q = req.query.q
        if (!q || typeof q !== 'string') return res.status(400).json({ error: 'Paramètre q requis' })

        const teams = await prisma.team.findMany({
            where: {
                OR: [
                    { name: { contains: q, mode: 'insensitive' } },
                    { objectname: { contains: q, mode: 'insensitive' } },
                ],
            },
            take: 20,
        })

        res.json(teams)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}
