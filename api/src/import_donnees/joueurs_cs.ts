import { Pool } from 'pg';
import axios from 'axios';
import * as fs from 'fs';
import * as path from 'path';

// Import des variables d'environnement
import * as dotenv from 'dotenv';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.join(__dirname, '..', '..', '.env') });

const API_KEY_PLAYER = process.env.LIQUIPEDIA_API_KEY_PLAYER;
const DB_PASSWORD = process.env.POSTGRES_PASSWORD;
const dbname = process.env.POSTGRES_DB;
const user = process.env.POSTGRES_USER;
const host = process.env.POSTGRES_HOST;
const port = parseInt(process.env.POSTGRES_PORT || '5432');

console.log("🚀 Démarrage du script importation joueurs Counter-Strike...");
console.log(`⏰ Heure actuelle : ${new Date().toLocaleString()}`);

// Vérification des variables d'environnement
try {
    if (!API_KEY_PLAYER || !DB_PASSWORD || !dbname || !user || !host) {
        throw new Error("Variables d'environnement manquantes");
    }
    console.log(`✅ Variables d'environnement chargées`);
    console.log(`   - API_KEY: ***${API_KEY_PLAYER.slice(-10)}`);
    console.log(`   - Base: ${dbname}`);
    console.log(`   - Host: ${host}:${port}`);
} catch (e) {
    console.error(`❌ Erreur lors du chargement des variables : ${e}`);
    process.exit(1);
}

// URL de l'API Liquipedia
const url = "https://api.liquipedia.net/api/v3/player";
console.log(`📡 URL API : ${url}`);

// En-têtes pour la requête API
const headers = {
    "Authorization": `Apikey ${API_KEY_PLAYER}`,
    "Accept": "application/json",
    "User-Agent": "CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)"
};

// Pool de connexion PostgreSQL
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

// Fonction pour ajouter un délai
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Fonction pour mettre à jour la table des joueurs
async function updatePlayers(): Promise<boolean> {
    console.log("\n" + "=".repeat(60));
    console.log("🔄 DÉBUT DE LA MISE À JOUR DES JOUEURS");
    console.log(`⏰ ${new Date().toLocaleString()}`);
    console.log("=".repeat(60));

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

        console.log(`\n💾 ${totalInserted} joueurs insérés avec succès !`);
        return true;
    } catch (e: any) {
        console.error(`❌ Erreur connexion BDD: ${e.message}`);
        return false;
    } finally {
        client.release();
    }
}

// Fonction principale pour exécuter la mise à jour une seule fois
async function main() {
    console.log("\n📥 Démarrage de l'importation des joueurs...\n");

    console.log("   ✅ Exécution de updatePlayers()...");
    try {
        const result = await updatePlayers();
        if (result) {
            console.log("   ✅ Mise à jour réussie!");
            await pool.end();
            process.exit(0);
        } else {
            console.log("   ⚠️ Mise à jour échouée!");
            await pool.end();
            process.exit(1);
        }
    } catch (e: any) {
        console.error(`   ❌ Erreur lors de l'exécution: ${e.message}`);
        await pool.end();
        process.exit(1);
    }
}

// Gestion de l'arrêt gracieux
process.on('SIGINT', async () => {
    console.log('\n\n🛑 SIGINT détecté - Arrêt du script...');
    await pool.end();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n\n🛑 SIGTERM détecté - Arrêt du script...');
    await pool.end();
    process.exit(0);
});

// Démarrer le script
main().catch((e) => {
    console.error('❌ Erreur fatale:', e.message);
    process.exit(1);
});
