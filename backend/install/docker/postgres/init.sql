-- QuteCart PostgreSQL Initialization Script
-- This script runs once when the PostgreSQL container is first created

-- Create database if not exists (Docker usually creates this from DB_DATABASE)
-- SELECT 'CREATE DATABASE qutekart' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'qutekart')\gexec

-- Set timezone to UTC
SET timezone = 'UTC';

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";      -- UUID generation
CREATE EXTENSION IF NOT EXISTS "pg_trgm";        -- Full-text search
CREATE EXTENSION IF NOT EXISTS "unaccent";       -- Remove accents for search

-- Create custom functions for MySQL compatibility

-- CURDATE() equivalent
CREATE OR REPLACE FUNCTION CURDATE() RETURNS DATE AS $$
BEGIN
    RETURN CURRENT_DATE;
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- CURTIME() equivalent
CREATE OR REPLACE FUNCTION CURTIME() RETURNS TIME AS $$
BEGIN
    RETURN CURRENT_TIME;
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- NOW() is already available in PostgreSQL

-- Performance optimizations
ALTER DATABASE qutekart SET synchronous_commit = OFF;

-- Connection settings
ALTER DATABASE qutekart SET work_mem = '32MB';
ALTER DATABASE qutekart SET maintenance_work_mem = '128MB';

-- Grant privileges to user
GRANT ALL PRIVILEGES ON DATABASE qutekart TO qutekart;

-- Success message
DO $$
BEGIN
    RAISE NOTICE 'QuteCart database initialized successfully!';
    RAISE NOTICE 'Extensions enabled: uuid-ossp, pg_trgm, unaccent';
    RAISE NOTICE 'MySQL compatibility functions created: CURDATE(), CURTIME()';
END $$;
