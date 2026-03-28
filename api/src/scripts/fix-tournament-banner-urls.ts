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

const ASSETS_DIR = path.join(process.cwd(), 'public', 'assets', 'tournaments')

// Typage des assets possibles pour chaque tournoi
type AssetTypes = 'banner' | 'icon'

// tournamentAssets : tournamentId -> (assetType -> chemin)
const tournamentAssets: Record<string, Partial<Record<AssetTypes, string>>> = {}

/**
 * Mettre à jour les URLs des bannières pour pointer vers les fichiers locaux
 */
async function fixTournamentBannerUrls() {
    try {
        console.log('🚀 Démarrage de la correction des URLs des bannières...\n')

        // Vérifier que le dossier existe
        if (!fs.existsSync(ASSETS_DIR)) {
            console.error(`❌ Dossier non trouvé: ${ASSETS_DIR}`)
            process.exit(1)
        }

        // Récupérer tous les fichiers du dossier
        const files = fs.readdirSync(ASSETS_DIR)
        console.log(`📁 ${files.length} fichiers trouvés\n`)

        // Parser les fichiers et construire la map tournamentAssets
        for (const file of files) {
            // Format: tournament_[pageid]_[type].[ext]
            const match = file.match(/^tournament_(\d+)_(\w+)\.\w+$/)
            if (match) {
                const [, tournamentId, assetType] = match
                if (!tournamentAssets[tournamentId]) {
                    tournamentAssets[tournamentId] = {}
                }
                if (['banner', 'icon'].includes(assetType)) {
                    tournamentAssets[tournamentId][assetType as AssetTypes] = `/assets/tournaments/${file}`
                }
            }
        }

        console.log(`✅ ${Object.keys(tournamentAssets).length} tournois avec assets trouvés\n`)

        // Mettre à jour chaque tournoi dans la DB
        let updatedCount = 0
        let skippedCount = 0

        for (const [tournamentId, assets] of Object.entries(tournamentAssets)) {
            try {
                const tournament = await prisma.tournament.findUnique({
                    where: { pageid: BigInt(tournamentId) },
                })

                if (!tournament) {
                    skippedCount++
                    continue
                }

                // Construire les données de mise à jour
                const updateData: Partial<Record<string, string>> = {}

                if (assets.banner) {
                    updateData.bannerurl = `http://localhost:3070/assets/tournaments/${assets.banner.split('/').pop()}`
                }
                if (assets.icon) {
                    updateData.iconurl = `http://localhost:3070/assets/tournaments/${assets.icon.split('/').pop()}`
                }

                // Mettre à jour si au moins un champ
                if (Object.keys(updateData).length > 0) {
                    await prisma.tournament.update({
                        where: { pageid: BigInt(tournamentId) },
                        data: updateData,
                    })
                    updatedCount++

                    if (updatedCount % 100 === 0) {
                        console.log(`   ✅ ${updatedCount} tournois mis à jour...`)
                    }
                }
            } catch (error) {
                console.error(`⚠️ Erreur pour le tournoi ${tournamentId}:`, error)
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
fixTournamentBannerUrls()
    .then(async () => {
        await prisma.$disconnect()
    })
    .catch(async (error) => {
        console.error(error)
        await prisma.$disconnect()
        process.exit(1)
    })