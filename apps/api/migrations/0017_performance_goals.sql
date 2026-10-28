CREATE TABLE performance_goals (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    user_id UUID NOT NULL,
    title TEXT NOT NULL CHECK(length(trim(title)) BETWEEN 1 AND 200),
    unit TEXT NOT NULL CHECK(length(trim(unit)) BETWEEN 1 AND 40),
    target BIGINT NOT NULL CHECK(target > 0),
    actual BIGINT NOT NULL DEFAULT 0 CHECK(actual >= 0),
    due_on DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','submitted','approved','needs_revision')),
    evidence TEXT NOT NULL DEFAULT '',
    review_note TEXT NOT NULL DEFAULT '',
    reviewed_by UUID,
    version BIGINT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id),
    FOREIGN KEY(company_id,reviewed_by) REFERENCES company_memberships(company_id,user_id)
);
CREATE INDEX performance_employee_idx ON performance_goals(company_id,user_id,due_on);
