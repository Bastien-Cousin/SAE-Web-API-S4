import { Pool } from 'pg';
import axios from 'axios';
import * as fs from 'fs';
import * as path from 'path';
import settings from './settings.js';
import { fileURLToPath } from 'url';

// Fonction pour ajouter un délai
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Fonction exportée d'importation des joueurs
export async function importPlayers(pool: Pool): Promise<boolean> {
    const API_KEY_PLAYER = settings.API_KEY_PLAYER;
    const url = 'https://api.liquipedia.net/api/v3/player';
    
    const headers = {
        'Authorization': `Apikey ${API_KEY_PLAYER}`,
        'Accept': 'application/json',
        'User-Agent': 'CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)'
    };

    console.log('📥 Importation des joueurs...');

    interface player {
        [key: string]: any;
        name?: string;
        teampagename?: string;
    }

    const allPlayers: Map<string | undefined, player[]> = new Map();
    let totalFetched = 0;
    let offset = 0;
    let page = 1;

    // Pagination pour récupérer toutes les pages
    while (true) {
        console.log(`\n📄 Page ${page} - Offset: ${offset}`);
        try {
            const response = await axios.get(url, {
                headers: headers,
                params: {
                    wiki: 'counterstrike',
                    limit: 100,
                    offset: offset
                },
                timeout: 10000
            });

            console.log(`   Status Code: ${response.status}`);

            if (response.status === 200) {
                const data = response.data.result || [];
                console.log(`   ✅ ${data.length} joueurs récupérés`);

                if (!data || data.length === 0) {
                    console.log("   ✋ Fin de la pagination (aucune donnée)");
                    break;
                }

                for (const player of data) {
                    if (player.type === "player") {
                        const teamName = player.teampagename;
                        if (!allPlayers.has(teamName)) {
                            allPlayers.set(teamName, []);
                        }
                        allPlayers.get(teamName)!.push(player);
                        totalFetched++;
                    }
                }

                offset += 100;
                console.log(`   → Prochain offset: ${offset}`);
                await sleep(2000); // Anti rate-limit
                page++;
            } else {
                console.error(`   ❌ Erreur API : ${response.status}`);
                break;
            }
        } catch (error: any) {
            if (error.response?.status === 429) {
                console.warn(`   ⚠️ Rate limit détecté (429) - attente 60s...`);
                await sleep(60000);
                continue;
            }
            console.error(`   ❌ Erreur requête : ${error.message}`);
            break;
        }
    }

    console.log(`\n📊 Total de joueurs récupérés : ${totalFetched}`);
    console.log(`📍 Nombre d'équipes : ${allPlayers.size}`);

    // Connexion à la base de données PostgreSQL
    console.log("\n🔌 Connexion à la base de données...");
    const client = await pool.connect();

    try {
        console.log("✅ Connecté à PostgreSQL");

        let totalInserted = 0;

        for (const [teamName, players] of allPlayers) {
            console.log(`\n👥 Équipe: ${teamName} (${players.length} joueurs)`);
            const playersToInsert = players.slice(0, 5); // Ne garder que les 5 premiers joueurs
            console.log(`   → Insertion de ${playersToInsert.length} joueurs...`);

            for (let idx = 0; idx < playersToInsert.length; idx++) {
                const player = playersToInsert[idx];
                if (!player) {
                    continue;
                }
                try {
                    let birthdate = player.birthdate;
                    if (birthdate === "0000-01-01" || !birthdate) {
                        birthdate = null;
                    }

                    let deathdate = player.deathdate;
                    if (deathdate === "0000-01-01" || !deathdate) {
                        deathdate = null;
                    }

                    // Essayer de récupérer team_id depuis la table teams
                    let teamId: number | null = null;
                    try {
                        const playerTeamName = player.teampagename;
                        if (playerTeamName) {
                            const result = await client.query(
                                "SELECT pageid FROM team WHERE pagename = $1",
                                [playerTeamName]
                            );
                            teamId = result.rows.length > 0 ? result.rows[0].pageid : null;
                        }
                    } catch (te: any) {
                        console.log(`      ℹ️ Impossible de récupérer team_id: ${te.message}`);
                        teamId = null;
                    }

                    // Gérer les earnings comme integer
                    let earnings = player.earnings;
                    if (earnings !== undefined && earnings !== null) {
                        try {
                            earnings = earnings ? parseInt(earnings) : null;
                        } catch {
                            earnings = null;
                        }
                    }

                    const query = `
            INSERT INTO player (
                pageid, pagename, namespace, objectname, id, alternateid, name, localizedname, type, 
                nationality, nationality2, nationality3, region, birthdate, deathdate, teampagename, 
                teamtemplate, links, status, earnings, earningsbyyear, extradata, wiki, team_id
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24
            ) ON CONFLICT (objectname) DO UPDATE SET
                teampagename = EXCLUDED.teampagename,
                team_id = EXCLUDED.team_id,
                earnings = EXCLUDED.earnings;
          `;

                    const values = [
                        player.pageid,
                        player.pagename,
                        player.namespace,
                        player.objectname,
                        player.id,
                        player.alternateid,
                        player.name,
                        player.localizedname,
                        player.type,
                        player.nationality,
                        player.nationality2,
                        player.nationality3,
                        player.region,
                        birthdate,
                        deathdate,
                        player.teampagename,
                        player.teamtemplate,
                        JSON.stringify(player.links || {}),
                        player.status,
                        earnings,
                        JSON.stringify(player.earningsbyyear || {}),
                        JSON.stringify(player.extradata || {}),
                        "counterstrike",
                        teamId
                    ];

                    await client.query(query, values);
                    totalInserted++;
                    console.log(`      ✅ ${idx + 1}. ${player.name} inséré`);
                } catch (e: any) {
                    console.error(`      ❌ Erreur insertion ${player?.name}: ${e.message}`);
                    // Pas de rollback avec le client, il ne sera pas réutilisé après erreur
                    continue;
                }
            }
        }

        console.log(`\n💾 ${totalInserted} joueurs insérés`);
        return true;
    } catch (e: any) {
        console.error(`❌ Erreur: ${e.message}`);
        return false;
    } finally {
        client.release();
    }
}

