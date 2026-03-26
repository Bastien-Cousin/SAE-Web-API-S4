#!/bin/sh
set -e

echo "Waiting for PostgreSQL at $DB_HOST:$DB_PORT..."
counter=0
max_attempts=60

while [ $counter -lt $max_attempts ]; do
    if nc -z -w1 "$DB_HOST" "$DB_PORT" 2>/dev/null; then
        echo "PostgreSQL is ready!"
        break
    fi
    counter=$((counter + 1))
    echo "Attempt $counter/$max_attempts..."
    sleep 1
done

if [ $counter -eq $max_attempts ]; then
    echo "PostgreSQL did not respond!"
    exit 1
fi

sleep 2
echo "Running migrations..."
npx prisma migrate deploy

echo "Seeding database..."
npx prisma db seed

echo "Starting app..."
exec npm run dev
