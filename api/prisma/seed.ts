import { fileURLToPath } from 'url'
import * as path from 'path'
import * as dotenv from 'dotenv'
import pg from 'pg'
import { PrismaPg } from '@prisma/adapter-pg'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Charger .env.local (dev local) en priorité, puis .env (fallback)
dotenv.config({ path: path.join(__dirname, '..', '..', '.env.local') })
dotenv.config({ path: path.join(__dirname, '..', '..', '.env') })

import { PrismaClient } from '@prisma/client'

// Si on est dans Docker (pas de LOCAL_DEV), remplacer localhost par db
let connectionString = process.env.DATABASE_URL || 'postgresql://sae_user:sae_password@localhost:5432/sae_db'

if (!process.env.LOCAL_DEV) {
    connectionString = connectionString.replace('localhost', 'db')
}

const client = new pg.Client({
    connectionString,
})

const adapter = new PrismaPg(client)
const prisma = new PrismaClient({ adapter })

async function main() {
    console.log('Starting seed...')

    // Créer des équipes
    const team1 = await prisma.team.upsert({
        where: { pageid: BigInt(1) },
        update: {},
        create: {
            pageid: BigInt(1),
            objectname: 'fnatic',
            name: 'Fnatic',
            region: 'eu',
            logo: 'https://example.com/fnatic.png',
            status: 'Active',
        },
    })

    const team2 = await prisma.team.upsert({
        where: { pageid: BigInt(2) },
        update: {},
        create: {
            pageid: BigInt(2),
            objectname: 'navi',
            name: 'Natus Vincere',
            region: 'eu',
            logo: 'https://example.com/navi.png',
            status: 'Active',
        },
    })

    // Créer des joueurs
    const player1 = await prisma.player.upsert({
        where: { pageid: BigInt(100) },
        update: {},
        create: {
            pageid: BigInt(100),
            objectname: 'flusha',
            name: 'Freddy Wersén',
            nationality: 'se',
            team_id: team1.pageid,
            status: 'Active',
        },
    })

    const player2 = await prisma.player.upsert({
        where: { pageid: BigInt(101) },
        update: {},
        create: {
            pageid: BigInt(101),
            objectname: 's1mple',
            name: 'Oleksandr Kostyliev',
            nationality: 'ua',
            team_id: team2.pageid,
            status: 'Active',
        },
    })

    const player3 = await prisma.player.upsert({
        where: { pageid: BigInt(102) },
        update: {},
        create: {
            pageid: BigInt(102),
            objectname: 'device',
            name: 'Nicolai Reedtz',
            nationality: 'dk',
            team_id: team1.pageid,
            status: 'Active',
        },
    })

    // Créer un match
    const match1 = await prisma.match.upsert({
        where: { pageid: BigInt(1000) },
        update: {},
        create: {
            pageid: BigInt(1000),
            objectname: 'fnatic-vs-navi-2024-01',
            team1_id: team1.pageid,
            team2_id: team2.pageid,
            status: 'Completed',
            winner: 'fnatic',
            finished: true,
            date: new Date('2024-01-15'),
        },
    })

    // Créer les relations matchteam
    await prisma.matchteam.upsert({
        where: { match_id_team_id: { match_id: match1.pageid, team_id: team1.pageid } },
        update: {},
        create: {
            match_id: match1.pageid,
            team_id: team1.pageid,
        },
    })

    await prisma.matchteam.upsert({
        where: { match_id_team_id: { match_id: match1.pageid, team_id: team2.pageid } },
        update: {},
        create: {
            match_id: match1.pageid,
            team_id: team2.pageid,
        },
    })

    // Créer les relations matchplayer
    await prisma.matchplayer.upsert({
        where: { match_id_player_id: { match_id: match1.pageid, player_id: player1.pageid } },
        update: {},
        create: {
            match_id: match1.pageid,
            player_id: player1.pageid,
        },
    })

    await prisma.matchplayer.upsert({
        where: { match_id_player_id: { match_id: match1.pageid, player_id: player2.pageid } },
        update: {},
        create: {
            match_id: match1.pageid,
            player_id: player2.pageid,
        },
    })

    console.log('Seed completed!')
}

main()
    .then(async () => {
        await prisma.$disconnect()
        await client.end()
    })
    .catch(async (e) => {
        console.error(e)
        await prisma.$disconnect()
        await client.end()
        process.exit(1)
    })
