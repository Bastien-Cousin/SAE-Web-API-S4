import { describe, it, expect, vi } from 'vitest'

// Mock Prisma client before importing routes
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

import playersRouter from '../../src/routes/players'

describe('Players Routes', () => {
  it('should export a valid Express router', () => {
    expect(playersRouter).toBeDefined()
    expect(typeof playersRouter).toBe('function')
  })

  it('should have router methods configured', () => {
    expect(playersRouter).toBeDefined()
    expect(typeof playersRouter.get).toBe('function')
    expect(typeof playersRouter.post).toBe('function')
  })
})
