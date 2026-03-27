import { fileURLToPath } from 'url'
import * as path from 'path'
import * as dotenv from 'dotenv'
import pg from 'pg'
import { PrismaPg } from '@prisma/adapter-pg'
import * as fs from 'fs'
import https from 'https'
import http from 'http'

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

/**
 * Script pour télécharger les bannières des tournois depuis Liquipedia
 * et les sauvegarder localement
 */

const ASSETS_DIR = process.env.LOCAL_DEV 
    ? path.join(process.cwd(), 'public', 'assets', 'tournaments')
    : '/var/www/html/assets/tournaments'

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
 * Valider une URL
 */
function isValidUrl(url: string): boolean {
    try {
        new URL(url)
        return true
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
                console.log(`✅ Téléchargé: ${filename}`)
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

            const updateData: any = {}

            for (const asset of assets) {
                if (!asset.url) continue

                const ext = getFileExtension(asset.url)
                const filename = generateTournamentFilename(tournament.pageid, asset.type, ext)
                const success = await downloadFile(asset.url, filename)

                totalDownloads++
                if (success) {
                    successCount++
                    // Mettre à jour l'URL dans la base de données
                    const publicUrl = `/assets/tournaments/${filename}`

                    if (asset.type === 'banner') updateData.bannerurl = publicUrl
                    else if (asset.type === 'icon') updateData.iconurl = publicUrl

                    console.log(`   ✅ Mis à jour: ${publicUrl}`)
                }
            }

            // Mettre à jour la BD si au moins un fichier a été téléchargé
            if (Object.keys(updateData).length > 0) {
                await prisma.tournament.update({
                    where: { pageid: tournament.pageid },
                    data: updateData,
                })
            }
        }

        console.log(`\n\n📈 Résumé:`)
        console.log(`   Total téléchargés: ${successCount}/${totalDownloads}`)
        console.log(`   Dossier: ${ASSETS_DIR}\n`)

        process.exit(successCount === totalDownloads ? 0 : 1)
    } catch (error: any) {
        console.error(`❌ Erreur: ${error.message}`)
        console.error('Stack complète:', error)
        console.log('prisma object:', prisma)
        process.exit(1)
    } finally {
        await prisma.$disconnect()
        await client.end()
    }
}

// Gestion Ctrl+C
process.on('SIGINT', async () => {
    console.log('\n🛑 Arrêt du service...')
    await prisma.$disconnect()
    await client.end()
    process.exit(0)
})

process.on('SIGTERM', async () => {
    console.log('\n🛑 Arrêt du service...')
    await prisma.$disconnect()
    await client.end()
    process.exit(0)
})

// Démarrer le script
downloadTournamentBanners()
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
