import { prisma } from '../client.js'
import * as fs from 'fs'
import * as path from 'path'
import https from 'https'
import http from 'http'

/**
 * Script pour télécharger les logos des équipes depuis Liquipedia
 * et les sauvegarder localement
 * 
 * Usage: npx ts-node src/scripts/download-team-assets.ts
 */

const ASSETS_DIR = path.join(process.cwd(), 'public', 'assets', 'teams')

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
 * Générer un nom de fichier unique pour l'équipe
 */
function generateTeamFilename(teamId: bigint, assetType: string, ext: string): string {
    return `team_${teamId}_${assetType}${ext}`
}

/**
 * Télécharger tous les logos des équipes
 */
async function downloadTeamAssets() {
    try {
        console.log('🚀 Démarrage du téléchargement des logos des équipes...\n')

        ensureDirectoriesExist()

        // Récupérer toutes les équipes
        const teams = await prisma.team.findMany({
            select: {
                pageid: true,
                objectname: true,
                name: true,
                logourl: true,
                logodarkurl: true,
                textlesslogourl: true,
                textlesslogodarkurl: true,
            },
        })

        console.log(`📊 ${teams.length} équipes trouvées\n`)

        let successCount = 0
        let totalDownloads = 0

        for (const team of teams) {
            console.log(`\n📥 Traitement: ${team.name} (${team.objectname})`)

            const assets = [
                { url: team.logourl, type: 'logo' },
                { url: team.logodarkurl, type: 'logodark' },
                { url: team.textlesslogourl, type: 'textlesslogo' },
                { url: team.textlesslogodarkurl, type: 'textlesslogodark' },
            ]

            const updateData: any = {}

            for (const asset of assets) {
                if (!asset.url) continue

                const ext = getFileExtension(asset.url)
                const filename = generateTeamFilename(team.pageid, asset.type, ext)
                const success = await downloadFile(asset.url, filename)

                totalDownloads++
                if (success) {
                    successCount++
                    // Mettre à jour l'URL dans la base de données
                    const publicUrl = `/assets/teams/${filename}`

                    if (asset.type === 'logo') updateData.logourl = publicUrl
                    else if (asset.type === 'logodark') updateData.logodarkurl = publicUrl
                    else if (asset.type === 'textlesslogo') updateData.textlesslogourl = publicUrl
                    else if (asset.type === 'textlesslogodark') updateData.textlesslogodarkurl = publicUrl

                    console.log(`   ✅ Mis à jour: ${publicUrl}`)
                }
            }

            // Mettre à jour la BD si au moins un fichier a été téléchargé
            if (Object.keys(updateData).length > 0) {
                await prisma.team.update({
                    where: { pageid: team.pageid },
                    data: updateData,
                })
            }

            // Petit délai supplémentaire entre les équipes
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
downloadTeamAssets()
