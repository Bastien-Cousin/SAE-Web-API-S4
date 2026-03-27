import { prisma } from '../client.js'
import * as dotenv from 'dotenv'
import * as path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

dotenv.config({ path: path.join(__dirname, '..', '..', '.env.local') })
dotenv.config({ path: path.join(__dirname, '..', '..', '.env') })

async function diagnoseDatabase() {
    try {
        console.log('\n📊 DIAGNOSTIC DE LA BASE DE DONNÉES\n')
        console.log('='.repeat(60))

        // Compter les équipes
        const totalTeams = await prisma.team.count()
        console.log(`📋 Total d'équipes: ${totalTeams}`)

        // Vérifier les équipes du seed (pageid 1 et 2)
        const seedTeams = await prisma.team.findMany({
            where: {
                pageid: {
                    in: [BigInt(1), BigInt(2)]
                }
            },
            select: {
                pageid: true,
                name: true,
                logourl: true
            }
        })

        if (seedTeams.length > 0) {
            console.log(`\n🌱 Équipes du SEED trouvées:`)
            for (const team of seedTeams) {
                console.log(`   - ${team.name} (ID: ${team.pageid}) - Logo: ${team.logourl}`)
            }
        }

        // Vérifier les équipes avec URLs locales
        const teamsWithLocalUrls = await prisma.team.count({
            where: {
                logourl: {
                    startsWith: '/assets/teams/'
                }
            }
        })

        console.log(`\n✅ Équipes avec URLs locales: ${teamsWithLocalUrls}`)

        // Vérifier les équipes avec URLs distantes
        const teamsWithRemoteUrls = await prisma.team.count({
            where: {
                logourl: {
                    startsWith: 'http'
                }
            }
        })

        console.log(`🌐 Équipes avec URLs distantes: ${teamsWithRemoteUrls}`)

        // Vérifier les équipes sans logo
        const teamsWithoutLogo = await prisma.team.count({
            where: {
                OR: [
                    { logourl: null },
                    { logourl: '' }
                ]
            }
        })

        console.log(`❌ Équipes sans logo: ${teamsWithoutLogo}`)

        console.log('\n' + '='.repeat(60))

        // Recommandations
        console.log('\n💡 RECOMMANDATIONS:')
        if (totalTeams < 100) {
            console.log('   ⚠️  Moins de 100 équipes trouvées!')
            console.log('   → Exécutez: npm run import:teams')
            console.log('   → Puis: npm run fix:team-urls')
        } else if (teamsWithLocalUrls === 0 && teamsWithRemoteUrls > 0) {
            console.log('   → Les équipes ont toutes des URLs distantes')
            console.log('   → Exécutez: npm run fix:team-urls')
        } else {
            console.log('   ✅ Configuration semble correcte!')
        }

        console.log()

        process.exit(0)
    } catch (error) {
        console.error('❌ Erreur:', error)
        process.exit(1)
    }
}

diagnoseDatabase()
    .then(async () => {
        await prisma.$disconnect()
    })
    .catch(async (error) => {
        console.error(error)
        await prisma.$disconnect()
        process.exit(1)
    })
