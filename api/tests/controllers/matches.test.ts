import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock MUST come before other imports
vi.mock('../../src/client', () => {
  const mockPrisma = {
    match: {
      findMany: vi.fn(),
      findUnique: vi.fn(),
      count: vi.fn(),
    },
  }
  return { prisma: mockPrisma }
})

import { Request, Response } from 'express'
import * as matchesController from '../../src/controllers/matches'
import { prisma } from '../../src/client'

const mockPrisma = prisma as any
describe('Matches Controller', () => {
    let mockRequest: Partial<Request>
    let mockResponse: Partial<Response>
    let responseData: any

    beforeEach(() => {
        vi.clearAllMocks()

        responseData = {}
        mockRequest = {
            query: {},
            params: {},
        }

        mockResponse = {
            json: vi.fn(function (this: any, data: any) {
                responseData = data
                return this
            }),
            status: vi.fn(function (this: any, code: number) {
                this.statusCode = code
                return this
            }),
            statusCode: 200,
        }
    })

    describe('getAllMatches', () => {
        it('should return matches with default pagination', async () => {
            const mockMatches = [
                {
                    pageid: BigInt(1),
                    pagename: 'Match1',
                    team1_id: BigInt(1),
                    team2_id: BigInt(2),
                    team1: { pageid: BigInt(1), name: 'Team 1', objectname: 'team_1' },
                    team2: { pageid: BigInt(2), name: 'Team 2', objectname: 'team_2' },
                },
            ]

            mockPrisma.match.findMany.mockResolvedValue(mockMatches)
            mockPrisma.match.count.mockResolvedValue(1)

            await matchesController.getAllMatches(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.match.findMany).toHaveBeenCalledWith({
                skip: 0,
                take: 20,
                include: {
                    team1: { select: { pageid: true, name: true, objectname: true } },
                    team2: { select: { pageid: true, name: true, objectname: true } },
                },
                orderBy: { date: 'desc' },
            })
            expect(mockResponse.json).toHaveBeenCalled()
            expect(responseData.pagination).toEqual({ total: 1, skip: 0, take: 20 })
        })

        it('should handle custom pagination parameters', async () => {
            mockRequest.query = { skip: '20', take: '50' }
            mockPrisma.match.findMany.mockResolvedValue([])
            mockPrisma.match.count.mockResolvedValue(100)

            await matchesController.getAllMatches(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.match.findMany).toHaveBeenCalledWith(
                expect.objectContaining({
                    skip: 20,
                    take: 50,
                })
            )
        })

        it('should handle database errors', async () => {
            mockPrisma.match.findMany.mockRejectedValue(new Error('Database error'))

            await matchesController.getAllMatches(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })

    describe('getMatchById', () => {
        it('should return match with details', async () => {
            const mockMatch = {
                pageid: BigInt(1),
                pagename: 'Match1',
                team1_id: BigInt(1),
                team2_id: BigInt(2),
                team1: { pageid: BigInt(1), name: 'Team 1' },
                team2: { pageid: BigInt(2), name: 'Team 2' },
                matchplayers: [],
            }

            mockRequest.params = { id: '1' }
            mockPrisma.match.findUnique.mockResolvedValue(mockMatch)

            await matchesController.getMatchById(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.match.findUnique).toHaveBeenCalledWith({
                where: { pageid: BigInt(1) },
                include: {
                    team1: true,
                    team2: true,
                    matchplayers: {
                        include: { player: true },
                    },
                },
            })
            expect(mockResponse.json).toHaveBeenCalled()
        })

        it('should return 400 if no id provided', async () => {
            mockRequest.params = {}

            await matchesController.getMatchById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
        })

        it('should return 404 if match not found', async () => {
            mockRequest.params = { id: '999' }
            mockPrisma.match.findUnique.mockResolvedValue(null)

            await matchesController.getMatchById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(404)
        })

        it('should handle errors gracefully', async () => {
            mockRequest.params = { id: '1' }
            mockPrisma.match.findUnique.mockRejectedValue(new Error('Database error'))

            await matchesController.getMatchById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })

    describe('getMatchesByTeam', () => {
        it('should return matches for a team', async () => {
            const mockMatches = [
                {
                    pageid: BigInt(1),
                    team1_id: BigInt(100),
                    team2_id: BigInt(200),
                    team1: { pageid: BigInt(100), name: 'Team 100' },
                    team2: { pageid: BigInt(200), name: 'Team 200' },
                },
            ]

            mockRequest.params = { teamId: '100' }
            mockPrisma.match.findMany.mockResolvedValue(mockMatches)

            await matchesController.getMatchesByTeam(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.match.findMany).toHaveBeenCalledWith({
                where: {
                    OR: [
                        { team1_id: BigInt(100) },
                        { team2_id: BigInt(100) },
                    ],
                },
                include: {
                    team1: { select: { pageid: true, name: true } },
                    team2: { select: { pageid: true, name: true } },
                },
                orderBy: { date: 'desc' },
                take: 50,
            })
            expect(mockResponse.json).toHaveBeenCalledWith(mockMatches)
        })

        it('should return 400 if no teamId provided', async () => {
            mockRequest.params = {}

            await matchesController.getMatchesByTeam(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
        })

        it('should handle errors gracefully', async () => {
            mockRequest.params = { teamId: '100' }
            mockPrisma.match.findMany.mockRejectedValue(new Error('Database error'))

            await matchesController.getMatchesByTeam(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })

    describe('getRecentMatches', () => {
        it('should return recent matches with default limit', async () => {
            const mockMatches = [
                {
                    pageid: BigInt(1),
                    date: new Date('2026-03-26'),
                    team1_id: BigInt(1),
                    team2_id: BigInt(2),
                    team1: { pageid: BigInt(1), name: 'Team 1' },
                    team2: { pageid: BigInt(2), name: 'Team 2' },
                },
            ]

            mockPrisma.match.findMany.mockResolvedValue(mockMatches)

            await matchesController.getRecentMatches(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.match.findMany).toHaveBeenCalledWith({
                where: { date: { not: null } },
                include: {
                    team1: { select: { pageid: true, name: true } },
                    team2: { select: { pageid: true, name: true } },
                },
                orderBy: { date: 'desc' },
                take: 20,
            })
            expect(mockResponse.json).toHaveBeenCalledWith(mockMatches)
        })

        it('should handle custom limit parameter', async () => {
            mockRequest.query = { limit: '50' }
            mockPrisma.match.findMany.mockResolvedValue([])

            await matchesController.getRecentMatches(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.match.findMany).toHaveBeenCalledWith(
                expect.objectContaining({
                    take: 50,
                })
            )
        })

        it('should handle database errors', async () => {
            mockPrisma.match.findMany.mockRejectedValue(new Error('Database error'))

            await matchesController.getRecentMatches(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })

    describe('searchMatches', () => {
        it('should search matches by query', async () => {
            const mockMatches = [
                {
                    pageid: BigInt(1),
                    objectname: 'finals',
                    tournament: 'ESL Pro League',
                    series: null,
                },
            ]

            mockRequest.query = { q: 'finals' }
            mockPrisma.match.findMany.mockResolvedValue(mockMatches)

            await matchesController.searchMatches(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.match.findMany).toHaveBeenCalledWith({
                where: {
                    OR: [
                        { objectname: { contains: 'finals', mode: 'insensitive' } },
                        { tournament: { contains: 'finals', mode: 'insensitive' } },
                        { series: { contains: 'finals', mode: 'insensitive' } },
                    ],
                },
                include: {
                    team1: { select: { pageid: true, name: true } },
                    team2: { select: { pageid: true, name: true } },
                },
                orderBy: { date: 'desc' },
                take: 30,
            })
            expect(mockResponse.json).toHaveBeenCalledWith(mockMatches)
        })

        it('should return 400 if no search query provided', async () => {
            mockRequest.query = {}

            await matchesController.searchMatches(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
            expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Paramètre q requis' })
        })

        it('should handle errors gracefully', async () => {
            mockRequest.query = { q: 'test' }
            mockPrisma.match.findMany.mockRejectedValue(new Error('Database error'))

            await matchesController.searchMatches(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })
})
