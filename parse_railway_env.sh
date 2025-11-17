#!/bin/bash

# Parse Railway DATABASE_URL and set Laravel DB variables
if [ -n "$DATABASE_URL" ]; then
    # Extract components from DATABASE_URL
    # Format: postgresql://username:password@host:port/database
    DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):\([^@]*\)@.*|\1|p')
    DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):\([^@]*\)@.*|\2|p')
    DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*://[^@]*@\([^:]*\):.*|\1|p')
    DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*://[^@]*@[^:]*:\([^/]*\)/.*|\1|p')
    DB_NAME=$(echo $DATABASE_URL | sed -n 's|.*/\([^?]*\).*|\1|p')

    export DB_CONNECTION=pgsql
    export DB_HOST=$DB_HOST
    export DB_PORT=$DB_PORT
    export DB_DATABASE=$DB_NAME
    export DB_USERNAME=$DB_USER
    export DB_PASSWORD=$DB_PASS
fi

# Parse Railway REDIS_URL and set Laravel Redis variables
if [ -n "$REDIS_URL" ]; then
    # Extract components from REDIS_URL
    # Format: redis://username:password@host:port
    REDIS_USER=$(echo $REDIS_URL | sed -n 's|.*://\([^:]*\):\([^@]*\)@.*|\1|p')
    REDIS_PASS=$(echo $REDIS_URL | sed -n 's|.*://\([^:]*\):\([^@]*\)@.*|\2|p')
    REDIS_HOST=$(echo $REDIS_URL | sed -n 's|.*://[^@]*@\([^:]*\):.*|\1|p')
    REDIS_PORT=$(echo $REDIS_URL | sed -n 's|.*://[^@]*@[^:]*:\([^/]*\).*|\1|p')

    export REDIS_HOST=$REDIS_HOST
    export REDIS_PORT=$REDIS_PORT
    if [ -n "$REDIS_PASS" ] && [ "$REDIS_PASS" != "redis" ]; then
        export REDIS_PASSWORD=$REDIS_PASS
    fi
fi

# Set APP_URL from Railway
if [ -n "$RAILWAY_STATIC_URL" ]; then
    export APP_URL=$RAILWAY_STATIC_URL
fi

# Execute the original command
exec "$@"