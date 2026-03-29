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

// URL de l'API Liquipedia
const url: string = 'https://api.liquipedia.net/api/v3/match';

// En-têtes pour la requête API
const headers: Record<string, string> = {
    Authorization: `Apikey ${settings.API_KEY_MATCHES}`,
    Accept: 'application/json',
    'User-Agent': 'CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)'
};

// Configuration PostgreSQL (utilisée uniquement pour l'exécution directe du script)
function createPool(): Pool {
    return new Pool({
        database: dbname,
        user: user,
        password: DB_PASSWORD,
        host: host,
        port: port,
        max: 20,
        idleTimeoutMillis: 30000,
        connectionTimeoutMillis: 2000
    });
}

// Variable globale pour la pool (utilisée lors de l'exécution directe)
let pool: Pool;

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

// Fonction exportée pour l'import des matches (utilisée par le seed)
export async function importMatches(pool: Pool): Promise<boolean> {
    try {
        console.log('\n' + '='.repeat(60));
        console.log('📥 IMPORT DES MATCHES DEPUIS L\'API LIQUIPEDIA');
        console.log('='.repeat(60));

        // Récupérer les matches from API
        const matchesData: match[] = [];
        const MAX_MATCHES: number = 1000;
        const params: PaginationParams = {
            wiki: 'counterstrike',
            conditions: '([[liquipediatier::1]] OR [[liquipediatier::2]] OR [[liquipediatier::3]]) AND [[date::>2020-01-01]]',
            limit: 100,
            offset: 0
        };

        let page: number = 1;
        let totalFetched: number = 0;

        // Pagination pour récupérer tous les matchs
        while (totalFetched < MAX_MATCHES) {
            console.log(`\n📄 Page ${page} - Offset: ${params.offset}`);

            try {
                const response = await axios.get<{ result: match[] }>(url, {
                    headers: headers,
                    params: params,
                    timeout: 30000
                });

                if (response.status === 200) {
                    const data: match[] = response.data.result || [];
                    console.log(`   ✅ ${data.length} matchs récupérés`);

                    if (data.length === 0) {
                        console.log('   ✋ Fin de la pagination');
                        break;
                    }

                    const remainingSlots = MAX_MATCHES - totalFetched;
                    const dataToAdd = data.slice(0, remainingSlots);
                    matchesData.push(...dataToAdd);
                    totalFetched += dataToAdd.length;

                    if (totalFetched >= MAX_MATCHES) {
                        console.log(`   ⚠️ Limite de ${MAX_MATCHES} matchs atteinte`);
                        break;
                    }

                    params.offset += params.limit;
                    await sleep(2000);
                    page++;
                }
            } catch (error: any) {
                if (error.response?.status === 429) {
                    console.log('   ⏱️ Rate limit (429) - attente 2h...');
                    await sleep(7200000);
                    continue;
                } else {
                    console.error(`   ❌ Erreur: ${error.message}`);
                    break;
                }
            }
        }

        console.log(`\n📊 Total récupéré: ${totalFetched}`);

        // Insertion dans la base de données
        const client: PoolClient = await pool.connect();

        try {
            console.log('\n🔌 Connexion à la base de données...');
            console.log('✅ Connecté à PostgreSQL\n');

            let totalInserted: number = 0;

            for (const match of matchesData) {
                try {
                    const checkQuery = 'SELECT 1 FROM match WHERE pageid = $1';
                    const existsResult = await client.query(checkQuery, [match.pageid]);

                    if (existsResult.rows.length > 0) {
                        console.log(`   ℹ️ ${match.objectname} existe déjà`);
                        continue;
                    }

                    let dateValue: string | null = match.date || null;
                    if (dateValue === '0000-01-01' || !dateValue || dateValue.startsWith('0000-')) {
                        dateValue = null;
                    }

                    let finished: boolean | null = match.finished || false;
                    let dateexact: boolean | null = match.dateexact || false;

                    const query = `
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
                    console.error(`   ❌ Erreur: ${match.objectname}: ${error.message}`);
                }
            }

            console.log(`\n💾 ${totalInserted} matchs insérés`);

            // Lier les équipes aux matches using the provided pool's client
            await linkTeamsToMatchesExport(client);

            console.log('='.repeat(60));
            console.log('✅ IMPORT TERMINÉ');
            console.log('='.repeat(60) + '\n');

            return true;
        } finally {
            client.release();
        }
    } catch (error: any) {
        console.error(`❌ Erreur: ${error.message}`);
        return false;
    }
}

// Fonction pour lier les équipes aux matches (version pour export)
async function linkTeamsToMatchesExport(client: PoolClient): Promise<void> {
    console.log('\n' + '='.repeat(60));
    console.log('🔗 LIAISON DES ÉQUIPES AUX MATCHES');
    console.log('='.repeat(60));

    try {
        const matchesWithoutTeams = await client.query(`
            SELECT pageid, match2opponents FROM match 
            WHERE team1_id IS NULL AND match2opponents IS NOT NULL
            ORDER BY pageid DESC
        `);

        console.log(`   📋 ${matchesWithoutTeams.rows.length} matches à traiter`);

        let linked = 0;
        let skipped = 0;

        for (const row of matchesWithoutTeams.rows) {
            try {
                const opponents = typeof row.match2opponents === 'string' 
                    ? JSON.parse(row.match2opponents) 
                    : row.match2opponents;
                
                if (!Array.isArray(opponents) || opponents.length < 2) {
                    skipped++;
                    continue;
                }

                const team1Name = opponents[0].name?.trim();
                const team2Name = opponents[1].name?.trim();

                if (!team1Name || !team2Name) {
                    skipped++;
                    continue;
                }

                // Récupérer les IDs des équipes (recherche exacte + insensible à la casse)
                let team1Result = await client.query(
                    'SELECT pageid FROM team WHERE LOWER(name) = LOWER($1) LIMIT 1',
                    [team1Name]
                );
                let team2Result = await client.query(
                    'SELECT pageid FROM team WHERE LOWER(name) = LOWER($1) LIMIT 1',
                    [team2Name]
                );

                // Si pas de correspondance exacte, essayer avec LIKE (recherche flexible)
                if (team1Result.rows.length === 0) {
                    team1Result = await client.query(
                        'SELECT pageid FROM team WHERE LOWER(name) LIKE LOWER($1) LIMIT 1',
                        [`%${team1Name}%`]
                    );
                }
                if (team2Result.rows.length === 0) {
                    team2Result = await client.query(
                        'SELECT pageid FROM team WHERE LOWER(name) LIKE LOWER($1) LIMIT 1',
                        [`%${team2Name}%`]
                    );
                }

                // Essayer aussi une recherche inverse (le nom de la DB contient le nom de l'API)
                if (team1Result.rows.length === 0) {
                    team1Result = await client.query(
                        'SELECT pageid FROM team WHERE $1 LIKE LOWER(name) || \'%\' LIMIT 1',
                        [team1Name.toLowerCase()]
                    );
                }
                if (team2Result.rows.length === 0) {
                    team2Result = await client.query(
                        'SELECT pageid FROM team WHERE $1 LIKE LOWER(name) || \'%\' LIMIT 1',
                        [team2Name.toLowerCase()]
                    );
                }

                if (team1Result.rows.length === 0 || team2Result.rows.length === 0) {
                    skipped++;
                    continue;
                }

                const team1Id = team1Result.rows[0].pageid;
                const team2Id = team2Result.rows[0].pageid;

                await client.query(
                    'UPDATE match SET team1_id = $1, team2_id = $2 WHERE pageid = $3',
                    [team1Id, team2Id, row.pageid]
                );

                linked++;
                console.log(`   ✅ Match ${row.pageid}: lié`);
            } catch (error: any) {
                console.error(`   ❌ Erreur: ${error.message}`);
                skipped++;
            }
        }

        console.log(`\n   📊 ${linked} liés, ${skipped} ignorés`);
    } catch (error: any) {
        console.error(`   ❌ Erreur: ${error.message}`);
    }
}

// Fonction pour importer les matches d'un tournoi spécifique
async function importTournamentMatches(tournamentName: string): Promise<void> {
    console.log('\n' + '='.repeat(60));
    console.log(`🏆 IMPORT DES MATCHES POUR: ${tournamentName}`);
    console.log('='.repeat(60));

    const matchesData: match[] = [];
    const params: PaginationParams = {
        wiki: 'counterstrike',
        conditions: `[[tournament::${tournamentName}]]`,
        limit: 100,
        offset: 0
    };

    let page: number = 1;
    let totalFetched: number = 0;
    const MAX_MATCHES: number = 1000;

    try {
        // Pagination pour récupérer tous les matchs du tournoi
        while (totalFetched < MAX_MATCHES) {
            console.log(`\n📄 Page ${page} - Offset: ${params.offset}`);

            try {
                const response = await axios.get<{ result: match[] }>(url, {
                    headers: headers,
                    params: params,
                    timeout: 30000
                });

                if (response.status === 200) {
                    const data: match[] = response.data.result || [];
                    console.log(`   ✅ ${data.length} matchs récupérés`);

                    if (data.length === 0) {
                        console.log('   ✋ Fin de la pagination');
                        break;
                    }

                    matchesData.push(...data);
                    totalFetched += data.length;

                    if (totalFetched >= MAX_MATCHES) {
                        console.log(`   ⚠️ Limite de ${MAX_MATCHES} matchs atteinte`);
                        break;
                    }

                    params.offset += params.limit;
                    await sleep(2000);
                    page++;
                }
            } catch (error: any) {
                if (error.response?.status === 429) {
                    console.log('   ⏱️ Rate limit (429) - attente 2h...');
                    await sleep(7200000);
                    continue;
                } else {
                    console.error(`   ❌ Erreur: ${error.message}`);
                    break;
                }
            }
        }

        console.log(`\n📊 Total récupéré: ${totalFetched}`);

        // Insertion dans la base de données
        const client: PoolClient = await pool.connect();

        try {
            let insertedCount = 0;

            for (const match of matchesData) {
                try {
                    // Vérifier si existe déjà
                    const checkQuery = 'SELECT 1 FROM match WHERE pageid = $1';
                    const existsResult = await client.query(checkQuery, [match.pageid]);

                    if (existsResult.rows.length > 0) {
                        console.log(`   ℹ️  ${match.objectname} existe déjà`);
                        continue;
                    }

                    let dateValue: string | null = match.date || null;
                    if (dateValue === '0000-01-01' || !dateValue || dateValue.startsWith('0000-')) {
                        dateValue = null;
                    }

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
                status = EXCLUDED.status,
                winner = EXCLUDED.winner,
                walkover = EXCLUDED.walkover,
                resulttype = EXCLUDED.resulttype,
                finished = EXCLUDED.finished,
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
                    insertedCount++;
                    console.log(`   ✅ ${match.objectname} inséré`);
                } catch (error: any) {
                    console.error(`   ❌ Erreur: ${match.objectname}: ${error.message}`);
                }
            }

            console.log(`\n💾 ${insertedCount} matchs insérés`);

            // Lier les équipes
            await linkTeamsToMatches(client);

            console.log('='.repeat(60) + '\n');
        } finally {
            client.release();
        }
    } catch (error: any) {
        console.error(`❌ Erreur globale: ${error.message}`);
    }
}

// Fonction pour lier les équipes aux matches
async function linkTeamsToMatches(client: PoolClient): Promise<void> {
    console.log('\n' + '='.repeat(60));
    console.log('🔗 LIAISON DES ÉQUIPES AUX MATCHES');
    console.log('='.repeat(60));

    try {
        // Récupérer tous les matches qui n'ont pas team1_id rempli
        const matchesWithoutTeams = await client.query(`
            SELECT pageid, match2opponents FROM match 
            WHERE team1_id IS NULL AND match2opponents IS NOT NULL
            ORDER BY pageid DESC
        `);

        console.log(`   📋 ${matchesWithoutTeams.rows.length} matches à traiter`);

        let linked = 0;
        let skipped = 0;

        for (const row of matchesWithoutTeams.rows) {
            try {
                // match2opponents peut être un objet parsé ou une chaîne JSON
                const opponents = typeof row.match2opponents === 'string' 
                    ? JSON.parse(row.match2opponents) 
                    : row.match2opponents;
                
                if (!Array.isArray(opponents) || opponents.length < 2) {
                    console.log(`   ⏭️  Match ${row.pageid}: format invalide`);
                    skipped++;
                    continue;
                }

                const team1Name = opponents[0].name?.trim();
                const team2Name = opponents[1].name?.trim();

                if (!team1Name || !team2Name) {
                    console.log(`   ⏭️  Match ${row.pageid}: noms d'équipes manquants`);
                    skipped++;
                    continue;
                }

                // Récupérer les IDs des équipes (recherche exacte + insensible à la casse)
                let team1Result = await client.query(
                    'SELECT pageid FROM team WHERE LOWER(name) = LOWER($1) LIMIT 1',
                    [team1Name]
                );
                let team2Result = await client.query(
                    'SELECT pageid FROM team WHERE LOWER(name) = LOWER($1) LIMIT 1',
                    [team2Name]
                );

                // Si pas de correspondance exacte, essayer avec LIKE (recherche flexible)
                if (team1Result.rows.length === 0) {
                    team1Result = await client.query(
                        'SELECT pageid FROM team WHERE LOWER(name) LIKE LOWER($1) LIMIT 1',
                        [`%${team1Name}%`]
                    );
                }
                if (team2Result.rows.length === 0) {
                    team2Result = await client.query(
                        'SELECT pageid FROM team WHERE LOWER(name) LIKE LOWER($1) LIMIT 1',
                        [`%${team2Name}%`]
                    );
                }

                // Essayer aussi une recherche inverse (le nom de la DB contient le nom de l'API)
                if (team1Result.rows.length === 0) {
                    team1Result = await client.query(
                        'SELECT pageid FROM team WHERE $1 LIKE LOWER(name) || \'%\' LIMIT 1',
                        [team1Name.toLowerCase()]
                    );
                }
                if (team2Result.rows.length === 0) {
                    team2Result = await client.query(
                        'SELECT pageid FROM team WHERE $1 LIKE LOWER(name) || \'%\' LIMIT 1',
                        [team2Name.toLowerCase()]
                    );
                }

                if (team1Result.rows.length === 0 || team2Result.rows.length === 0) {
                    console.log(`   ⏭️  Match ${row.pageid}: équipes non trouvées (${team1Name} vs ${team2Name})`);
                    skipped++;
                    continue;
                }

                const team1Id = team1Result.rows[0].pageid;
                const team2Id = team2Result.rows[0].pageid;

                // Lier les équipes au match
                await client.query(
                    'UPDATE match SET team1_id = $1, team2_id = $2 WHERE pageid = $3',
                    [team1Id, team2Id, row.pageid]
                );

                linked++;
                console.log(`   ✅ Match ${row.pageid}: ${team1Name} (${team1Id}) vs ${team2Name} (${team2Id})`);
            } catch (error: any) {
                console.error(`   ❌ Erreur pour match ${row.pageid}: ${error.message}`);
                skipped++;
            }
        }

        console.log(`\n   📊 Résumé: ${linked} liés, ${skipped} ignorés`);
        console.log('='.repeat(60) + '\n');
    } catch (error: any) {
        console.error(`   ❌ Erreur globale de liaison: ${error.message}`);
    }
}

