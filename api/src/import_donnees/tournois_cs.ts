import { Pool, type PoolClient } from 'pg'
import axios, { AxiosError } from 'axios'
import * as dotenv from 'dotenv'
import * as path from 'path'
import { fileURLToPath } from 'url'
import settings from './settings.js'

// Créer __dirname en ESM
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Charger les variables d'environnement
dotenv.config({ path: path.join(__dirname, '.env') })

const API_KEY_TOURNAMENT: string = settings.API_KEY_TOURNAMENT || settings.API_KEY_TEAM
const DB_PASSWORD: string = settings.POSTGRES_PASSWORD
const dbname: string = settings.POSTGRES_DB
const user: string = settings.POSTGRES_USER
const host: string = settings.POSTGRES_HOST
const port: number = parseInt(String(settings.POSTGRES_PORT))

console.log('🚀 Démarrage du script importation tournois Counter-Strike...')
console.log(`⏰ Heure actuelle : ${new Date().toLocaleString()}`)

// URL de l'API Liquipedia pour les tournois
const url: string = 'https://api.liquipedia.net/api/v3/tournament'

// En-têtes pour la requête API
const headers: Record<string, string> = {
    Authorization: `Apikey ${API_KEY_TOURNAMENT}`,
    Accept: 'application/json',
    'User-Agent': 'CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)',
}

// Configuration PostgreSQL
const pool = new Pool({
    database: dbname,
    user: user,
    password: DB_PASSWORD,
    host: host,
    port: port,
    max: 20,
    idleTimeoutMillis: 30000,
    connectionTimeoutMillis: 2000,
})

// Interface pour les données de tournoi
interface tournament {
    pageid: number
    pagename?: string
    namespace?: number
    objectname?: string
    name?: string
    shortname?: string
    tickername?: string
    banner?: string
    bannerurl?: string
    bannerdark?: string
    bannerdarkurl?: string
    icon?: string
    iconurl?: string
    icondark?: string
    icondarkurl?: string
    seriespage?: string
    serieslist?: Record<string, any>
    previous?: string
    previous2?: string
    next?: string
    next2?: string
    game?: string
    mode?: string
    patch?: string
    endpatch?: string
    type?: string
    organizers?: string
    startdate?: string
    enddate?: string
    sortdate?: string
    locations?: Record<string, any>
    prizepool?: number | string
    participantsnumber?: number
    liquipediatier?: string
    liquipediatiertype?: string
    publishertier?: string
    status?: string
    maps?: string
    format?: string
    sponsors?: string
    extradata?: Record<string, any>
}

// Interface pour les paramètres de pagination
interface PaginationParams {
    wiki: string
    conditions: string
    limit: number
    offset: number
}

// Fonction utilitaire pour pause
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms))
}

