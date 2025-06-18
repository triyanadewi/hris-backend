#!/bin/bash

echo "=== Environment Configuration Verification ==="

echo "Checking for required environment files..."

if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "Please create .env file with Laravel configuration"
    exit 1
else
    echo "✅ .env file found"
fi

if [ ! -f .env.postgres ]; then
    echo "❌ .env.postgres file not found!"
    echo "Please create .env.postgres file with PostgreSQL configuration"
    exit 1
else
    echo "✅ .env.postgres file found"
fi

echo ""
echo "=== Laravel Database Configuration (.env) ==="
DB_CONNECTION=$(grep '^DB_CONNECTION=' .env 2>/dev/null | cut -d'=' -f2)
DB_HOST=$(grep '^DB_HOST=' .env 2>/dev/null | cut -d'=' -f2)
DB_PORT=$(grep '^DB_PORT=' .env 2>/dev/null | cut -d'=' -f2)
DB_DATABASE=$(grep '^DB_DATABASE=' .env 2>/dev/null | cut -d'=' -f2)
DB_USERNAME=$(grep '^DB_USERNAME=' .env 2>/dev/null | cut -d'=' -f2)

echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"

echo ""
echo "=== PostgreSQL Configuration (.env.postgres) ==="
POSTGRES_DB=$(grep '^POSTGRES_DB=' .env.postgres 2>/dev/null | cut -d'=' -f2)
POSTGRES_USER=$(grep '^POSTGRES_USER=' .env.postgres 2>/dev/null | cut -d'=' -f2)

echo "POSTGRES_DB: $POSTGRES_DB"
echo "POSTGRES_USER: $POSTGRES_USER"

echo ""
echo "=== Credential Consistency Check ==="

if [ "$DB_USERNAME" = "$POSTGRES_USER" ]; then
    echo "✅ DB_USERNAME matches POSTGRES_USER ($DB_USERNAME)"
else
    echo "❌ DB_USERNAME ($DB_USERNAME) does not match POSTGRES_USER ($POSTGRES_USER)"
    ERRORS=1
fi

if [ "$DB_DATABASE" = "$POSTGRES_DB" ]; then
    echo "✅ DB_DATABASE matches POSTGRES_DB ($DB_DATABASE)"
else
    echo "❌ DB_DATABASE ($DB_DATABASE) does not match POSTGRES_DB ($POSTGRES_DB)"
    ERRORS=1
fi

echo ""
echo "=== Docker Compose Configuration Check ==="

if [ -f docker-compose.yml ]; then
    echo "✅ docker-compose.yml found"
    
    if grep -q "env_file:.*\.env\.postgres" docker-compose.yml; then
        echo "✅ PostgreSQL service uses .env.postgres"
    else
        echo "❌ PostgreSQL service should use 'env_file: .env.postgres'"
        ERRORS=1
    fi
    
    if grep -A 5 "api:" docker-compose.yml | grep -q "\.env"; then
        echo "✅ API service uses .env file"
    else
        echo "⚠️  API service may not be using .env file explicitly"
    fi
else
    echo "❌ docker-compose.yml not found"
    ERRORS=1
fi

echo ""
echo "=== .dockerignore Check ==="

if [ -f .dockerignore ]; then
    if grep -q "^\.env\.postgres$" .dockerignore || grep -q "^\.env\.\*$" .dockerignore; then
        echo "❌ .env.postgres is ignored in .dockerignore - this will prevent it from being included in builds"
        ERRORS=1
    else
        echo "✅ .env.postgres is not being ignored"
    fi
else
    echo "⚠️  .dockerignore not found"
fi

echo ""
echo "=== Verification Result ==="

if [ "$ERRORS" = "1" ]; then
    echo "❌ Configuration errors found! Please fix the issues above."
    exit 1
else
    echo "✅ All environment configurations are correct!"
    echo ""
    echo "You can now run:"
    echo "  docker compose up -d --build"
    echo ""
    echo "To start the application stack."
fi
