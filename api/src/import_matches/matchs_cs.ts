import { Pool, type PoolClient } from 'pg';
import axios, { AxiosError } from 'axios';
import * as dotenv from 'dotenv';
import * as path from 'path';
import { fileURLToPath } from 'url';
import settings from './settings.js';

// Créer __dirname en ESM
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Charger les variables d'environnement
dotenv.config({ path: path.join(__dirname, '..', '..', '.env') });

const API_KEY_MATCHES: string = settings.API_KEY_MATCHES;
const DB_PASSWORD: string = (settings.POSTGRES_PASSWORD);
const dbname: string = (settings.POSTGRES_DB);
const user: string = (settings.POSTGRES_USER);
const host: string = (settings.POSTGRES_HOST);
const port: number = parseInt(String(settings.POSTGRES_PORT));

console.log('🚀 Démarrage du script importation matchs Counter-Strike...');
console.log(`⏰ Heure actuelle : ${new Date().toLocaleString()}`);

// URL de l'API Liquipedia
const url: string = 'https://api.liquipedia.net/api/v3/match';

// En-têtes pour la requête API
const headers: Record<string, string> = {
    Authorization: `Apikey ${settings.API_KEY_MATCHES}`,
    Accept: 'application/json',
    'User-Agent': 'CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)'
};

// Configuration PostgreSQL
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

// Interface pour les données de match
interface match {
    pageid: number;
    pagename?: string;
    namespace?: number;
    objectname?: string;
    match2id?: string;
    match2bracketid?: string;
    status?: string;
    winner?: string;
    walkover?: string;
    resulttype?: string;
    finished?: boolean;
    mode?: string;
    type?: string;
    section?: string;
    game?: string;
    patch?: string;
    links?: Record<string, string>;
    bestof?: number;
    date?: string;
    dateexact?: boolean;
    stream?: Record<string, any>;
    vod?: string;
    tournament?: string;
    parent?: string;
    tickername?: string;
    shortname?: string;
    series?: string;
    icon?: string;
    iconurl?: string;
    icondark?: string;
    icondarkurl?: string;
    liquipediatier?: string;
    liquipediatiertype?: string;
    publishertier?: string;
    extradata?: Record<string, any>;
    match2bracketdata?: Record<string, any>;
    match2games?: Record<string, any>;
    match2opponents?: Record<string, any>;
    wiki?: string;
}

// Interface pour les paramètres de pagination
interface PaginationParams {
    wiki: string;
    conditions: string;
    limit: number;
    offset: number;
}

