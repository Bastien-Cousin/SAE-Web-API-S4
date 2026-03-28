import { prisma } from '../client.js'
import * as fs from 'fs'
import * as path from 'path'
import https from 'https'
import http from 'http'

/**
 * Script pour télécharger les bannières des tournois depuis Liquipedia
 * et les sauvegarder localement
 */

const ASSETS_DIR = path.join(process.cwd(), 'public', 'assets', 'tournaments')

/**
 * Attendre (sleep) en ms
 */
function sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms))
}

// Créer les dossiers nécessaires
function ensureDirectoriesExist() {
    if (!fs.existsSync(ASSETS_DIR)) {
        fs.mkdirSync(ASSETS_DIR, { recursive: true })
        console.log(`✅ Dossier créé: ${ASSETS_DIR}`)
    }
}

/**
 * Extraire l'extension depuis l'URL
 */
function getFileExtension(url: string): string {
    try {
        const urlObj = new URL(url)
        const pathname = urlObj.pathname
        const ext = path.extname(pathname)
        return ext || '.png'
    } catch {
        return '.png'
    }
}

/**
 * Valider une URL HTTP/HTTPS
 */
function isValidUrl(url: string): boolean {
    if (!url) return false
    try {
        const urlObj = new URL(url)
        return urlObj.protocol === 'http:' || urlObj.protocol === 'https:'
    } catch {
        return false
    }
}

/**
 * Télécharger un fichier depuis une URL
 */
function downloadFile(url: string, filename: string): Promise<boolean> {
    return new Promise((resolve) => {
        if (!url || !isValidUrl(url)) {
            console.log(`⏭️  URL invalide: ${url}`)
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

        protocol.get(url, {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer': 'https://liquipedia.net/'
            },
            timeout: 30000
        }, (response) => {
            if (response.statusCode !== 200) {
                console.error(`❌ Erreur ${response.statusCode} pour ${url}`)
                file.destroy()
                fs.unlink(filepath, () => { })
                resolve(false)
                return
            }

            response.pipe(file)

            file.on('finish', () => {
                file.close()
                resolve(true)
            })

            file.on('error', (err) => {
                fs.unlink(filepath, () => { })
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
function generateTournamentFilename(tournamentId: bigint, assetType: string, ext: string): string {
    return `tournament_${tournamentId}_${assetType}${ext}`
}

async function downloadTournamentBanners() {
    try {
        console.log('🚀 Démarrage du téléchargement des bannières des tournois...\n')

        ensureDirectoriesExist()

        // Récupérer tous les tournois
        const tournaments = await prisma.tournament.findMany({
            select: {
                pageid: true,
                objectname: true,
                name: true,
                bannerurl: true,
                iconurl: true,
            },
        })

        console.log(`📊 ${tournaments.length} tournois trouvés\n`)

        let successCount = 0
        let totalDownloads = 0

        for (const tournament of tournaments) {
            console.log(`\n📥 Traitement: ${tournament.name} (${tournament.objectname})`)

            const assets = [
                { url: tournament.bannerurl, type: 'banner' },
                { url: tournament.iconurl, type: 'icon' },
            ]

            for (const asset of assets) {
                if (!asset.url) continue

                const ext = getFileExtension(asset.url)
                const filename = generateTournamentFilename(tournament.pageid, asset.type, ext)
                const success = await downloadFile(asset.url, filename)

                totalDownloads++
                if (success) {
                    successCount++
                    console.log(`   ✅ Téléchargé: ${filename}`)
                }
            }
        }

        console.log(`\n\n📈 Résumé:`)
        console.log(`   Total téléchargés: ${successCount}/${totalDownloads}`)
        console.log(`   Dossier: ${ASSETS_DIR}\n`)

        process.exit(successCount === totalDownloads ? 0 : 1)
    } catch (error: any) {
        console.error(`❌ Erreur: ${error.message}`)
        console.error('Stack complète:', error)
        process.exit(1)
    } finally {
        await prisma.$disconnect()
    }
}

// Démarrer le script
(async () => {
    try {
        await downloadTournamentBanners()
    } finally {
        await prisma.$disconnect()
    }
})().catch(async (e) => {
    console.error(e)
    await prisma.$disconnect()
    process.exit(1)
})
