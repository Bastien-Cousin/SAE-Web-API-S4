import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock MUST come before other imports
vi.mock('../../src/client', () => {
  const mockPrisma = {
    player: {
      findMany: vi.fn(),
      findUnique: vi.fn(),
      count: vi.fn(),
    },
  }
  return { prisma: mockPrisma }
})

import { Request, Response } from 'express'
import * as playersController from '../../src/controllers/players'
import { prisma } from '../../src/client'

const mockPrisma = prisma as any
describe('Players Controller', () => {
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

    describe('getAllPlayers', () => {
        it('should return players with default pagination', async () => {
            const mockPlayers = [
                {
                    pageid: BigInt(1),
                    name: 'Player One',
                    objectname: 'player_one',
                    team: null,
                },
            ]

            mockPrisma.player.findMany.mockResolvedValue(mockPlayers)
            mockPrisma.player.count.mockResolvedValue(1)

            await playersController.getAllPlayers(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.player.findMany).toHaveBeenCalledWith({
                skip: 0,
                take: 20,
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
            })
            expect(mockResponse.json).toHaveBeenCalled()
            expect(responseData.pagination).toEqual({ total: 1, skip: 0, take: 20 })
        })

        it('should handle custom pagination', async () => {
            mockRequest.query = { skip: '5', take: '10' }
            mockPrisma.player.findMany.mockResolvedValue([])
            mockPrisma.player.count.mockResolvedValue(50)

            await playersController.getAllPlayers(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.player.findMany).toHaveBeenCalledWith(
                expect.objectContaining({
                    skip: 5,
                    take: 10,
                })
            )
        })

        it('should handle database errors', async () => {
            mockPrisma.player.findMany.mockRejectedValue(new Error('Database error'))

            await playersController.getAllPlayers(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })

    describe('getPlayerById', () => {
        it('should return player with details', async () => {
            const mockPlayer = {
                pageid: BigInt(1),
                name: 'Player One',
                objectname: 'player_one',
                team: null,
                matchplayers: [],
            }

            mockRequest.params = { id: '1' }
            mockPrisma.player.findUnique.mockResolvedValue(mockPlayer)

            await playersController.getPlayerById(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.player.findUnique).toHaveBeenCalledWith({
                where: { pageid: BigInt(1) },
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
            expect(mockResponse.json).toHaveBeenCalled()
        })

        it('should return 400 if no id provided', async () => {
            mockRequest.params = {}

            await playersController.getPlayerById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
        })

        it('should return 404 if player not found', async () => {
            mockRequest.params = { id: '999' }
            mockPrisma.player.findUnique.mockResolvedValue(null)

            await playersController.getPlayerById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(404)
        })

        it('should handle errors gracefully', async () => {
            mockRequest.params = { id: '1' }
            mockPrisma.player.findUnique.mockRejectedValue(new Error('Database error'))

            await playersController.getPlayerById(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })

    describe('getPlayersByTeam', () => {
        it('should return players for a team', async () => {
            const mockPlayers = [
                {
                    pageid: BigInt(1),
                    name: 'Player One',
                    objectname: 'player_one',
                    team_id: BigInt(100),
                },
            ]

            mockRequest.params = { teamId: '100' }
            mockPrisma.player.findMany.mockResolvedValue(mockPlayers)

            await playersController.getPlayersByTeam(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.player.findMany).toHaveBeenCalledWith({
                where: { team_id: BigInt(100) },
                orderBy: { name: 'asc' },
            })
            expect(mockResponse.json).toHaveBeenCalledWith(mockPlayers)
        })

        it('should return 400 if no teamId provided', async () => {
            mockRequest.params = {}

            await playersController.getPlayersByTeam(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
        })

        it('should handle errors gracefully', async () => {
            mockRequest.params = { teamId: '100' }
            mockPrisma.player.findMany.mockRejectedValue(new Error('Database error'))

            await playersController.getPlayersByTeam(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })

    describe('searchPlayers', () => {
        it('should search players by name', async () => {
            const mockPlayers = [
                {
                    pageid: BigInt(1),
                    name: 'Player Name',
                    objectname: 'player_name',
                },
            ]

            mockRequest.query = { q: 'Player' }
            mockPrisma.player.findMany.mockResolvedValue(mockPlayers)

            await playersController.searchPlayers(mockRequest as Request, mockResponse as Response)

            expect(mockPrisma.player.findMany).toHaveBeenCalledWith({
                where: {
                    OR: [
                        { name: { contains: 'Player', mode: 'insensitive' } },
                        { objectname: { contains: 'Player', mode: 'insensitive' } },
                        { localizedname: { contains: 'Player', mode: 'insensitive' } },
                    ],
                },
                take: 20,
            })
            expect(mockResponse.json).toHaveBeenCalledWith(mockPlayers)
        })

        it('should return 400 if no search query provided', async () => {
            mockRequest.query = {}

            await playersController.searchPlayers(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(400)
        })

        it('should handle errors gracefully', async () => {
            mockRequest.query = { q: 'test' }
            mockPrisma.player.findMany.mockRejectedValue(new Error('Database error'))

            await playersController.searchPlayers(mockRequest as Request, mockResponse as Response)

            expect(mockResponse.status).toHaveBeenCalledWith(500)
        })
    })
})
