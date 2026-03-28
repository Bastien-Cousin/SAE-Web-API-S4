import { Pool, type PoolClient } from 'pg';
import axios from 'axios';
import settings from './settings.js';
import { fileURLToPath } from 'url';

// API configuration
const API_URL: string = 'https://api.liquipedia.net/api/v3/match';
const getHeaders = (): Record<string, string> => ({
    Authorization: `Apikey ${settings.API_KEY_MATCHES}`,
    Accept: 'application/json',
    'User-Agent': 'CounterStrikeUniversityProject/1.0 (Counter Strike stats; pierredeldalle.pro@gmail.com)'
});

// Interface pour les données de match
interface Match {
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
    links?: Record<string, any>;
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

// Fonction utilitaire pour pause
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Fonction pour insérer/mettre à jour les matchs
async function updateMatches(client: PoolClient, matches: Match[]): Promise<number> {
    let insertedCount = 0;

    for (const match of matches) {
        try {
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
                )
                ON CONFLICT (objectname) DO UPDATE SET
                    pageid = EXCLUDED.pageid,
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
            insertedCount++;
            console.log(`   ✅ ${match.objectname} créé/mis à jour`);

            // Vérifier le count après insertion
            const countResult = await client.query('SELECT COUNT(*) FROM match;');
            console.log(`   📊 Total dans DB: ${countResult.rows[0].count}`);
        } catch (error: any) {
            console.error(`   ❌ Erreur ${match.objectname}: ${error.message}`);
        }
    }

    return insertedCount;
}

