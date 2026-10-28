CREATE TABLE hr_document_templates (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    title TEXT NOT NULL CHECK(length(trim(title)) BETWEEN 1 AND 200),
    body TEXT NOT NULL CHECK(length(trim(body)) BETWEEN 1 AND 20000),
    active BOOLEAN NOT NULL DEFAULT true,
    version BIGINT NOT NULL DEFAULT 1,
    UNIQUE(company_id,id)
);
ALTER TABLE hr_document_requests ADD COLUMN template_id UUID,ADD COLUMN rendered_document JSONB,
    ADD FOREIGN KEY(company_id,template_id) REFERENCES hr_document_templates(company_id,id);
