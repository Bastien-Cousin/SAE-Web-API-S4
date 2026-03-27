import fs from 'fs'
import path from 'path'

const sourcePath = '/app/public/assets/tournaments'
const destPath = '/var/www/html/assets/tournaments'

try {
    console.log('Copie des bannières de tournois...')
    
    const files = fs.readdirSync(sourcePath)
    let copied = 0

    for (const file of files) {
        const src = path.join(sourcePath, file)
        const dest = path.join(destPath, file)
        
        fs.copyFileSync(src, dest)
        console.log(`✅ Copié: ${file}`)
        copied++
    }

    console.log(`\n✅ ${copied} fichiers copiés avec succès!`)
    process.exit(0)
} catch (error) {
    console.error('❌ Erreur:', error.message)
    process.exit(1)
}
