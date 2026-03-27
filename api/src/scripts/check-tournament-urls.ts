import { prisma } from '../client.js'

async function checkTournamentUrls() {
    try {
        console.log('🔍 Vérification des URLs des tournois...\n')

        // Récupérer les premiers 5 tournois avec leurs URLs bannerurl et iconurl
        const tournaments = await prisma.tournament.findMany({
            take: 5,
            select: {
                pageid: true,
                objectname: true,
                name: true,
                bannerurl: true,
                iconurl: true,
            },
        })

        console.log(`📊 ${tournaments.length} premiers tournois:\n`)

        for (const tournament of tournaments) {
            console.log(`\n📌 ${tournament.name}`)
            console.log(`   objectname: ${tournament.objectname}`)
            console.log(`   bannerurl: ${tournament.bannerurl}`)
            console.log(`   iconurl: ${tournament.iconurl}`)
        }

        // Compter combien ont des URLs invalides (locales)
        const withLocalUrls = tournaments.filter(t => 
            (t.bannerurl?.startsWith('/assets/') || t.bannerurl?.startsWith('./')) ||
            (t.iconurl?.startsWith('/assets/') || t.iconurl?.startsWith('./'))
        ).length

        console.log(`\n\n⚠️  Tournois avec URLs locales: ${withLocalUrls}/${tournaments.length}`)

        // Afficher les statistiques globales
        const stats = await prisma.tournament.aggregate({
            where: {
                bannerurl: {
                    not: null
                }
            },
            _count: {
                pageid: true
            }
        })

        console.log(`📈 Tournois avec bannerurl non-null: ${stats._count.pageid}`)

    } catch (error: any) {
        console.error(`❌ Erreur: ${error.message}`)
    } finally {
        await prisma.$disconnect()
    }
}

checkTournamentUrls()
