CREATE TABLE assets (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    asset_code TEXT NOT NULL CHECK(length(trim(asset_code)) BETWEEN 1 AND 80),
    name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 200),
    serial_number TEXT NOT NULL DEFAULT '',
    condition TEXT NOT NULL DEFAULT 'good' CHECK(condition IN ('good', 'damaged', 'maintenance', 'retired')),
    notes TEXT NOT NULL DEFAULT '',
    version BIGINT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id, id),
    UNIQUE(company_id, asset_code)
);
CREATE TABLE asset_assignments (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    asset_id UUID NOT NULL,
    user_id UUID NOT NULL,
    assigned_by UUID NOT NULL,
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    acknowledged_at TIMESTAMPTZ,
    returned_at TIMESTAMPTZ,
    return_note TEXT,
    FOREIGN KEY(company_id, asset_id) REFERENCES assets(company_id, id),
    FOREIGN KEY(company_id, user_id) REFERENCES company_memberships(company_id, user_id),
    FOREIGN KEY(company_id, assigned_by) REFERENCES company_memberships(company_id, user_id)
);
CREATE UNIQUE INDEX asset_one_active_assignment ON asset_assignments(company_id, asset_id) WHERE returned_at IS NULL;
CREATE INDEX asset_assignee_idx ON asset_assignments(company_id, user_id, assigned_at DESC);
