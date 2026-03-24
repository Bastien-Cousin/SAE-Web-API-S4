import * as path from 'path';
import * as dotenv from 'dotenv';

// Carregar .env do diretório pai (raiz do projeto)
dotenv.config({ path: path.join(__dirname, '..', '.env') });

import { defineConfig, env } from 'prisma/config';

export default defineConfig({
    schema: 'prisma/schema.prisma',
    migrations: {
        path: 'prisma/migrations',
    },
    datasource: {
        url: env('DATABASE_URL'),
    },
});
