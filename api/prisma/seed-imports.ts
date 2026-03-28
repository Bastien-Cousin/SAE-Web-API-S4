import { Pool } from 'pg';
import * as dotenv from 'dotenv';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Charger les variables d'environnement
dotenv.config({ path: path.join(__dirname, '..', '..', '.env') });

import { importTeams } from '../src/import_donnees/equipes_cs.js';
import { importPlayers } from '../src/import_donnees/joueurs_cs.js';
import { importMatches } from '../src/import_donnees/matchs_cs.js';
import { importTournaments } from '../src/import_donnees/tournois_cs.js';

const DB_PASSWORD: string = process.env.POSTGRES_PASSWORD || 'sae_password';
const dbname: string = process.env.POSTGRES_DB || 'sae_db';
const user: string = process.env.POSTGRES_USER || 'sae_user';
const host: string = process.env.POSTGRES_HOST || 'localhost';
const port: number = parseInt(String(process.env.POSTGRES_PORT || '5432'));

// Créer un pool de connexion
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

// Définir les étapes d'importation
const importSteps = [
    { name: 'Équipes', fn: () => importTeams(pool) },
    { name: 'Joueurs', fn: () => importPlayers(pool) },
    { name: 'Matchs', fn: () => importMatches(pool) },
    { name: 'Tournois', fn: () => importTournaments(pool) },
];

// Exécuter les étapes
async function runSeeding() {
    console.log('🚀 Démarrage du seed avec imports Liquipedia...');
    console.log(`⏰ ${new Date().toLocaleString()}\n`);

    let successCount = 0;
    let failCount = 0;

    for (const step of importSteps) {
        console.log(`\n${'='.repeat(60)}`);
        console.log(`📥 Importation: ${step.name}`);
        console.log('='.repeat(60));

        try {
            const ok = await step.fn();
            if (ok) {
                console.log(`✅ ${step.name} importé avec succès\n`);
                successCount++;
            } else {
                console.error(`❌ ${step.name} échoué, continuation...\n`);
                failCount++;
            }
        } catch (error: any) {
            console.error(`❌ Erreur lors de ${step.name}: ${error.message}\n`);
            failCount++;
        }
    }

    console.log(`\n${'='.repeat(60)}`);
    console.log('📊 Résumé du seed');
    console.log('='.repeat(60));
    console.log(`✅ Réussis: ${successCount}`);
    console.log(`❌ Échoués: ${failCount}\n`);

    await pool.end();

    if (failCount === 0) {
        console.log('✅ Seed complet réussi!');
        process.exit(0);
    } else {
        console.error('⚠️ Seed complet avec erreurs');
        process.exit(failCount > 0 ? 1 : 0);
    }
}

// Gestion de l'arrêt gracieux
process.on('SIGINT', async () => {
    console.log('\n\n🛑 Arrêt...');
    await pool.end();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n\n🛑 Arrêt...');
    await pool.end();
    process.exit(0);
});

// Démarrer le seed
runSeeding().catch(async (error: any) => {
    console.error('❌ Erreur fatale:', error.message);
    await pool.end();
    process.exit(1);
});
