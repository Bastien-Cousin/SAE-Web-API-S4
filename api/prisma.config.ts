import * as path from 'path';
import * as dotenv from 'dotenv';

// Charge .env.local (dev local) en priorité, puis .env (fallback)
dotenv.config({ path: path.join(__dirname, '..', '.env.local') });
dotenv.config({ path: path.join(__dirname, '..', '.env') });

import { defineConfig, env } from 'prisma/config';

// Si on est dans Docker (pas de LOCAL_DEV), remplacer localhost par db
let databaseUrl = env('DATABASE_URL');

if (!process.env.LOCAL_DEV) {
    databaseUrl = databaseUrl.replace('localhost', 'db');
}

export default defineConfig({
    schema: 'prisma/schema.prisma',
    migrations: {
        path: 'prisma/migrations',
    },
    datasource: {
        url: databaseUrl,
    },
});
