ALTER TABLE sessions ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
UPDATE sessions s SET company_id=(SELECT m.company_id FROM company_memberships m WHERE m.user_id=s.user_id AND m.employment_status='active' ORDER BY m.created_at,m.company_id LIMIT 1);
CREATE INDEX sessions_tenant_idx ON sessions(company_id,user_id) WHERE revoked_at IS NULL;

CREATE TABLE auth_attempt_windows (
    bucket_hash TEXT PRIMARY KEY,
    attempts INTEGER NOT NULL CHECK(attempts > 0),
    started_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX auth_attempt_expiry_idx ON auth_attempt_windows(started_at);
