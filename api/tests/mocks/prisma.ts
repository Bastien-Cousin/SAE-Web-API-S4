import { vi } from 'vitest'

export const mockPrismaTeam = {
    findMany: vi.fn(),
    findUnique: vi.fn(),
    count: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
}

export const mockPrismaPlayer = {
    findMany: vi.fn(),
    findUnique: vi.fn(),
    count: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
}

export const mockPrismaMatch = {
    findMany: vi.fn(),
    findUnique: vi.fn(),
    count: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
}

export const mockPrisma = {
    team: mockPrismaTeam,
    player: mockPrismaPlayer,
    match: mockPrismaMatch,
    $queryRaw: vi.fn(),
}
