import { describe, it, expect, vi } from 'vitest'

// Mock Prisma client before importing routes
vi.mock('../../src/client', () => {
    const mockPrisma = {
        prediction: {
            findMany: vi.fn(),
            findUnique: vi.fn(),
            create: vi.fn(),
            update: vi.fn(),
            delete: vi.fn(),
        },
        prediction_game: {
            upsert: vi.fn(),
        },
    }
    return { prisma: mockPrisma }
})

import predictionsRouter from '../../src/routes/predictions'

describe('Predictions Routes', () => {
    it('should export a valid Express router', () => {
        expect(predictionsRouter).toBeDefined()
        expect(typeof predictionsRouter).toBe('function')
    })

    it('should have router methods configured', () => {
        expect(predictionsRouter).toBeDefined()
        expect(typeof predictionsRouter.get).toBe('function')
        expect(typeof predictionsRouter.post).toBe('function')
        expect(typeof predictionsRouter.patch).toBe('function')
        expect(typeof predictionsRouter.delete).toBe('function')
    })
})
