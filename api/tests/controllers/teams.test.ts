import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock MUST come before other imports
vi.mock('../../src/client', () => {
  const mockPrisma = {
    team: {
      findMany: vi.fn(),
      findUnique: vi.fn(),
      count: vi.fn(),
    },
  }
  return { prisma: mockPrisma }
})

import { Request, Response } from 'express'
import * as teamsController from '../../src/controllers/teams'
import { prisma } from '../../src/client'

const mockPrisma = prisma as any
describe('Teams Controller', () => {
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

    describe('getAllTeams', () => {
        it('should return teams with default pagination', async () => {
            const mockTeams = [
                {
                    pageid: BigInt(1),
                    pagename: 'Team1',
                    name: 'Team One',
                    objectname: 'team_one',
                    players: [],
                },
                {
                    pageid: BigInt(2),
                    pagename: 'Team2',
                    name: 'Team Two',
                    objectname: 'team_two',
                    players: [],
                },
            ]

            mockPrisma.team.findMany.mockResolvedValue(mockTeams)
            mockPrisma.team.count.mockResolvedValue(2)

            await teamsController.getAllTeams(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.team.findMany).toHaveBeenCalledWith({
                skip: 0,
                take: 20,
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
            expect(mockResponse.json).toHaveBeenCalled()
            expect(responseData.pagination).toEqual({ total: 2, skip: 0, take: 20 })
        })

        it('should handle custom skip and take parameters', async () => {
            mockRequest.query = { skip: '10', take: '50' }
            mockPrisma.team.findMany.mockResolvedValue([])
            mockPrisma.team.count.mockResolvedValue(100)

            await teamsController.getAllTeams(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.team.findMany).toHaveBeenCalledWith(
                expect.objectContaining({
                    skip: 10,
                    take: 50,
                })
            )
        })

        it('should handle errors gracefully', async () => {
            mockPrisma.team.findMany.mockRejectedValue(new Error('Database error'))

            await teamsController.getAllTeams(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
            expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Erreur serveur' })
        })
    })

    describe('getTeamById', () => {
        it('should return team with details', async () => {
            const mockTeam = {
                pageid: BigInt(1),
                pagename: 'Team1',
                name: 'Team One',
                objectname: 'team_one',
                players: [],
                matchesAsteam1: [],
                matchesAsteam2: [],
            }

            mockRequest.params = { id: '1' }
            mockPrisma.team.findUnique.mockResolvedValue(mockTeam)

            await teamsController.getTeamById(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.team.findUnique).toHaveBeenCalledWith({
                where: { pageid: BigInt(1) },
                include: {
                    players: true,
                    matchesAsteam1: { take: 10 },
                    matchesAsteam2: { take: 10 },
                },
            })
            expect(mockResponse.json).toHaveBeenCalledWith(mockTeam)
        })

        it('should return 400 error if no id provided', async () => {
            mockRequest.params = {}

            await teamsController.getTeamById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
            expect(mockResponse.json).toHaveBeenCalledWith({ error: 'ID requis' })
        })

        it('should return 404 if team not found', async () => {
            mockRequest.params = { id: '999' }
            mockPrisma.team.findUnique.mockResolvedValue(null)

            await teamsController.getTeamById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(404)
            expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Équipe non trouvée' })
        })

        it('should handle errors gracefully', async () => {
            mockRequest.params = { id: '1' }
            mockPrisma.team.findUnique.mockRejectedValue(new Error('Database error'))

            await teamsController.getTeamById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
            expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Erreur serveur' })
        })
    })

    describe('searchTeams', () => {
        it('should search teams by name', async () => {
            const mockTeams = [
                {
                    pageid: BigInt(1),
                    name: 'FaZe Clan',
                    objectname: 'faze_clan',
                },
            ]

            mockRequest.query = { q: 'FaZe' }
            mockPrisma.team.findMany.mockResolvedValue(mockTeams)

            await teamsController.searchTeams(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.team.findMany).toHaveBeenCalledWith({
                where: {
                    OR: [
                        { name: { contains: 'FaZe', mode: 'insensitive' } },
                        { objectname: { contains: 'FaZe', mode: 'insensitive' } },
                    ],
                },
                take: 20,
            })
            expect(mockResponse.json).toHaveBeenCalledWith(mockTeams)
        })

        it('should return 400 if no search query provided', async () => {
            mockRequest.query = {}

            await teamsController.searchTeams(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
            expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Paramètre q requis' })
        })

        it('should handle errors gracefully', async () => {
            mockRequest.query = { q: 'test' }
            mockPrisma.team.findMany.mockRejectedValue(new Error('Database error'))

            await teamsController.searchTeams(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
            expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Erreur serveur' })
        })
    })
})