// Fonction utilitaire pour pause
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Fonction pour mettre à jour les matchs
async function updateMatches(): Promise<void> {
    console.log('\n' + '='.repeat(60));
    console.log('🔄 DÉBUT DE LA MISE À JOUR DES MATCHS');
    console.log(`⏰ ${new Date().toLocaleString()}`);
    console.log('='.repeat(60));

    const matchesData: match[] = [];
    const MAX_MATCHES: number = 5000;
    const params: PaginationParams = {
        wiki: 'counterstrike',
        conditions: '[[liquipediatier::1]] AND [[date::>2020-01-01]]',
        limit: 100,
        offset: 0
    };

    let page: number = 1;
    let totalFetched: number = 0;

    try {
        // Pagination pour récupérer tous les matchs (max 5000)
        while (totalFetched < MAX_MATCHES) {
            console.log(`\n📄 Page ${page} - Offset: ${params.offset}`);

            try {
                const response = await axios.get<{ result: match[] }>(url, {
                    headers: headers,
                    params: params,
                    timeout: 10000
                });

                console.log(`   Status Code: ${response.status}`);

                if (response.status === 200) {
                    const data: match[] = response.data.result || [];
                    console.log(`   ✅ ${data.length} matchs récupérés`);

                    if (data.length === 0) {
                        console.log('   ✋ Fin de la pagination (aucune donnée)');
                        break;
                    }

                    // Ne pas dépasser la limite de 5000
                    const remainingSlots = MAX_MATCHES - totalFetched;
                    const dataToAdd = data.slice(0, remainingSlots);

                    matchesData.push(...dataToAdd);
                    totalFetched += dataToAdd.length;

                    // Arrêter si on a atteint la limite
                    if (totalFetched >= MAX_MATCHES) {
                        console.log(`   ⚠️ Limite de ${MAX_MATCHES} matchs atteinte`);
                        break;
                    }

                    params.offset += params.limit;
                    console.log(`   → Prochain offset: ${params.offset}`);

                    // Anti rate-limit
                    await sleep(2000);
                    page++;
                }
            } catch (error: any) {
                if (error.response?.status === 429) {
                    console.log('   ⏱️ Rate limit détecté (429) - attente 60s...');
                    await sleep(60000);
                    continue;
                } else {
                    console.error(`   ❌ Erreur requête : ${error.message}`);
                    break;
                }
            }
        }

        console.log(`\n📊 Total de matchs récupérés : ${totalFetched}`);

        // Connexion à la base de données PostgreSQL et insertion
        const client: PoolClient = await pool.connect();

        try {
            console.log('\n🔌 Connexion à la base de données...');
            console.log('✅ Connecté à PostgreSQL\n');

            let totalInserted: number = 0;

            for (const match of matchesData) {
                try {
                    // Vérifier si le match existe déjà
                    const checkQuery: string = 'SELECT 1 FROM match WHERE pageid = $1';
                    const existsResult = await client.query(checkQuery, [match.pageid]);

                    if (existsResult.rows.length > 0) {
                        console.log(`   ℹ️ Le match ${match.objectname} existe déjà, skip`);
                        continue;
                    }

                    // Gestion des dates invalides
                    let dateValue: string | null = match.date || null;
                    if (dateValue === '0000-01-01' || !dateValue || dateValue.startsWith('0000-')) {
                        dateValue = null;
                    }

                    // Gestion des booléens
                    let finished: boolean | null = match.finished || false;
                    let dateexact: boolean | null = match.dateexact || false;

                    const query: string = `
            INSERT INTO match (
                pageid, pagename, namespace, objectname, match2id, match2bracketid, status, winner, walkover, 
                resulttype, finished, mode, type, section, game, patch, links, bestof, date, dateexact, 
                stream, vod, tournament, parent, tickername, shortname, series, icon, iconurl, icondark, 
                icondarkurl, liquipediatier, liquipediatiertype, publishertier, extradata, match2bracketdata, 
                match2games, match2opponents
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, 
                $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31, $32, $33, $34, $35, $36, $37, $38
            ) ON CONFLICT (pageid) DO UPDATE SET
                pagename = EXCLUDED.pagename,
                namespace = EXCLUDED.namespace,
                match2id = EXCLUDED.match2id,
                match2bracketid = EXCLUDED.match2bracketid,
                status = EXCLUDED.status,
                winner = EXCLUDED.winner,
                walkover = EXCLUDED.walkover,
                resulttype = EXCLUDED.resulttype,
                finished = EXCLUDED.finished,
                mode = EXCLUDED.mode,
                type = EXCLUDED.type,
                section = EXCLUDED.section,
                game = EXCLUDED.game,
                patch = EXCLUDED.patch,
                links = EXCLUDED.links,
                bestof = EXCLUDED.bestof,
                date = EXCLUDED.date,
                dateexact = EXCLUDED.dateexact,
                stream = EXCLUDED.stream,
                vod = EXCLUDED.vod,
                tournament = EXCLUDED.tournament,
                parent = EXCLUDED.parent,
                tickername = EXCLUDED.tickername,
                shortname = EXCLUDED.shortname,
                series = EXCLUDED.series,
                icon = EXCLUDED.icon,
                iconurl = EXCLUDED.iconurl,
                icondark = EXCLUDED.icondark,
                icondarkurl = EXCLUDED.icondarkurl,
                liquipediatier = EXCLUDED.liquipediatier,
                liquipediatiertype = EXCLUDED.liquipediatiertype,
                publishertier = EXCLUDED.publishertier,
                extradata = EXCLUDED.extradata,
                match2bracketdata = EXCLUDED.match2bracketdata,
                match2games = EXCLUDED.match2games,
                match2opponents = EXCLUDED.match2opponents;
          `;

                    const values: any[] = [
                        match.pageid,
                        match.pagename,
                        match.namespace,
                        match.objectname,
                        match.match2id,
                        match.match2bracketid,
                        match.status,
                        match.winner,
                        match.walkover,
                        match.resulttype,
                        finished,
                        match.mode,
                        match.type,
                        match.section,
                        match.game,
                        match.patch,
                        JSON.stringify(match.links || {}),
                        match.bestof,
                        dateValue,
                        dateexact,
                        JSON.stringify(match.stream || {}),
                        match.vod,
                        match.tournament,
                        match.parent,
                        match.tickername,
                        match.shortname,
                        match.series,
                        match.icon,
                        match.iconurl,
                        match.icondark,
                        match.icondarkurl,
                        match.liquipediatier,
                        match.liquipediatiertype,
                        match.publishertier,
                        JSON.stringify(match.extradata || {}),
                        JSON.stringify(match.match2bracketdata || {}),
                        JSON.stringify(match.match2games || {}),
                        JSON.stringify(match.match2opponents || {})
                    ];

                    await client.query(query, values);
                    totalInserted++;
                    console.log(`   ✅ ${match.objectname} inséré`);
                } catch (error: any) {
                    console.error(`   ❌ Erreur insertion ${match.objectname}: ${error.message}`);
                    continue;
                }
            }

            console.log(`\n💾 ${totalInserted} matchs insérés avec succès !`);

            console.log('='.repeat(60));
            console.log('✅ MISE À JOUR TERMINÉE');
            console.log('='.repeat(60) + '\n');
        } finally {
            client.release();
        }
    } catch (error: any) {
        console.error(`❌ Erreur globale : ${error.message}`);
    }
}

// Fonction principale pour exécuter la mise à jour une seule fois
async function main(): Promise<void> {
    try {
        await updateMatches();
        await pool.end();
        process.exit(0);
    } catch (error: any) {
        console.error(`❌ Erreur lors de l'exécution: ${error.message}`);
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
