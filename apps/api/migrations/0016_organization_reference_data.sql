CREATE TABLE organization_reference_data (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    kind TEXT NOT NULL CHECK(kind IN ('division','job_title','education','leave_type','holiday')),
    name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 100),
    active BOOLEAN NOT NULL DEFAULT true,
    holiday_date DATE,
    annual_days INTEGER CHECK(annual_days IS NULL OR annual_days BETWEEN 0 AND 366),
    version BIGINT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id, kind, name),
    UNIQUE(company_id, id),
    CHECK ((kind = 'holiday') = (holiday_date IS NOT NULL)),
    CHECK ((kind = 'leave_type') = (annual_days IS NOT NULL))
);
CREATE INDEX organization_reference_active_idx ON organization_reference_data(company_id, kind, active);

CREATE TABLE employee_leave_entitlements (
    company_id UUID NOT NULL,
    user_id UUID NOT NULL,
    leave_type_id UUID NOT NULL,
    year INTEGER NOT NULL CHECK(year BETWEEN 2000 AND 2200),
    days INTEGER NOT NULL CHECK(days BETWEEN 0 AND 366),
    version BIGINT NOT NULL DEFAULT 1,
    PRIMARY KEY(company_id,user_id,leave_type_id,year),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id),
    FOREIGN KEY(company_id,leave_type_id) REFERENCES organization_reference_data(company_id,id)
);

ALTER TABLE workforce_requests ADD COLUMN leave_type_id UUID,
    ADD CONSTRAINT request_leave_type_fk FOREIGN KEY(company_id,leave_type_id)
    REFERENCES organization_reference_data(company_id,id);
