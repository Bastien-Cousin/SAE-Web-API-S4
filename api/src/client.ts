import { PrismaClient } from '@prisma/client'
import { PrismaPg } from '@prisma/adapter-pg'

// Si on est dans Docker (pas de LOCAL_DEV), remplacer localhost par db
let connectionString = process.env.DATABASE_URL || 'postgresql://sae_user:sae_password@localhost:5432/sae_db'

if (!process.env.LOCAL_DEV) {
    connectionString = connectionString.replace('localhost', 'db')
}

const adapter = new PrismaPg({
    connectionString,
})

export const prisma = new PrismaClient({ adapter })