// Fonction pour importer les matches d'un tournoi spécifique
async function importTournamentMatches(tournamentIdOrMatchId: string, pool: Pool): Promise<void> {
    console.log('\n' + '='.repeat(60));
    console.log(`🏆 IMPORT DES MATCHES POUR: ${tournamentIdOrMatchId}`);
    console.log('='.repeat(60));

    try {
        // Step 1: Essayer de trouver le tournoi directement
        console.log('\n📥 Recherche du tournoi...');
        
        let tournamentPagename: string | undefined;

        // Essai 1: C'est un objectname de tournoi
        let tournamentResponse = await axios.get('https://api.liquipedia.net/api/v3/tournament', {
            headers: getHeaders(),
            params: {
                wiki: 'counterstrike',
                conditions: `[[objectname::${tournamentIdOrMatchId}]]`,
                limit: 1
            },
            timeout: 10000
        });

        let tournaments = tournamentResponse.data?.result || [];

        if (tournaments.length > 0) {
            // C'est un tournoi
            const tournament = tournaments[0];
            tournamentPagename = tournament.pagename || tournament.name;
            console.log(`✅ Tournoi trouvé: ${tournamentPagename}`);
        } else {
            // Essai 2: C'est un objectname de match, extraire le tournoi
            console.log('   📝 Pas trouvé comme tournoi, checking si c\'est un match...');
            
            const matchResponse = await axios.get('https://api.liquipedia.net/api/v3/match', {
                headers: getHeaders(),
                params: {
                    wiki: 'counterstrike',
                    conditions: `[[objectname::${tournamentIdOrMatchId}]]`,
                    limit: 1
                },
                timeout: 10000
            });

            const matches = matchResponse.data?.result || [];
            if (matches.length > 0) {
                const match = matches[0];
                tournamentPagename = match.tournament;
                console.log(`✅ Match trouvé! Tournoi: ${tournamentPagename}`);
            } else {
                console.log(`❌ Aucun tournoi ou match trouvé avec: ${tournamentIdOrMatchId}`);
                return;
            }
        }

        if (!tournamentPagename) {
            console.log('❌ Impossible de déterminer le nom du tournoi');
            return;
        }

        // Step 2: Récupérer les matchs du tournoi
        console.log(`\n📥 Récupération des matchs du tournoi...`);
        
        const matchesData: Match[] = [];
        const params: any = {
            wiki: 'counterstrike',
            conditions: `[[tournament::${tournamentPagename}]]`,
            limit: 100,
            offset: 0
        };

        let page: number = 1;
        let totalFetched: number = 0;
        const MAX_MATCHES: number = 5000;

        // Pagination pour récupérer tous les matchs du tournoi
        while (totalFetched < MAX_MATCHES) {
            console.log(`\n📄 Page ${page} - Offset: ${params.offset}`);

            try {
                const response = await axios.get<{ result: Match[] }>(API_URL, {
                    headers: getHeaders(),
                    params: params,
                    timeout: 10000
                });

                if (response.status === 200) {
                    const data: Match[] = response.data.result || [];
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
                    console.log('   ⏱️ Rate limit (429) - attente 60s...');
                    await sleep(60000);
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
                    // Ne pas vérifier si existe - toujours faire l'UPSERT pour mettre à jour les données
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
                pagename = EXCLUDED.pagename,
                objectname = EXCLUDED.objectname,
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
                    insertedCount++;
                    console.log(`   ✅ ${match.objectname} créé/mis à jour`);
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
                const team1Result = await client.query(
                    'SELECT pageid FROM team WHERE LOWER(name) = LOWER($1) LIMIT 1',
                    [team1Name]
                );
                const team2Result = await client.query(
                    'SELECT pageid FROM team WHERE LOWER(name) = LOWER($1) LIMIT 1',
                    [team2Name]
                );

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

// Fonction exportée pour l'importation des matchs
export async function importMatches(pool: Pool): Promise<boolean> {
    console.log('🚀 Démarrage du script importation matchs Counter-Strike...');
    console.log(`⏰ Heure actuelle : ${new Date().toLocaleString()}`);

    const matches_data: Match[] = [];
    const MAX_MATCHES: number = 5000;
    let offset: number = 0;
    const limit: number = 100;

    const params: any = {
        wiki: 'counterstrike',
        conditions: '[[date::>2025-01-01]]',
        limit: limit,
        offset: offset
    };

    console.log('\n📥 Récupération des matchs depuis 2025...');

    try {
        while (true) {
            console.log(`\n📄 Offset: ${params.offset}, Limit: ${params.limit}`);
            console.log(`   📋 Paramètres: ${JSON.stringify(params)}`);
            
            try {
                const response = await axios.get<{ result: Match[] }>(API_URL, {
                    headers: getHeaders(),
                    params: params,
                    timeout: 10000
                });

                const data: Match[] = response.data.result || [];

                if (data.length === 0) {
                    console.log('   ✅ 0 matchs récupérés');
                    console.log('   ✋ Fin de la pagination');
                    break;
                }

                console.log(`   ✅ ${data.length} matchs récupérés`);
                matches_data.push(...data);

                if (matches_data.length >= MAX_MATCHES) {
                    console.log(`   ⚠️ Limite de ${MAX_MATCHES} matchs atteinte`);
                    break;
                }

                params.offset += params.limit;
                await sleep(2000);

            } catch (error: any) {
                if (error.response?.status === 429) {
                    console.log('   ⏱️ Rate limit (429) - pause 60s...');
                    await sleep(60000);
                    continue;
                } else {
                    console.error(`   ❌ Erreur requête: ${error.message}`);
                    break;
                }
            }
        }

        console.log(`\n📊 Total récupéré: ${matches_data.length}`);

        const client: PoolClient = await pool.connect();

        try {
            console.log('\n🔌 Connexion à PostgreSQL...\n');

            const insertedCount: number = await updateMatches(client, matches_data);
            
            console.log(`\n💾 ${insertedCount} matchs insérés`);

            await linkTeamsToMatches(client);

            console.log('='.repeat(60) + '\n');
            return true;
        } finally {
            client.release();
        }
    } catch (error: any) {
        console.error(`❌ Erreur globale: ${error.message}`);
        return false;
    }
}

// CLI Entry point for importing all matches
async function main(): Promise<void> {
    const dbPassword: string = settings.POSTGRES_PASSWORD;
    const dbname: string = settings.POSTGRES_DB;
    const user: string = settings.POSTGRES_USER;
    const host: string = settings.POSTGRES_HOST;
    const port: number = parseInt(String(settings.POSTGRES_PORT));

    const pool = new Pool({
        database: dbname,
        user: user,
        password: dbPassword,
        host: host,
        port: port,
        max: 20,
        idleTimeoutMillis: 30000,
        connectionTimeoutMillis: 2000
    });

    try {
        const success = await importMatches(pool);
        process.exit(success ? 0 : 1);
    } finally {
        await pool.end();
    }
}

// CLI Entry point for importing tournament-specific matches
async function mainTournament(tournamentId: string): Promise<void> {
    const dbPassword: string = settings.POSTGRES_PASSWORD;
    const dbname: string = settings.POSTGRES_DB;
    const user: string = settings.POSTGRES_USER;
    const host: string = settings.POSTGRES_HOST;
    const port: number = parseInt(String(settings.POSTGRES_PORT));

    const pool = new Pool({
        database: dbname,
        user: user,
        password: dbPassword,
        host: host,
        port: port,
        max: 20,
        idleTimeoutMillis: 30000,
        connectionTimeoutMillis: 2000
    });

    try {
        await importTournamentMatches(tournamentId, pool);
        process.exit(0);
    } catch (error: any) {
        console.error('Erreur:', error);
        process.exit(1);
    } finally {
        await pool.end();
    }
}

// CLI execution
if (process.argv[1] === fileURLToPath(import.meta.url)) {
    const args = process.argv.slice(2);

    if (args.length > 0) {
        // Mode spécifique: importer les matches d'un tournoi
        console.log(`🎯 Mode spécifique de tournoi: ${args[0]}`);
        mainTournament(args[0]!).catch(error => {
            console.error('Erreur non gérée:', error);
            process.exit(1);
        });
    } else {
        main().catch(error => {
            console.error('Erreur non gérée:', error);
            process.exit(1);
        });
    }
}
