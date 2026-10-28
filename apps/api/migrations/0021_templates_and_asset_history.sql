CREATE TABLE hr_checklist_templates (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    title TEXT NOT NULL CHECK(length(trim(title)) BETWEEN 1 AND 200),
    kind TEXT NOT NULL CHECK(kind IN('onboarding','offboarding')),
    items TEXT[] NOT NULL CHECK(cardinality(items) BETWEEN 1 AND 30),
    active BOOLEAN NOT NULL DEFAULT true,
    version BIGINT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id,id)
);
ALTER TABLE hr_checklists ADD COLUMN template_id UUID,
    ADD CONSTRAINT hr_checklist_template_fk FOREIGN KEY(company_id,template_id) REFERENCES hr_checklist_templates(company_id,id);
ALTER TABLE asset_assignments ADD COLUMN return_condition TEXT CHECK(return_condition IN('good','damaged','maintenance','retired'));
