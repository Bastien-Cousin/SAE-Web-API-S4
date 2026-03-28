import { describe, it, expect, vi } from 'vitest'

// Mock Prisma client before importing routes
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

import matchesRouter from '../../src/routes/matches'

describe('Matches Routes', () => {
  it('should export a valid Express router', () => {
    expect(matchesRouter).toBeDefined()
    expect(typeof matchesRouter).toBe('function')
  })

  it('should have router methods configured', () => {
    expect(matchesRouter).toBeDefined()
    expect(typeof matchesRouter.get).toBe('function')
    expect(typeof matchesRouter.post).toBe('function')
  })
})
