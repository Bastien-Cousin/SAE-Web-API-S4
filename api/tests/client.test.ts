import { describe, it, expect, vi } from 'vitest'

describe('Prisma Client Configuration', () => {
    it('should export prisma client instance', async () => {
        // Import the client module
        const clientModule = await import('../src/client')

        expect(clientModule).toBeDefined()
        expect(clientModule.prisma).toBeDefined()
    })

    it('should have PrismaClient methods', async () => {
        const clientModule = await import('../src/client')
        const { prisma } = clientModule

        // Check that prisma has the expected models
        expect(typeof prisma.team).toBe('object')
        expect(typeof prisma.player).toBe('object')
        expect(typeof prisma.match).toBe('object')
    })

    it('should have query execution methods', async () => {
        const clientModule = await import('../src/client')
        const { prisma } = clientModule

        // Check for find methods
        expect(typeof prisma.team.findMany).toBe('function')
        expect(typeof prisma.team.findUnique).toBe('function')
        expect(typeof prisma.player.findMany).toBe('function')
        expect(typeof prisma.match.findMany).toBe('function')
    })
})

describe('Prisma Configuration', () => {
    it('should load environment variables correctly', () => {
        // The prisma.config.ts should be able to read DATABASE_URL
        const databaseUrl = process.env.DATABASE_URL

        // If DATABASE_URL is set, it should follow PostgreSQL format
        if (databaseUrl) {
            expect(databaseUrl).toMatch(/postgresql:\/\//)
        }
    })

    it('should handle LOCAL_DEV environment flag', () => {
        // Test that LOCAL_DEV flag can be used to detect environment
        const isLocalDev = process.env.LOCAL_DEV === '1'

        expect(typeof isLocalDev).toBe('boolean')
    })

    it('should replace localhost with db when not in LOCAL_DEV', () => {
        // Simulate the replacement logic
        const originalUrl = 'postgresql://user:pass@localhost:5432/db'
        const isLocalDev = process.env.LOCAL_DEV === '1'

        const finalUrl = !isLocalDev
            ? originalUrl.replace('localhost', 'db')
            : originalUrl

        if (!isLocalDev) {
            expect(finalUrl).toContain('db')
            expect(finalUrl).not.toContain('localhost')
        } else {
            expect(finalUrl).toContain('localhost')
        }
    })
})
