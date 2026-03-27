import { prisma } from './src/client.js'

async function checkLogos() {
    try {
        const teams = await prisma.team.findMany({
            where: { name: { in: ['Fnatic', 'Natus Vincere'] } },
            select: { name: true, logourl: true, logodarkurl: true, textlesslogourl: true }
        })

        console.log('Teams with logo URLs:')
        console.log(JSON.stringify(teams, null, 2))
    } catch (error) {
        console.error('Error:', error)
    } finally {
        await prisma.$disconnect()
    }
}

checkLogos()
