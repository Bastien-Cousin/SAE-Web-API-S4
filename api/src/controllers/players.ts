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
        const getFirstDefined = (keys: string[]): unknown => {
            for (const key of keys) {
                const value = req.body?.[key]
                if (value !== undefined) {
                    return value
                }
            }
            return undefined
        }

        const pseudoRaw = getFirstDefined(['pseudo'])
        const realNameRaw = getFirstDefined(['name', 'nom_reel'])
        const nationalityRaw = getFirstDefined(['nationality', 'nationalite'])
        const regionRaw = getFirstDefined(['region'])
        const statusRaw = getFirstDefined(['status', 'actif'])
        const teamIdRaw = getFirstDefined(['teamId', 'team_id'])
        const teamNameRaw = getFirstDefined(['teamName', 'equipe'])
        const earningsRaw = getFirstDefined(['earnings', 'gains'])
        const ageRaw = getFirstDefined(['age'])

        const pseudo = typeof pseudoRaw === 'string' ? pseudoRaw.trim() : ''
        const realName = typeof realNameRaw === 'string' ? realNameRaw.trim() : ''
        const nationality = typeof nationalityRaw === 'string' ? nationalityRaw.trim() : ''
        const region = typeof regionRaw === 'string' ? regionRaw.trim() : null
        const status = typeof statusRaw === 'string' && statusRaw.trim() !== '' ? statusRaw.trim() : 'actif'
        const teamName = typeof teamNameRaw === 'string' ? teamNameRaw.trim() : ''

        let roles: string[] = []
        const rolesRaw = getFirstDefined(['roles'])
        if (Array.isArray(rolesRaw)) {
            roles = rolesRaw
                .filter((role: unknown) => typeof role === 'string')
                .map((role: string) => role.trim())
                .filter((role: string) => role.length > 0)
                .slice(0, 2)
        } else {
            const role1 = getFirstDefined(['role_1', 'role1'])
            const role2 = getFirstDefined(['role_2', 'role2'])

            roles = [role1, role2]
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
            if (!/^\d+$/.test(`${teamIdRaw}`.trim())) {
                return res.status(400).json({ error: 'ID équipe invalide.' })
            }

            const parsedTeamId = BigInt(`${teamIdRaw}`.trim())
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
