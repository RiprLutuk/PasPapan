ALTER TABLE sessions
 ADD COLUMN last_active_at TIMESTAMPTZ NOT NULL DEFAULT now(),
 ADD COLUMN idle_expires_at TIMESTAMPTZ NOT NULL DEFAULT (now()+interval '30 minutes'),
 ADD COLUMN idle_timeout_seconds INTEGER NOT NULL DEFAULT 1800 CHECK(idle_timeout_seconds BETWEEN 60 AND 86400);
UPDATE sessions SET idle_expires_at=least(idle_expires_at,expires_at);
