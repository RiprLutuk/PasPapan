CREATE TABLE request_payments (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    request_id UUID NOT NULL REFERENCES workforce_requests(id) ON DELETE CASCADE,
    actor_id UUID NOT NULL REFERENCES users(id),
    kind TEXT NOT NULL CHECK(kind IN ('disbursement','repayment')),
    amount_cents BIGINT NOT NULL CHECK(amount_cents>0),
    reference TEXT NOT NULL CHECK(length(trim(reference)) BETWEEN 3 AND 200),
    recorded_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id,reference)
);
CREATE INDEX request_payments_request_idx ON request_payments(company_id,request_id,recorded_at);
