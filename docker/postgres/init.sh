#!/bin/sh
set -eu
: "${APP_DB_PASSWORD:?Application database password is required}"
psql --username "$POSTGRES_USER" --dbname postgres --set=ON_ERROR_STOP=1 \
    --set=app_password="$APP_DB_PASSWORD" <<'SQL'
CREATE ROLE runa LOGIN PASSWORD :'app_password' NOSUPERUSER NOCREATEDB NOCREATEROLE;
CREATE DATABASE runa OWNER runa;
REVOKE ALL ON DATABASE runa FROM PUBLIC;
SQL
psql --username "$POSTGRES_USER" --dbname runa --set=ON_ERROR_STOP=1 <<'SQL'
REVOKE ALL ON SCHEMA public FROM PUBLIC;
CREATE SCHEMA shared AUTHORIZATION runa;
SQL
if [ -n "${TEST_DB_PASSWORD:-}" ]; then
    psql --username "$POSTGRES_USER" --dbname postgres --set=ON_ERROR_STOP=1 \
        --set=test_password="$TEST_DB_PASSWORD" <<'SQL'
CREATE ROLE runa_test LOGIN PASSWORD :'test_password' NOSUPERUSER CREATEDB NOCREATEROLE;
CREATE DATABASE runa_test OWNER runa_test;
REVOKE ALL ON DATABASE runa_test FROM PUBLIC;
SQL
    psql --username "$POSTGRES_USER" --dbname runa_test --set=ON_ERROR_STOP=1 <<'SQL'
REVOKE ALL ON SCHEMA public FROM PUBLIC;
CREATE SCHEMA shared AUTHORIZATION runa_test;
SQL
fi
