-- PostgreSQL 18 extensions for cryptography, trigram search, case-insensitive text, GiST exclusion constraints, and query telemetry
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS citext;
CREATE EXTENSION IF NOT EXISTS btree_gist;

DO $$
BEGIN
    CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
EXCEPTION
    WHEN OTHERS THEN
        NULL;
END $$;

-- Trigram GIN indexes to accelerate ILIKE search across employees and workspace records
CREATE INDEX IF NOT EXISTS users_full_name_trgm_idx
    ON users USING gin (full_name gin_trgm_ops);

CREATE INDEX IF NOT EXISTS users_email_trgm_idx
    ON users USING gin (email gin_trgm_ops);

CREATE INDEX IF NOT EXISTS workspace_records_title_trgm_idx
    ON workspace_records USING gin (title gin_trgm_ops);
