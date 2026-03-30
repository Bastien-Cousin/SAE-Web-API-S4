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

export const createMatch = async (req: Request, res: Response) => {
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

        const team1IdRaw = getFirstDefined(['team1Id', 'team1_id'])
        const team2IdRaw = getFirstDefined(['team2Id', 'team2_id'])
        const tournamentRaw = getFirstDefined(['tournament'])
        const dateInput = getFirstDefined(['date', 'date_match'])
        const bestOfRaw = getFirstDefined(['bestof', 'bestOf'])
        const scoreTeam1Raw = getFirstDefined(['scoreTeam1', 'score_team1', 'team1Score'])
        const scoreTeam2Raw = getFirstDefined(['scoreTeam2', 'score_team2', 'team2Score'])
        const mapsRaw = getFirstDefined(['maps', 'match2games', 'maps_payload'])

        const tournament = typeof tournamentRaw === 'string' ? tournamentRaw.trim() : null
        const dateRaw = typeof dateInput === 'string' ? dateInput.trim() : ''

        if (!team1IdRaw || !team2IdRaw) {
            return res.status(400).json({ error: 'Les deux équipes sont requises.' })
        }

        const team1Id = BigInt(`${team1IdRaw}`)
        const team2Id = BigInt(`${team2IdRaw}`)

        if (team1Id === team2Id) {
            return res.status(400).json({ error: 'Les équipes doivent être différentes.' })
        }

        const [team1, team2] = await Promise.all([
            prisma.team.findUnique({ where: { pageid: team1Id }, select: { pageid: true, name: true } }),
            prisma.team.findUnique({ where: { pageid: team2Id }, select: { pageid: true, name: true } }),
        ])

        if (!team1 || !team2) {
            return res.status(400).json({ error: 'Une ou plusieurs équipes sont introuvables.' })
        }

        const lastMatch = await prisma.match.findFirst({
            select: { pageid: true },
            orderBy: { pageid: 'desc' },
        })

        const nextPageId = (lastMatch?.pageid ?? BigInt(0)) + BigInt(1)
        const objectname = `manual_match_${team1Id}_${team2Id}_${Date.now()}`

        const parsedDate = dateRaw ? new Date(dateRaw) : null
        const date = parsedDate && !Number.isNaN(parsedDate.getTime()) ? parsedDate : null

        const parsedBestOf = Number.parseInt(`${bestOfRaw ?? ''}`, 10)
        const bestof = [1, 3, 5].includes(parsedBestOf) ? parsedBestOf : null

        const parseOptionalScore = (value: unknown): number | null => {
            if (value === undefined || value === null || `${value}`.trim() === '') {
                return null
            }
            const parsed = Number.parseInt(`${value}`, 10)
            return Number.isNaN(parsed) || parsed < 0 ? null : parsed
        }

        const scoreTeam1 = parseOptionalScore(scoreTeam1Raw)
        const scoreTeam2 = parseOptionalScore(scoreTeam2Raw)

        const parsedMapsRaw = typeof mapsRaw === 'string'
            ? (() => {
                try {
                    const decoded = JSON.parse(mapsRaw)
                    return Array.isArray(decoded) ? decoded : []
                } catch {
                    return []
                }
            })()
            : mapsRaw

        const maps = Array.isArray(parsedMapsRaw)
            ? parsedMapsRaw
                .filter((game: unknown) => typeof game === 'object' && game !== null)
                .map((game: any) => {
                    const mapNameRaw = typeof game.map === 'string'
                        ? game.map
                        : (typeof game.name === 'string' ? game.name : (typeof game.mapName === 'string' ? game.mapName : ''))
                    const mapName = mapNameRaw.trim()

                    let mapScore1 = parseOptionalScore(
                        game.team1_score ?? game.team1Score ?? game.score_team1,
                    )
                    let mapScore2 = parseOptionalScore(
                        game.team2_score ?? game.team2Score ?? game.score_team2,
                    )

                    if ((mapScore1 === null || mapScore2 === null) && typeof game.score === 'string') {
                        const parsedScore = game.score.match(/^(\d+)\s*-\s*(\d+)$/)
                        if (parsedScore) {
                            mapScore1 = parseOptionalScore(parsedScore[1])
                            mapScore2 = parseOptionalScore(parsedScore[2])
                        }
                    }

                    if (!mapName || mapScore1 === null || mapScore2 === null) {
                        return null
                    }

                    return {
                        map: mapName,
                        score: `${mapScore1}-${mapScore2}`,
                    }
                })
                .filter((game: { map: string; score: string } | null) => game !== null)
            : []

        let computedScore1 = scoreTeam1
        let computedScore2 = scoreTeam2

        if (computedScore1 === null || computedScore2 === null) {
            let wins1 = 0
            let wins2 = 0

            for (const game of maps) {
                const [left, right] = game.score.split('-').map((value) => Number.parseInt(value, 10))
                if (left > right) {
                    wins1++
                } else if (right > left) {
                    wins2++
                }
            }

            if (wins1 > 0 || wins2 > 0) {
                computedScore1 = wins1
                computedScore2 = wins2
            }
        }

        const isFinished = computedScore1 !== null && computedScore2 !== null
        const winner = isFinished
            ? (computedScore1! > computedScore2! ? '1' : (computedScore2! > computedScore1! ? '2' : null))
            : null

        const match2opponents = isFinished
            ? [
                { name: team1.name ?? 'Team 1', score: computedScore1 },
                { name: team2.name ?? 'Team 2', score: computedScore2 },
            ]
            : null

        const match = await prisma.$transaction(async (tx) => {
            const created = await tx.match.create({
                data: {
                    pageid: nextPageId,
                    objectname,
                    team1_id: team1.pageid,
                    team2_id: team2.pageid,
                    date,
                    tournament,
                    bestof,
                    finished: isFinished,
                    status: isFinished ? 'finished' : 'upcoming',
                    winner,
                    match2games: maps.length > 0 ? maps : null,
                    match2opponents,
                },
                include: {
                    team1: { select: { pageid: true, name: true } },
                    team2: { select: { pageid: true, name: true } },
                },
            })

            await tx.matchteam.createMany({
                data: [
                    { match_id: nextPageId, team_id: team1.pageid },
                    { match_id: nextPageId, team_id: team2.pageid },
                ],
                skipDuplicates: true,
            })

            return created
        })

        return res.status(201).json(convertBigInt(match))
    } catch (error) {
        console.error(error)
        return res.status(500).json({ error: 'Erreur serveur' })
    }
}
