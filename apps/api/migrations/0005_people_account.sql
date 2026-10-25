ALTER TABLE company_memberships
    ADD COLUMN employment_status TEXT NOT NULL DEFAULT 'active' CHECK (employment_status IN ('active','inactive','resigned')),
    ADD COLUMN access_revoked_at TIMESTAMPTZ;
ALTER TABLE employee_profiles
    ADD COLUMN phone TEXT NOT NULL DEFAULT '',
    ADD COLUMN address TEXT NOT NULL DEFAULT '',
    ADD COLUMN team TEXT NOT NULL DEFAULT '',
    ADD COLUMN job_title TEXT NOT NULL DEFAULT '',
    ADD COLUMN version BIGINT NOT NULL DEFAULT 0;
