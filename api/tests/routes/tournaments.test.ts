import { describe, it, expect, vi } from 'vitest'

// Mock Prisma client before importing routes
vi.mock('../../src/client', () => {
  const mockPrisma = {
    tournament: {
      findMany: vi.fn(),
      findUnique: vi.fn(),
      count: vi.fn(),
    },
  }
  return { prisma: mockPrisma }
})

import tournamentsRouter from '../../src/routes/tournaments'

describe('Tournaments Routes', () => {
  it('should export a valid Express router', () => {
    expect(tournamentsRouter).toBeDefined()
    expect(typeof tournamentsRouter).toBe('function')
  })

  it('should have router methods configured', () => {
    expect(tournamentsRouter).toBeDefined()
    expect(typeof tournamentsRouter.get).toBe('function')
  })
})
