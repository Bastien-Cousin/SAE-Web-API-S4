import type { Request, Response } from 'express'
import { prisma } from '../client.js'
import { convertBigInt } from '../utils/convertBigInt.js'

export const getAllPlayers = async (req: Request, res: Response) => {
    try {
        const skip = parseInt(req.query.skip as string) || 0;
        const take = parseInt(req.query.take as string) || 20;

        const players = await prisma.player.findMany({
            skip,
            take,
            include: {
                team: {
                    select: {
                        pageid: true,
                        name: true,
                        objectname: true,
                    },
                },
            },
            orderBy: { name: 'asc' },
        });

        const total = await prisma.player.count();

        res.json({
            data: convertBigInt(players),
            pagination: { total, skip, take },
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Erreur serveur' });
    }
};

export const getPlayerById = async (req: Request, res: Response) => {
    try {
        const { id } = req.params
        if (!id || typeof id !== 'string') return res.status(400).json({ error: 'ID requis' })

        const player = await prisma.player.findUnique({
            where: { pageid: BigInt(id) },
            include: {
                team: true,
                matchplayers: {
                    include: {
                        match: true,
                    },
                    take: 20,
                },
            },
        })

        if (!player) return res.status(404).json({ error: 'Joueur non trouvé' })
        res.json(convertBigInt(player));
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const getPlayersByTeam = async (req: Request, res: Response) => {
    try {
        const { teamId } = req.params
        if (!teamId || typeof teamId !== 'string') return res.status(400).json({ error: 'ID équipe requis' })

        const players = await prisma.player.findMany({
            where: { team_id: BigInt(teamId) },
            orderBy: { name: 'asc' },
        })

        res.json(players)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const searchPlayers = async (req: Request, res: Response) => {
    try {
        let q = req.query.q
        if (!q || typeof q !== 'string') return res.status(400).json({ error: 'Paramètre q requis' })

        const players = await prisma.player.findMany({
            where: {
                OR: [
                    { name: { contains: q, mode: 'insensitive' } },
                    { objectname: { contains: q, mode: 'insensitive' } },
                    { localizedname: { contains: q, mode: 'insensitive' } },
                ],
            },
            take: 20,
        })

        res.json(players)
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}
