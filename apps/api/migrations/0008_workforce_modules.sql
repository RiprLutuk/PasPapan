CREATE TABLE IF NOT EXISTS workspace_records (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    created_by UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    module_key TEXT NOT NULL CHECK (length(trim(module_key)) BETWEEN 1 AND 120),
    title TEXT NOT NULL CHECK (length(trim(title)) BETWEEN 1 AND 200),
    detail TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'Aktif' CHECK (length(trim(status)) BETWEEN 1 AND 80),
    amount TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY (company_id, created_by) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS workspace_records_company_module_idx
    ON workspace_records (company_id, module_key, created_at DESC);
