import type { Request, Response } from 'express'
import { prisma } from '../client.js'
import { convertBigInt } from '../utils/convertBigInt.js'
import { Prisma } from '@prisma/client'

const slugify = (value: string): string => {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
}

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
        res.json(convertBigInt(team))
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

        res.json(convertBigInt(teams))
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: 'Erreur serveur' })
    }
}

export const createTeam = async (req: Request, res: Response) => {
    try {
        const name = typeof req.body?.name === 'string' ? req.body.name.trim() : ''
        const region = typeof req.body?.region === 'string' ? req.body.region.trim() : null
        const logoUrl = typeof req.body?.logoUrl === 'string' ? req.body.logoUrl.trim() : null
        const ceo = typeof req.body?.ceo === 'string' ? req.body.ceo.trim() : null
        const createdAtRaw = typeof req.body?.createdate === 'string' ? req.body.createdate.trim() : ''
        const earningsRaw = req.body?.earnings

        if (!name) {
            return res.status(400).json({ error: 'Le nom de l\'équipe est requis.' })
        }

        const lastTeam = await prisma.team.findFirst({
            select: { pageid: true },
            orderBy: { pageid: 'desc' },
        })

        const nextPageId = (lastTeam?.pageid ?? BigInt(0)) + BigInt(1)
        const baseSlug = slugify(name) || `team_${Date.now()}`
        const objectname = `${baseSlug}_${Date.now()}`

        let earnings: bigint | null = null
        if (earningsRaw !== undefined && earningsRaw !== null && `${earningsRaw}`.trim() !== '') {
            const parsed = Number.parseInt(`${earningsRaw}`, 10)
            if (!Number.isNaN(parsed) && parsed >= 0) {
                earnings = BigInt(parsed)
            }
        }

        let createdate: Date | null = null
        if (createdAtRaw) {
            const parsedDate = new Date(createdAtRaw)
            if (!Number.isNaN(parsedDate.getTime())) {
                createdate = parsedDate
            }
        }

        const team = await prisma.team.create({
            data: {
                pageid: nextPageId,
                objectname,
                name,
                pagename: name,
                region: region || null,
                logourl: logoUrl || null,
                createdate,
                earnings,
                extradata: ceo ? { ceo } : Prisma.JsonNull,
            },
        })

        return res.status(201).json(convertBigInt(team))
    } catch (error) {
        console.error(error)
        return res.status(500).json({ error: 'Erreur serveur' })
    }
}
