FROM node:20-alpine

WORKDIR /app

# 1. Installer netcat pour vérifier postgres
RUN apk add --no-cache netcat-openbsd

# 2. Dépendances
COPY package*.json ./
RUN npm install

# 3. Schéma Prisma (obligatoire avant generate)
COPY prisma ./prisma

# 4. Génération du client Prisma
RUN npx prisma generate

# 5. Code applicatif
COPY . .

# 6. Script d'entrypoint qui attend PostgreSQL
COPY entrypoint.sh /app/entrypoint.sh
RUN chmod +x /app/entrypoint.sh

EXPOSE 3070

ENTRYPOINT ["/app/entrypoint.sh"]