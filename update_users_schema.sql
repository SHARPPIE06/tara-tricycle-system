-- Migration: Add Verification and Extended Profile Fields to Users

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'verification_status') THEN
        CREATE TYPE verification_status AS ENUM ('pending', 'verified', 'rejected');
    END IF;
END
$$;

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS age INT,
ADD COLUMN IF NOT EXISTS birthdate DATE,
ADD COLUMN IF NOT EXISTS status verification_status DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS classifications JSONB,
ADD COLUMN IF NOT EXISTS toda_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS home_address VARCHAR(255),
ADD COLUMN IF NOT EXISTS member_number VARCHAR(50),
ADD COLUMN IF NOT EXISTS id_documents JSONB;
