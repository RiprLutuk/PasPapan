CREATE TABLE workforce_requests (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kind TEXT NOT NULL CHECK (kind IN ('leave', 'overtime', 'attendance_correction', 'reimbursement')),
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    minutes BIGINT CHECK (minutes IS NULL OR minutes > 0),
    reason TEXT NOT NULL CHECK (length(trim(reason)) > 0),
    status TEXT NOT NULL DEFAULT 'pending_manager' CHECK (status IN ('pending_manager', 'pending_admin', 'approved', 'rejected', 'cancelled')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CHECK (ends_on >= starts_on),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE
);

CREATE INDEX workforce_requests_company_status_idx ON workforce_requests (company_id, status, created_at DESC);
CREATE INDEX workforce_requests_user_date_idx ON workforce_requests (company_id, user_id, starts_on DESC);

CREATE TABLE request_decisions (
    id UUID PRIMARY KEY,
    request_id UUID NOT NULL REFERENCES workforce_requests(id) ON DELETE CASCADE,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    approver_id UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    stage TEXT NOT NULL CHECK (stage IN ('manager', 'admin')),
    decision TEXT NOT NULL CHECK (decision IN ('approved', 'rejected')),
    note TEXT,
    decided_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (request_id, stage),
    FOREIGN KEY (company_id, approver_id) REFERENCES company_memberships(company_id, user_id) ON DELETE RESTRICT
);

CREATE TABLE payroll_periods (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'closed')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (company_id, period_start, period_end),
    CHECK (period_end >= period_start)
);

CREATE TABLE payroll_records (
    id UUID PRIMARY KEY,
    period_id UUID NOT NULL REFERENCES payroll_periods(id) ON DELETE CASCADE,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    gross_amount NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (gross_amount >= 0),
    deduction_amount NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (deduction_amount >= 0),
    net_amount NUMERIC(14,2) GENERATED ALWAYS AS (gross_amount - deduction_amount) STORED,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (period_id, user_id),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE RESTRICT
);

CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    read_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE
);

CREATE INDEX notifications_user_unread_idx ON notifications (company_id, user_id, created_at DESC) WHERE read_at IS NULL;

CREATE TABLE outbox_events (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    event_type TEXT NOT NULL,
    payload JSONB NOT NULL CHECK (jsonb_typeof(payload) = 'object'),
    available_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    processed_at TIMESTAMPTZ,
    attempts BIGINT NOT NULL DEFAULT 0 CHECK (attempts >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX outbox_events_pending_idx ON outbox_events (available_at, created_at) WHERE processed_at IS NULL;

CREATE TABLE private_files (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    uploaded_by UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    object_key TEXT NOT NULL UNIQUE,
    file_name TEXT NOT NULL,
    content_type TEXT NOT NULL,
    byte_size BIGINT NOT NULL CHECK (byte_size >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY (company_id, uploaded_by) REFERENCES company_memberships(company_id, user_id) ON DELETE RESTRICT
);