// Fonction principale pour exécuter via CLI
async function main() {
    const DB_PASSWORD = settings.POSTGRES_PASSWORD;
    const dbname = settings.POSTGRES_DB;
    const user = settings.POSTGRES_USER;
    const host = settings.POSTGRES_HOST;
    const port = parseInt(String(settings.POSTGRES_PORT));

    const pool = new Pool({
        database: dbname,
        user: user,
        password: DB_PASSWORD,
        host: host,
        port: port,
        max: 20,
        idleTimeoutMillis: 30000,
        connectionTimeoutMillis: 2000,
    });

    try {
        console.log('🚀 Démarrage du script importation joueurs Counter-Strike...');
        console.log(`⏰ Heure actuelle : ${new Date().toLocaleString()}\n`);
        const ok = await importPlayers(pool);
        if (ok) {
            console.log('✅ Import réussi');
            process.exit(0);
        } else {
            console.error('❌ Import échoué');
            process.exit(1);
        }
    } catch (e: any) {
        console.error(`❌ Erreur: ${e.message}`);
        process.exit(1);
    } finally {
        await pool.end();
    }
}

// Gestion de l'arrêt gracieux
process.on('SIGINT', async () => {
    console.log('\n🛑 Arrêt...');
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n🛑 Arrêt...');
    process.exit(0);
});

// Démarrer le script
if (process.argv[1] === fileURLToPath(import.meta.url)) {
    main().catch((e) => {
        console.error('❌ Erreur fatale:', e.message);
        process.exit(1);
    });
}
