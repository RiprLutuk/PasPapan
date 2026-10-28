CREATE TABLE payroll_policies (
    company_id UUID PRIMARY KEY REFERENCES companies(id) ON DELETE CASCADE,
    version BIGINT NOT NULL DEFAULT 1,
    rules JSONB NOT NULL CHECK(jsonb_typeof(rules)='array'),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE payroll_records ADD COLUMN calculation JSONB;
