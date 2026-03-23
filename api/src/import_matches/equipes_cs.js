const { Pool } = require('pg');
const axios = require('axios');
const dotenv = require('dotenv');
const path = require('path');
const { API_KEY_TEAM } = require('./settings');

// Charger les variables d'environnement
dotenv.config({ path: path.join(__dirname, '.env') });

const API_KEY = process.env.API_KEY;
const DB_PASSWORD = process.env.DB_PASSWORD;
const dbname = process.env.dbname || 'postgres';
const user = process.env.user || 'user';
const host = process.env.host || '51.38.38.87';

console.log('🚀 Démarrage du script importation équipes Counter-Strike...');
console.log(`⏰ Heure actuelle : ${new Date().toLocaleString()}`);

// URL de l'API Liquipedia
const url = 'https://api.liquipedia.net/api/v3/team';

// En-têtes pour la requête API
const headers = {
    'Authorization': `Apikey ${API_KEY_TEAM}`,
    'Accept': 'application/json',
    'User-Agent': 'CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)'
};

// Configuration PostgreSQL
const pool = new Pool({
    database: dbname,
    user: user,
    password: DB_PASSWORD,
    host: host,
    port: 5439
});

// Fonction pour mettre à jour les équipes
async function updateTeams() {
    console.log('\n' + '='.repeat(60));
    console.log('🔄 DÉBUT DE LA MISE À JOUR DES ÉQUIPES');
    console.log(`⏰ ${new Date().toLocaleString()}`);
    console.log('='.repeat(60));

    const teamsData = [];
    const params = {
        wiki: 'counterstrike',
        limit: 100,
        offset: 0
    };

    let page = 1;
    let totalFetched = 0;

    try {
        // Pagination pour récupérer toutes les équipes
        while (true) {
            console.log(`\n📄 Page ${page} - Offset: ${params.offset}`);
            
            try {
                const response = await axios.get(url, {
                    headers: headers,
                    params: params,
                    timeout: 10000
                });

                console.log(`   Status Code: ${response.status}`);

                if (response.status === 200) {
                    const data = response.data.result || [];
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
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    page++;
                }
            } catch (error) {
                if (error.response && error.response.status === 429) {
                    console.log('   ⏱️ Rate limit détecté (429) - attente 60s...');
                    await new Promise(resolve => setTimeout(resolve, 60000));
                    continue;
                } else {
                    console.log(`   ❌ Erreur requête : ${error.message}`);
                    break;
                }
            }
        }

        console.log(`\n📊 Total d'équipes récupérées : ${totalFetched}`);

        // Connexion à la base de données PostgreSQL et insertion
        const client = await pool.connect();
        
        try {
            console.log('\n🔌 Connexion à la base de données...');
            console.log('✅ Connecté à PostgreSQL\n');

            let totalInserted = 0;
            let totalSkipped = 0;

            for (const team of teamsData) {
                try {
                    // Vérifier si l'équipe existe déjà
                    const checkQuery = 'SELECT 1 FROM teams WHERE pageid = $1';
                    const existsResult = await client.query(checkQuery, [team.pageid]);

                    if (existsResult.rows.length > 0) {
                        console.log(`   ℹ️ L'équipe ${team.name} existe déjà, skip`);
                        totalSkipped++;
                        continue;
                    }

                    // Gestion des dates invalides
                    let createdate = team.createdate;
                    if (createdate === '0000-01-01' || !createdate) {
                        createdate = null;
                    }

                    let disbanddate = team.disbanddate;
                    if (disbanddate === '0000-01-01' || !disbanddate) {
                        disbanddate = null;
                    }

                    // Gestion des earnings
                    let earnings = team.earnings;
                    if (earnings) {
                        try {
                            earnings = parseInt(earnings);
                        } catch (e) {
                            earnings = null;
                        }
                    }

                    const query = `
                        INSERT INTO teams (
                            pageid, pagename, namespace, objectname, name, locations, region, logo, logourl, logodark, 
                            logodarkurl, textlesslogourl, textlesslogodarkurl, status, createdate, disbanddate, earnings, 
                            earningsbyyear, template, links, extradata
                        ) VALUES (
                            $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21
                        ) ON CONFLICT (pageid) DO NOTHING;
                    `;

                    const values = [
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

                } catch (error) {
                    console.log(`   ❌ Erreur insertion ${team.name}: ${error.message}`);
                    continue;
                }
            }

            console.log(`\n💾 ${totalInserted} équipes insérées avec succès !`);
            console.log(`⏭️ ${totalSkipped} équipes déjà présentes\n`);

            console.log('='.repeat(60));
            console.log('✅ MISE À JOUR TERMINÉE');
            console.log('='.repeat(60) + '\n');

        } finally {
            client.release();
        }

    } catch (error) {
        console.log(`❌ Erreur globale : ${error.message}`);
    }
}

// Boucle infinie pour exécuter la mise à jour une fois par mois
async function main() {
    console.log('\n🔁 Démarrage de la boucle de vérification...');
    console.log('   Exécution toutes les 24 heures\n');

    let iteration = 0;
    
    while (true) {
        iteration++;
        const now = new Date();
        console.log(`[ITÉRATION ${iteration}] ⏰ ${now.toLocaleString()}`);
        
        try {
            await updateTeams();
        } catch (error) {
            console.log(`   ❌ Erreur lors de l'exécution: ${error.message}`);
        }

        console.log('   ⏳ Attente 24 heures avant prochaine vérification...\n');
        
        // Attendre 24 heures
        await new Promise(resolve => setTimeout(resolve, 86400000)); // 24 heures en ms
    }
}

// Gestion Ctrl+C
process.on('SIGINT', () => {
    console.log('\n🛑 Arrêt du service...');
    pool.end();
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\n🛑 Arrêt du service...');
    pool.end();
    process.exit(0);
});

// Démarrage
main().catch(error => {
    console.log(`❌ Erreur non gérée : ${error.message}`);
    pool.end();
    process.exit(1);
});
