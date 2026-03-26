import { describe, it, expect, vi } from 'vitest'

// Mock Prisma client before importing routes
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

import teamsRouter from '../../src/routes/teams'

describe('Teams Routes', () => {
  it('should export a valid Express router', () => {
    expect(teamsRouter).toBeDefined()
    expect(typeof teamsRouter).toBe('function')
  })

  it('should have router methods configured', () => {
    expect(teamsRouter).toBeDefined()
    expect(typeof teamsRouter.get).toBe('function')
    expect(typeof teamsRouter.post).toBe('function')
  })
})

