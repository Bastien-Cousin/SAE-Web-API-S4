require('dotenv').config();

module.exports = {
    API_KEY_TEAM: process.env.LIQUIPEDIA_API_KEY_TEAM,
    API_KEY_PLAYER: process.env.LIQUIPEDIA_API_KEY_PLAYER,
    API_KEY_MATCHES: process.env.LIQUIPEDIA_API_KEY_MATCHES,
    POSTGRES_PASSWORD: process.env.POSTGRES_PASSWORD,
    POSTGRES_DB: process.env.POSTGRES_DB,
    POSTGRES_USER: process.env.POSTGRES_USER,
    POSTGRES_HOST: process.env.POSTGRES_HOST,
    POSTGRES_PORT: process.env.POSTGRES_PORT || 5439,
};