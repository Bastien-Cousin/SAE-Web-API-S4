import { prisma } from '../client.js'
import * as fs from 'fs'
import * as path from 'path'
import https from 'https'
import http from 'http'

/**
 * Script pour télécharger les assets des tournois depuis Liquipedia
 * (banners, icons) et les sauvegarder localement
 * 
 * Usage: npx ts-node src/scripts/download-tournament-assets.ts
 */

const ASSETS_DIR = path.join(process.cwd(), 'public', 'assets', 'tournaments')

// Créer les dossiers nécessaires
function ensureDirectoriesExist() {
    if (!fs.existsSync(ASSETS_DIR)) {
        fs.mkdirSync(ASSETS_DIR, { recursive: true })
        console.log(`✅ Dossier créé: ${ASSETS_DIR}`)
    }
}

/**
 * Télécharger un fichier depuis une URL
 */
function downloadFile(url: string, filename: string): Promise<boolean> {
    return new Promise((resolve) => {
        if (!url) {
            resolve(false)
            return
        }

        const filepath = path.join(ASSETS_DIR, filename)

        // Si le fichier existe déjà, ne pas retélécharger
        if (fs.existsSync(filepath)) {
            console.log(`⏭️  Fichier existant: ${filename}`)
            resolve(true)
            return
        }

        const file = fs.createWriteStream(filepath)

        // Déterminer le protocole (http ou https)
        const protocol = url.startsWith('https') ? https : http

        protocol.get(url, (response) => {
            if (response.statusCode !== 200) {
                console.error(`❌ Erreur ${response.statusCode} pour ${url}`)
                file.destroy()
                fs.unlink(filepath, () => {})
                resolve(false)
                return
            }

            response.pipe(file)

            file.on('finish', () => {
                file.close()
                console.log(`✅ Téléchargé: ${filename}`)
                resolve(true)
            })

            file.on('error', (err) => {
                fs.unlink(filepath, () => {})
                console.error(`❌ Erreur fichier: ${err.message}`)
                resolve(false)
            })
        }).on('error', (err) => {
            console.error(`❌ Erreur réseau: ${err.message}`)
            resolve(false)
        })
    })
}

/**
 * Générer un nom de fichier unique pour le tournoi
 */
function generateTournamentFilename(tournamentId: bigint, assetType: string): string {
    return `tournament_${tournamentId}_${assetType}.png`
}

/**
 * Télécharger tous les assets des tournois
 */
async function downloadTournamentAssets() {
    try {
        console.log('🚀 Démarrage du téléchargement des assets des tournois...\n')

        ensureDirectoriesExist()

        // Récupérer tous les tournois
        const tournaments = await prisma.tournament.findMany({
            select: {
                pageid: true,
                objectname: true,
                bannerurl: true,
                iconurl: true,
            },
        })

        console.log(`📊 ${tournaments.length} tournois trouvés\n`)

        let successCount = 0
        let totalDownloads = 0

        for (const tournament of tournaments) {
            console.log(`\n📥 Traitement: ${tournament.objectname} (ID: ${tournament.pageid})`)

            const assets = [
                { url: tournament.bannerurl, type: 'banner' },
                { url: tournament.iconurl, type: 'icon' },
            ]

            for (const asset of assets) {
                if (!asset.url) continue

                const filename = generateTournamentFilename(tournament.pageid, asset.type)
                const success = await downloadFile(asset.url, filename)

                totalDownloads++
                if (success) {
                    successCount++
                    // Mettre à jour l'URL dans la base de données
                    const publicUrl = `/assets/tournaments/${filename}`

                    const updateData: any = {}
                    if (asset.type === 'banner') updateData.bannerurl = publicUrl
                    else if (asset.type === 'icon') updateData.iconurl = publicUrl

                    await prisma.tournament.update({
                        where: { pageid: tournament.pageid },
                        data: updateData,
                    })

                    console.log(`   ✅ Mis à jour: ${publicUrl}`)
                }
            }
        }

        console.log(`\n\n📈 Résumé:`)
        console.log(`   Total téléchargés: ${successCount}/${totalDownloads}`)
        console.log(`   Dossier: ${ASSETS_DIR}\n`)

        process.exit(successCount === totalDownloads ? 0 : 1)
    } catch (error) {
        console.error('❌ Erreur:', error)
        process.exit(1)
    } finally {
        await prisma.$disconnect()
    }
}

// Exécuter
downloadTournamentAssets()
