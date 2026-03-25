#!/bin/sh
set -e

echo "PostgreSQL Host: $DB_HOST:$DB_PORT"
echo "Waiting for PostgreSQL to be ready..."

# Wait for PostgreSQL
counter=0
max_attempts=60

while [ $counter -lt $max_attempts ]; do
    if nc -z -w1 "$DB_HOST" "$DB_PORT" 2>/dev/null; then
        echo "✓ PostgreSQL is ready!"
        break
    fi
    
    counter=$((counter + 1))
    echo "Attempt $counter/$max_attempts - PostgreSQL not ready yet, waiting..."
    sleep 1
done

if [ $counter -eq $max_attempts ]; then
    echo "✗ PostgreSQL did not respond after $max_attempts seconds"
    exit 1
fi

# Extra safety delay
echo "Waiting 2 more seconds for PostgreSQL to stabilize..."
sleep 2

# Run migrations
echo "Running Prisma migrations..."
npx prisma migrate deploy

# Start application
echo "✓ Starting application..."
exec npm run dev
