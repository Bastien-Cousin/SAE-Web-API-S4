import { Pool, type PoolClient } from 'pg';
import axios from 'axios';
import settings from './settings.js';
import { fileURLToPath } from 'url';

// Interface pour les données d'équipe
interface team {
    pageid: number;
    pagename?: string;
    namespace?: number;
    objectname?: string;
    name?: string;
    locations?: string[];
    region?: string;
    logo?: string;
    logourl?: string;
    logodark?: string;
    logodarkurl?: string;
    textlesslogourl?: string;
    textlesslogodarkurl?: string;
    status?: string;
    createdate?: string;
    disbanddate?: string;
    earnings?: number | string;
    earningsbyyear?: Record<string, number>;
    template?: string;
    links?: Record<string, string>;
    extradata?: Record<string, any>;
}

// Interface pour les paramètres de pagination
interface PaginationParams {
    wiki: string;
    limit: number;
    offset: number;
}

// Fonction utilitaire pour pause
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Fonction exportée d'importation des équipes
export async function importTeams(pool: Pool): Promise<boolean> {
    const API_KEY_TEAM: string = settings.API_KEY_TEAM;
    const url: string = 'https://api.liquipedia.net/api/v3/team';
    
    const headers: Record<string, string> = {
        Authorization: `Apikey ${API_KEY_TEAM}`,
        Accept: 'application/json',
        'User-Agent': 'CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)'
    };

    const teamsData: team[] = [];
    const params: PaginationParams = {
        wiki: 'counterstrike',
        limit: 100,
        offset: 0
    };

    let page: number = 1;
    let totalFetched: number = 0;

    try {
        // Pagination pour récupérer toutes les équipes
        while (true) {
            console.log(`\n📄 Page ${page} - Offset: ${params.offset}`);

            try {
                const response = await axios.get<{ result: team[] }>(url, {
                    headers: headers,
                    params: params,
                    timeout: 10000
                });

                console.log(`   Status Code: ${response.status}`);

                if (response.status === 200) {
                    const data: team[] = response.data.result || [];
                    console.log(`   ✅ ${data.length} équipes récupérées`);

                    if (data.length === 0) {
                        console.log('   ✋ Fin de la pagination (aucune donnée)');
                        break;
                    }

                    teamsData.push(...data);
                    totalFetched += data.length;

                    params.offset += params.limit;
                    console.log(`   → Prochain offset: ${params.offset}`);

                    // Anti rate-limit
                    await sleep(2000);
                    page++;
                }
            } catch (error: any) {
                if (error.response?.status === 429) {
                    console.log('   ⏱️ Rate limit détecté (429) - attente 2h...');
                    await sleep(7200000);
                    continue;
                } else {
                    console.error(`   ❌ Erreur requête : ${error.message}`);
                    break;
                }
            }
        }

        console.log(`\n📊 Total d'équipes récupérées : ${totalFetched}`);

        // Connexion à la base de données PostgreSQL et insertion
        const client: PoolClient = await pool.connect();

        try {
            console.log('\n🔌 Insertion des équipes...\n');

            let totalInserted: number = 0;
            let totalSkipped: number = 0;

            for (const team of teamsData) {
                try {
                    // Vérifier si l'équipe existe déjà
                    const checkQuery: string = 'SELECT 1 FROM "team" WHERE pageid = $1';
                    const existsResult = await client.query(checkQuery, [team.pageid]);

                    if (existsResult.rows.length > 0) {
                        console.log(`   ℹ️ L'équipe ${team.name} existe déjà, skip`);
                        totalSkipped++;
                        continue;
                    }

                    // Gestion des dates invalides
                    let createdate: string | null = team.createdate || null;
                    if (createdate === '0000-01-01' || !createdate) {
                        createdate = null;
                    }

                    let disbanddate: string | null = team.disbanddate || null;
                    if (disbanddate === '0000-01-01' || !disbanddate) {
                        disbanddate = null;
                    }

                    // Gestion des earnings
                    let earnings: number | null = null;
                    if (team.earnings) {
                        try {
                            earnings = parseInt(String(team.earnings));
                        } catch (e) {
                            earnings = null;
                        }
                    }

                    const query: string = `
            INSERT INTO "team" (
                pageid, pagename, namespace, objectname, name, locations, region, logo, logourl, logodark, 
                logodarkurl, textlesslogourl, textlesslogodarkurl, status, createdate, disbanddate, earnings, 
                earningsbyyear, template, links, extradata
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21
            ) ON CONFLICT (pageid) DO NOTHING;
          `;

                    const values: any[] = [
                        team.pageid,
                        team.pagename,
                        team.namespace,
                        team.objectname,
                        team.name,
                        JSON.stringify(team.locations || []),
                        team.region,
                        team.logo,
                        team.logourl,
                        team.logodark,
                        team.logodarkurl,
                        team.textlesslogourl,
                        team.textlesslogodarkurl,
                        team.status,
                        createdate,
                        disbanddate,
                        earnings,
                        JSON.stringify(team.earningsbyyear || {}),
                        team.template,
                        JSON.stringify(team.links || {}),
                        JSON.stringify(team.extradata || {})
                    ];

                    await client.query(query, values);
                    totalInserted++;
                    console.log(`   ✅ ${team.name} inséré`);
                } catch (error: any) {
                    console.error(`   ❌ Erreur insertion ${team.name}: ${error.message}`);
                    continue;
                }
            }

            console.log(`\n💾 ${totalInserted} équipes insérées`);
            console.log(`⏭️ ${totalSkipped} équipes déjà présentes\n`);
            return true;
        } finally {
            client.release();
        }
    } catch (error: any) {
        console.error(`❌ Erreur: ${error.message}`);
        return false;
    }
}

// Fonction principale pour exécuter via CLI
async function main(): Promise<void> {
    const DB_PASSWORD: string = settings.POSTGRES_PASSWORD;
    const dbname: string = settings.POSTGRES_DB;
    const user: string = settings.POSTGRES_USER;
    const host: string = settings.POSTGRES_HOST;
    const port: number = parseInt(String(settings.POSTGRES_PORT));

    const pool = new Pool({
        database: dbname,
        user: user,
        password: DB_PASSWORD,
        host: host,
        port: port,
        max: 20,
        idleTimeoutMillis: 30000,
        connectionTimeoutMillis: 2000
    });

    try {
        console.log('🚀 Démarrage du script importation équipes Counter-Strike...');
        console.log(`⏰ Heure actuelle : ${new Date().toLocaleString()}\n`);
        const ok = await importTeams(pool);
        if (ok) {
            console.log('✅ Import réussi');
            process.exit(0);
        } else {
            console.error('❌ Import échoué');
            process.exit(1);
        }
    } catch (error: any) {
        console.error(`❌ Erreur: ${error.message}`);
        process.exit(1);
    } finally {
        await pool.end();
    }
}

// Gestion Ctrl+C
process.on('SIGINT', async () => {
    console.log('\n🛑 Arrêt...');
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n🛑 Arrêt...');
    process.exit(0);
});

// Démarrage en CLI
if (process.argv[1] === fileURLToPath(import.meta.url)) {
    main().catch(async (error: any) => {
        console.error(`❌ Erreur fatale: ${error.message}`);
        process.exit(1);
    });
}
