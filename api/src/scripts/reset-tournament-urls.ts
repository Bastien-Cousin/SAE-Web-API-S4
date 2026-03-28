import { prisma } from '../client.js'
import axios from 'axios'
import * as dotenv from 'dotenv'
import * as path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Charger les variables d'environnement
dotenv.config({ path: path.join(__dirname, '..', '..', '.env.local') })
dotenv.config({ path: path.join(__dirname, '..', '..', '.env') })

const API_KEY = process.env.LIQUIPEDIA_API_KEY_TOURNAMENT || process.env.LIQUIPEDIA_API_KEY_TEAM || ''
const API_URL = 'https://api.liquipedia.net/api/v3/tournament'

async function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms))
}

async function resetTournamentUrls() {
    try {
        if (!API_KEY) {
            console.error('❌ Clé API Liquipedia manquante!')
            process.exit(1)
        }

        console.log('🚀 Réinitialisation des URLs des tournois depuis Liquipedia...\n')

        // Récupérer tous les tournois locaux et créer une map par pageid
        const localTournaments = await prisma.tournament.findMany({
            select: {
                pageid: true,
                objectname: true,
                name: true,
            },
        })

        console.log(`📊 ${localTournaments.length} tournois locaux trouvés\n`)

        const tournamentsByPageId = new Map()
        for (const t of localTournaments) {
            tournamentsByPageId.set(t.pageid.toString(), t)
        }

        let updated = 0
        let matched = 0
        let offset = 0
        const limit = 100

        // Récupérer les données de l'API avec pagination
        while (true) {
            try {
                console.log(`📥 Récupération de l'API (offset: ${offset}, limit: ${limit})...`)

                const response = await axios.get(API_URL, {
                    params: {
                        wiki: 'counterstrike',
                        conditions: '([[liquipediatier::1]] OR [[liquipediatier::2]]) AND [[startdate::>2020-01-01]]',
                        limit: limit,
                        offset: offset,
                    },
                    headers: {
                        Authorization: `Apikey ${API_KEY}`,
                        Accept: 'application/json',
                        'User-Agent': 'CounterStrikeUniversityProject/1.0',
                    },
                    timeout: 15000,
                })

                const apiTournaments = response.data?.result || []

                if (apiTournaments.length === 0) {
                    console.log(`✅ Fin de la pagination\n`)
                    break
                }

                console.log(`   ✅ ${apiTournaments.length} tournois reçus`)

                // Chercher les matches dans la BD locale
                for (const apiTournament of apiTournaments) {
                    const pageIdStr = apiTournament.pageid.toString()
                    const localTournament = tournamentsByPageId.get(pageIdStr)

                    if (localTournament) {
                        matched++
                        const updateData: any = {}

                        // Vérifier et mettre à jour bannerurl
                        if (apiTournament.bannerurl && 
                            apiTournament.bannerurl.startsWith('http') &&
                            !apiTournament.bannerurl.startsWith('//')) {
                            updateData.bannerurl = apiTournament.bannerurl
                        }

                        // Vérifier et mettre à jour iconurl
                        if (apiTournament.iconurl && 
                            apiTournament.iconurl.startsWith('http') &&
                            !apiTournament.iconurl.startsWith('//')) {
                            updateData.iconurl = apiTournament.iconurl
                        }

                        // Mettre à jour si au moins une URL a été trouvée
                        if (Object.keys(updateData).length > 0) {
                            await prisma.tournament.update({
                                where: { pageid: localTournament.pageid },
                                data: updateData,
                            })
                            updated++

                            if (updated % 50 === 0) {
                                console.log(`   ✅ ${updated} tournois mis à jour...`)
                            }
                        }
                    }
                }

                offset += limit
                await sleep(200) // Respecter les limites de l'API
            } catch (error: any) {
                if (error.message === 'Request failed with status code 429') {
                    console.log(`⚠️  Rate limit atteint, pause de 5 secondes...`)
                    await sleep(5000)
                } else if (error.code === 'ECONNABORTED') {
                    console.log(`⚠️  Timeout, pause et retry...`)
                    await sleep(2000)
                } else {
                    console.error(`❌ Erreur: ${error.message}`)
                    throw error
                }
            }
        }

        console.log(`\n📈 Résumé:`)
        console.log(`   ✅ ${updated} tournois mis à jour`)
        console.log(`   🔗 ${matched} tournois matchés avec l'API`)
        console.log(`   📊 Total local: ${localTournaments.length}\n`)

        process.exit(updated > 0 ? 0 : 1)
    } catch (error: any) {
        console.error(`❌ Erreur: ${error.message}`)
        process.exit(1)
    } finally {
        await prisma.$disconnect()
    }
}

resetTournamentUrls()
