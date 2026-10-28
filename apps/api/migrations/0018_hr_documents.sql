CREATE TABLE hr_document_requests (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    user_id UUID NOT NULL,
    title TEXT NOT NULL CHECK(length(trim(title)) BETWEEN 1 AND 200),
    reason TEXT NOT NULL CHECK(length(trim(reason)) BETWEEN 1 AND 4000),
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','issued','rejected')),
    file_id UUID REFERENCES private_files(id) ON DELETE RESTRICT,
    decision_note TEXT NOT NULL DEFAULT '',
    issued_by UUID,
    version BIGINT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id),
    FOREIGN KEY(company_id,issued_by) REFERENCES company_memberships(company_id,user_id),
    CHECK ((status='issued') = (file_id IS NOT NULL))
);
CREATE INDEX hr_documents_user_idx ON hr_document_requests(company_id,user_id,created_at DESC);
