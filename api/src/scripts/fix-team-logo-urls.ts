import { prisma } from '../client.js'
import * as fs from 'fs'
import * as path from 'path'
import { fileURLToPath } from 'url'
import * as dotenv from 'dotenv'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Charger .env.local (dev local) en priorité, puis .env (fallback)
dotenv.config({ path: path.join(__dirname, '..', '..', '.env.local') })
dotenv.config({ path: path.join(__dirname, '..', '..', '.env') })

const ASSETS_DIR = path.join(process.cwd(), 'public', 'assets', 'teams')

// Typage des assets possibles pour chaque équipe
type AssetTypes = 'logo' | 'logodark' | 'textlesslogo' | 'textlesslogodark'

// teamAssets : teamId -> (assetType -> chemin)
const teamAssets: Record<string, Partial<Record<AssetTypes, string>>> = {}

/**
 * Mettre à jour les URLs des logos pour pointer vers les fichiers locaux
 */
async function fixTeamLogoUrls() {
    try {
        console.log('🚀 Démarrage de la correction des URLs des logos...\n')

        // Vérifier que le dossier existe
        if (!fs.existsSync(ASSETS_DIR)) {
            console.error(`❌ Dossier non trouvé: ${ASSETS_DIR}`)
            process.exit(1)
        }

        // Récupérer tous les fichiers du dossier
        const files = fs.readdirSync(ASSETS_DIR)
        console.log(`📁 ${files.length} fichiers trouvés\n`)

        // Parser les fichiers et construire la map teamAssets
        for (const file of files) {
            // Format: team_[pageid]_[type].[ext]
            const match = file.match(/^team_(\d+)_(\w+)\.\w+$/)
            if (match) {
                const [, teamId, assetType] = match
                if (!teamAssets[teamId]) {
                    teamAssets[teamId] = {}
                }
                // TypeScript sait maintenant que assetType est une clé valide partielle
                if (['logo', 'logodark', 'textlesslogo', 'textlesslogodark'].includes(assetType)) {
                    teamAssets[teamId][assetType as AssetTypes] = `/assets/teams/${file}`
                }
            }
        }

        console.log(`✅ ${Object.keys(teamAssets).length} équipes avec assets trouvées\n`)

        // Mettre à jour chaque équipe dans la DB
        let updatedCount = 0
        let skippedCount = 0

        for (const [teamId, assets] of Object.entries(teamAssets)) {
            try {
                const team = await prisma.team.findUnique({
                    where: { pageid: BigInt(teamId) },
                })

                if (!team) {
                    skippedCount++
                    continue
                }

                // Construire les données de mise à jour
                const updateData: Partial<Record<string, string>> = {}

                if (assets.logo) {
                    updateData.logourl = `http://localhost:3070/assets/teams/${assets.logo.split('/').pop()}`
                }
                if (assets.logodark) {
                    updateData.logodarkurl = `http://localhost:3070/assets/teams/${assets.logodark.split('/').pop()}`
                }
                if (assets.textlesslogo) {
                    updateData.textlesslogourl = `http://localhost:3070/assets/teams/${assets.textlesslogo.split('/').pop()}`
                }
                if (assets.textlesslogodark) {
                    updateData.textlesslogodarkurl = `http://localhost:3070/assets/teams/${assets.textlesslogodark.split('/').pop()}`
                }

                // Mettre à jour si au moins un champ
                if (Object.keys(updateData).length > 0) {
                    await prisma.team.update({
                        where: { pageid: BigInt(teamId) },
                        data: updateData,
                    })
                    updatedCount++

                    if (updatedCount % 100 === 0) {
                        console.log(`   ✅ ${updatedCount} équipes mises à jour...`)
                    }
                }
            } catch (error) {
                console.error(`⚠️ Erreur pour l'équipe ${teamId}:`, error)
            }
        }

        console.log(`\n📈 Résumé:`)
        console.log(`   ✅ Mises à jour: ${updatedCount}`)
        console.log(`   ⏭️ Ignorées: ${skippedCount}\n`)

        process.exit(0)
    } catch (error) {
        console.error('❌ Erreur:', error)
        process.exit(1)
    }
}

// Lancer le script
fixTeamLogoUrls()
    .then(async () => {
        await prisma.$disconnect()
    })
    .catch(async (error) => {
        console.error(error)
        await prisma.$disconnect()
        process.exit(1)
    })