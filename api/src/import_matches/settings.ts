import * as dotenv from 'dotenv';

dotenv.config();

interface Settings {
    API_KEY_TEAM: string;
    API_KEY_PLAYER: string;
    API_KEY_MATCHES: string;
    POSTGRES_PASSWORD: string;
    POSTGRES_DB: string;
    POSTGRES_USER: string;
    POSTGRES_HOST: string;
    POSTGRES_PORT: string | number;
}

const settings: Settings = {
    API_KEY_TEAM: process.env.LIQUIPEDIA_API_KEY_TEAM || '',
    API_KEY_PLAYER: process.env.LIQUIPEDIA_API_KEY_PLAYER || '',
    API_KEY_MATCHES: process.env.LIQUIPEDIA_API_KEY_MATCHES || '',
    POSTGRES_PASSWORD: process.env.POSTGRES_PASSWORD || 'User06',
    POSTGRES_DB: process.env.POSTGRES_DB || 'postgres',
    POSTGRES_USER: process.env.POSTGRES_USER || 'user',
    POSTGRES_HOST: process.env.POSTGRES_HOST || 'localhost',
    POSTGRES_PORT: process.env.POSTGRES_PORT || '5439'
};

export default settings;
