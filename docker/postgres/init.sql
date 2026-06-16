-- Create the application database user
-- PostgreSQL RLS setup
ALTER DATABASE codeguardian SET app.current_organization_id = '';

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
