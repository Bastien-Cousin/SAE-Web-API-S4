import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'

// Mock modules BEFORE importing routes
vi.mock('../../src/client', () => ({
    prisma: {
        user: {
            findUnique: vi.fn(),
            create: vi.fn(),
        },
    },
}))

vi.mock('bcryptjs')
vi.mock('jsonwebtoken')

import { prisma as originalPrisma } from '../../src/client'
import bcrypt from 'bcryptjs'
import jwt from 'jsonwebtoken'

// Cast prisma to any to bypass TypeScript type checking for mocked properties
const prisma = originalPrisma as any

// Import auth route handlers after mocks
import { authRouter } from '../../src/auth.route'

describe('Auth Routes', () => {
    beforeEach(() => {
        vi.resetAllMocks()
        process.env.JWT_SECRET = 'test-secret-key'

        // Mock bcrypt functions
        vi.mocked(bcrypt.hash).mockImplementation(async (password: string) => {
            return `hashed_${password}`
        })

        vi.mocked(bcrypt.compare).mockImplementation(async (password: string, hashed: string) => {
            return hashed === `hashed_${password}`
        })

        // Mock jwt functions
        vi.mocked(jwt.sign).mockImplementation((payload: any) => {
            return `token_${JSON.stringify(payload)}`
        })
    })

    afterEach(() => {
        vi.resetAllMocks()
    })

    describe('POST /api/auth/sign-up', () => {
        it('should create user and return 201 with token', async () => {
            // Setup mocks
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce(null)
            vi.mocked(prisma.user.create).mockResolvedValueOnce({
                id: 1,
                username: 'testuser',
                email: 'test@example.com',
                password: 'hashed_password123',
                createdAt: new Date(),
                updatedAt: new Date(),
            } as any)

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    username: 'testuser',
                    password: 'password123',
                },
            }

            // Call the route by finding it
            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(201)
            expect(mockRes.json).toHaveBeenCalledWith(
                expect.objectContaining({
                    message: 'Inscription réussie',
                    token: expect.any(String),
                    user: expect.objectContaining({
                        id: 1,
                        username: 'testuser',
                        email: 'test@example.com',
                    }),
                })
            )
        })

        it('should return 400 when email is missing', async () => {
            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    username: 'testuser',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(400)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Données manquantes / invalides',
            })
        })

        it('should return 400 when username is missing', async () => {
            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(400)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Données manquantes / invalides',
            })
        })

        it('should return 400 when password is missing', async () => {
            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    username: 'testuser',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(400)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Données manquantes / invalides',
            })
        })

        it('should return 409 when email already exists', async () => {
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce({
                id: 1,
                username: 'existing',
                email: 'test@example.com',
                password: 'hashed',
                createdAt: new Date(),
                updatedAt: new Date(),
            } as any)

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    username: 'testuser',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(409)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Un compte existe déjà avec cet email.',
            })
        })

        it('should return 500 when user creation fails', async () => {
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce(null)
            vi.mocked(prisma.user.create).mockRejectedValueOnce(new Error('DB Error'))

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    username: 'testuser',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(500)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Erreur serveur',
            })
        })

        it('should return 500 on database error', async () => {
            vi.mocked(prisma.user.findUnique).mockRejectedValueOnce(new Error('DB Error'))

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    username: 'testuser',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(500)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Erreur serveur',
            })
        })
    })

    describe('POST /api/auth/sign-in', () => {
        it('should login user and return 200 with token', async () => {
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce({
                id: 1,
                username: 'testuser',
                email: 'test@example.com',
                password: 'hashed_password123',
                createdAt: new Date(),
                updatedAt: new Date(),
            } as any)

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-in'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(200)
            expect(mockRes.json).toHaveBeenCalledWith(
                expect.objectContaining({
                    message: 'Connexion réussie',
                    token: expect.any(String),
                    user: expect.objectContaining({
                        id: 1,
                        username: 'testuser',
                        email: 'test@example.com',
                    }),
                })
            )
        })

        it('should return 400 when email is missing', async () => {
            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-in'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(400)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Données manquantes / invalides',
            })
        })

        it('should return 400 when password is missing', async () => {
            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-in'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(400)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Données manquantes / invalides',
            })
        })

        it('should return 401 when user not found', async () => {
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce(null)

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'nonexistent@example.com',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-in'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(401)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Email ou mot de passe incorrect',
            })
        })

        it('should return 401 when password is incorrect', async () => {
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce({
                id: 1,
                username: 'testuser',
                email: 'test@example.com',
                password: 'hashed_wrongpassword',
                createdAt: new Date(),
                updatedAt: new Date(),
            } as any)

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-in'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(401)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Email ou mot de passe incorrect',
            })
        })

        it('should return 500 on database error', async () => {
            vi.mocked(prisma.user.findUnique).mockRejectedValueOnce(new Error('DB Error'))

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-in'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.status).toHaveBeenCalledWith(500)
            expect(mockRes.json).toHaveBeenCalledWith({
                error: 'Erreur serveur',
            })
        })
    })

    describe('Token Generation', () => {
        it('should generate valid JWT token on sign-up', async () => {
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce(null)
            vi.mocked(prisma.user.create).mockResolvedValueOnce({
                id: 5,
                username: 'newuser',
                email: 'new@test.com',
                password: 'hashedPassword',
                createdAt: new Date(),
                updatedAt: new Date(),
            } as any)

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'new@test.com',
                    username: 'newuser',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-up'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.json).toHaveBeenCalledWith(
                expect.objectContaining({
                    token: expect.stringContaining('token_'),
                })
            )
        })

        it('should generate valid JWT token on sign-in', async () => {
            vi.mocked(prisma.user.findUnique).mockResolvedValueOnce({
                id: 2,
                username: 'testuser',
                email: 'test@example.com',
                password: 'hashed_password123',
                createdAt: new Date(),
                updatedAt: new Date(),
            } as any)

            const mockRes = {
                status: vi.fn(function (this: any, code: number) {
                    this.statusCode = code
                    return this
                }),
                json: vi.fn(function (this: any, data: any) {
                    this.responseData = data
                    return this
                }),
            }

            const mockReq = {
                body: {
                    email: 'test@example.com',
                    password: 'password123',
                },
            }

            const handler = (authRouter.stack as any).find(
                (layer: any) => layer.route?.path === '/api/auth/sign-in'
            )?.route?.stack[0]?.handle

            if (handler) {
                await handler.call(mockRes, mockReq as any, mockRes as any)
            }

            expect(mockRes.json).toHaveBeenCalledWith(
                expect.objectContaining({
                    token: expect.stringContaining('token_'),
                })
            )
        })
    })
})
