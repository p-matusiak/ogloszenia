#!/bin/sh
# Osobna baza dla `php artisan test`, żeby RefreshDatabase nigdy nie czyścił
# danych deweloperskich. Nazwa pochodzi z DB_TEST_DATABASE w .env.
set -e

TEST_DB="${POSTGRES_TEST_DB:-ogloszenia_test}"

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE "$TEST_DB" OWNER "$POSTGRES_USER";
EOSQL
