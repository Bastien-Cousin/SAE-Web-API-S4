import type { Request, Response } from 'express'
import { prisma } from '../client.js'
import { convertBigInt } from '../utils/convertBigInt.js'

const slugify = (value: string): string => {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
}

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

export const createPlayer = async (req: Request, res: Response) => {
    try {
        const pseudo = typeof req.body?.pseudo === 'string' ? req.body.pseudo.trim() : ''
        const realName = typeof req.body?.name === 'string' ? req.body.name.trim() : ''
        const nationality = typeof req.body?.nationality === 'string' ? req.body.nationality.trim() : ''
        const region = typeof req.body?.region === 'string' ? req.body.region.trim() : null
        const status = typeof req.body?.status === 'string' ? req.body.status.trim() : 'actif'
        const teamIdRaw = req.body?.teamId
        const teamName = typeof req.body?.teamName === 'string' ? req.body.teamName.trim() : ''
        const earningsRaw = req.body?.earnings
        const ageRaw = req.body?.age

        let roles: string[] = []
        if (Array.isArray(req.body?.roles)) {
            roles = req.body.roles
                .filter((role: unknown) => typeof role === 'string')
                .map((role: string) => role.trim())
                .filter((role: string) => role.length > 0)
                .slice(0, 2)
        }

        if (!pseudo || !realName || !nationality) {
            return res.status(400).json({ error: 'Pseudo, nom et nationalité sont requis.' })
        }

        const lastPlayer = await prisma.player.findFirst({
            select: { pageid: true },
            orderBy: { pageid: 'desc' },
        })
        const nextPageId = (lastPlayer?.pageid ?? BigInt(0)) + BigInt(1)

        const objectname = `player_${slugify(pseudo) || 'nouveau'}_${Date.now()}`

        let earnings: number | null = null
        if (earningsRaw !== undefined && earningsRaw !== null && `${earningsRaw}`.trim() !== '') {
            const parsed = Number.parseInt(`${earningsRaw}`, 10)
            if (!Number.isNaN(parsed) && parsed >= 0) {
                earnings = parsed
            }
        }

        let birthdate: Date | null = null
        const age = Number.parseInt(`${ageRaw ?? ''}`, 10)
        if (!Number.isNaN(age) && age > 0 && age < 120) {
            const now = new Date()
            birthdate = new Date(now.getFullYear() - age, 0, 1)
        }

        let teamId: bigint | null = null
        let resolvedTeamName: string | null = null

        if (teamIdRaw !== undefined && teamIdRaw !== null && `${teamIdRaw}`.trim() !== '') {
            const parsedTeamId = BigInt(`${teamIdRaw}`)
            const team = await prisma.team.findUnique({
                where: { pageid: parsedTeamId },
                select: { pageid: true, name: true },
            })
            if (!team) {
                return res.status(400).json({ error: 'Équipe introuvable.' })
            }
            teamId = team.pageid
            resolvedTeamName = team.name ?? null
        } else if (teamName) {
            const team = await prisma.team.findFirst({
                where: { name: { equals: teamName, mode: 'insensitive' } },
                select: { pageid: true, name: true },
            })
            if (team) {
                teamId = team.pageid
                resolvedTeamName = team.name ?? null
            }
        }

        const player = await prisma.player.create({
            data: {
                pageid: nextPageId,
                objectname,
                name: realName,
                localizedname: pseudo,
                nationality,
                region: region || null,
                status,
                type: roles.length > 0 ? roles.join(', ') : null,
                earnings,
                birthdate,
                team_id: teamId,
                teampagename: resolvedTeamName,
            },
        })

        return res.status(201).json(convertBigInt(player))
    } catch (error) {
        console.error(error)
        return res.status(500).json({ error: 'Erreur serveur' })
    }
}