// Fonction pour mettre à jour les matchs
async function updateMatches(): Promise<void> {
    console.log('\n' + '='.repeat(60));
    console.log('🔄 DÉBUT DE LA MISE À JOUR DES MATCHS');
    console.log(`⏰ ${new Date().toLocaleString()}`);
    console.log('='.repeat(60));

    const matchesData: match[] = [];
    const MAX_MATCHES: number = 1000;
    const params: PaginationParams = {
        wiki: 'counterstrike',
        conditions: '([[liquipediatier::1]] OR [[liquipediatier::2]] OR [[liquipediatier::3]]) AND [[date::>2020-01-01]]',
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
                    timeout: 30000
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
                    console.log('   ⏱️ Rate limit détecté (429) - attente 2h...');
                    await sleep(7200000);
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

            // Lier les équipes aux matches
            await linkTeamsToMatches(client);

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
    // Créer la pool pour l'exécution directe
    pool = createPool();

    try {
        // Vérifier si un argument est fourni pour importer un tournoi spécifique
        const args = process.argv.slice(2);
        
        if (args.length > 0) {
            // Mode: import d'un tournoi spécifique
            const tournamentName = args.join(' ');
            console.log(`\n🎯 Mode spécifique: import du tournoi "${tournamentName}"`);
            await importTournamentMatches(tournamentName);
        } else {
            // Mode: import général
            console.log('\n🎯 Mode général: import de tous les matchs');
            await updateMatches();
        }
        
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