// Fonction pour mettre à jour les tournois
async function updateTournaments(): Promise<void> {
    console.log('\n' + '='.repeat(60))
    console.log('🔄 DÉBUT DE LA MISE À JOUR DES TOURNOIS')
    console.log(`⏰ ${new Date().toLocaleString()}`)
    console.log('='.repeat(60))

    const tournamentsData: tournament[] = []
    const params: any = {
        wiki: 'counterstrike',
        conditions: '[[liquipediatier::1]] AND [[date::>2025-01-01]]',
        limit: 100,
        offset: 0,
    }

    let page: number = 1
    let totalFetched: number = 0

    try {
        // Pagination pour récupérer tous les tournois
        while (true) {
            console.log(`\n📄 Page ${page} - Offset: ${params.offset}`)

            try {
                const response = await axios.get<{ result: tournament[] }>(url, {
                    headers: headers,
                    params: params,
                    timeout: 10000,
                })

                console.log(`   Status Code: ${response.status}`)

                if (response.status === 200) {
                    const data: tournament[] = response.data.result || []
                    console.log(`   ✅ ${data.length} tournois récupérés`)

                    if (data.length === 0) {
                        console.log('   ✋ Fin de la pagination (aucune donnée)')
                        break
                    }

                    tournamentsData.push(...data)
                    totalFetched += data.length

                    params.offset += params.limit
                    console.log(`   → Prochain offset: ${params.offset}`)

                    // Anti rate-limit
                    await sleep(2000)
                    page++
                }
            } catch (error: any) {
                if (error.response?.status === 429) {
                    console.log('   ⏱️ Rate limit détecté (429) - attente 60s...')
                    await sleep(60000)
                    continue
                } else {
                    console.error(`   ❌ Erreur requête : ${error.message}`)
                    break
                }
            }
        }

        console.log(`\n📊 Total de tournois récupérés : ${totalFetched}`)

        // Connexion à la base de données PostgreSQL et insertion
        const client: PoolClient = await pool.connect()

        try {
            console.log('\n🔌 Connexion à la base de données...')
            console.log('✅ Connecté à PostgreSQL\n')

            let totalInserted: number = 0
            let totalSkipped: number = 0

            for (const tournament of tournamentsData) {
                try {
                    // Vérifier si le tournoi existe déjà
                    const checkQuery: string = 'SELECT 1 FROM "tournament" WHERE pageid = $1'
                    const existsResult = await client.query(checkQuery, [tournament.pageid])

                    if (existsResult.rows.length > 0) {
                        console.log(`   ℹ️ Le tournoi ${tournament.name} existe déjà, skip`)
                        totalSkipped++
                        continue
                    }

                    // Gestion des dates invalides
                    let startdate: string | null = tournament.startdate || null
                    if (startdate === '0000-01-01' || !startdate) {
                        startdate = null
                    }

                    let enddate: string | null = tournament.enddate || null
                    if (enddate === '0000-01-01' || !enddate) {
                        enddate = null
                    }

                    let sortdate: string | null = tournament.sortdate || null
                    if (sortdate === '0000-01-01' || !sortdate) {
                        sortdate = null
                    }

                    // Gestion des prize pool
                    let prizepool: number | null = null
                    if (tournament.prizepool) {
                        try {
                            prizepool = parseInt(String(tournament.prizepool))
                        } catch (e) {
                            prizepool = null
                        }
                    }

                    const query: string = `
            INSERT INTO "tournament" (
                pageid, pagename, namespace, objectname, name, shortname, tickername, banner, bannerurl, 
                bannerdark, bannerdarkurl, icon, iconurl, icondark, icondarkurl, seriespage, serieslist, 
                previous, previous2, next, next2, game, mode, patch, endpatch, type, organizers, startdate, 
                enddate, sortdate, locations, prizepool, participantsnumber, liquipediatier, liquipediatiertype, 
                publishertier, status, maps, format, sponsors, extradata
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, 
                $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31, $32, $33, $34, $35, $36, $37, $38, 
                $39, $40, $41, $42
            ) ON CONFLICT (pageid) DO NOTHING;
          `

                    const values: any[] = [
                        tournament.pageid,
                        tournament.pagename,
                        tournament.namespace,
                        tournament.objectname,
                        tournament.name,
                        tournament.shortname,
                        tournament.tickername,
                        tournament.banner,
                        tournament.bannerurl,
                        tournament.bannerdark,
                        tournament.bannerdarkurl,
                        tournament.icon,
                        tournament.iconurl,
                        tournament.icondark,
                        tournament.icondarkurl,
                        tournament.seriespage,
                        JSON.stringify(tournament.serieslist || {}),
                        tournament.previous,
                        tournament.previous2,
                        tournament.next,
                        tournament.next2,
                        tournament.game,
                        tournament.mode,
                        tournament.patch,
                        tournament.endpatch,
                        tournament.type,
                        tournament.organizers,
                        startdate,
                        enddate,
                        sortdate,
                        JSON.stringify(tournament.locations || {}),
                        prizepool,
                        tournament.participantsnumber,
                        tournament.liquipediatier,
                        tournament.liquipediatiertype,
                        tournament.publishertier,
                        tournament.status,
                        tournament.maps,
                        tournament.format,
                        tournament.sponsors,
                        JSON.stringify(tournament.extradata || {}),
                    ]

                    await client.query(query, values)
                    totalInserted++
                    console.log(`   ✅ ${tournament.name} inséré`)
                } catch (error: any) {
                    console.error(`   ❌ Erreur insertion ${tournament.name}: ${error.message}`)
                    continue
                }
            }

            console.log(`\n💾 ${totalInserted} tournois insérés avec succès !`)
            console.log(`⏭️ ${totalSkipped} tournois déjà présents\n`)

            console.log('='.repeat(60))
            console.log('✅ MISE À JOUR TERMINÉE')
            console.log('='.repeat(60) + '\n')
        } finally {
            client.release()
        }
    } catch (error: any) {
        console.error(`❌ Erreur globale : ${error.message}`)
    }
}

// Fonction principale pour exécuter la mise à jour une seule fois
async function main(): Promise<void> {
    try {
        await updateTournaments()
        await pool.end()
        process.exit(0)
    } catch (error: any) {
        console.error(`   ❌ Erreur lors de l'exécution: ${error.message}`)
        await pool.end()
        process.exit(1)
    }
}

// Gestion Ctrl+C
process.on('SIGINT', async () => {
    console.log('\n🛑 Arrêt du service...')
    await pool.end()
    process.exit(0)
})

process.on('SIGTERM', async () => {
    console.log('\n🛑 Arrêt du service...')
    await pool.end()
    process.exit(0)
})

// Démarrage
main().catch(async (error: any) => {
    console.error(`❌ Erreur non gérée : ${error.message}`)
    await pool.end()
    process.exit(1)
})
