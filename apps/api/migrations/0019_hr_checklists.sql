CREATE TABLE hr_checklists (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    user_id UUID NOT NULL,
    title TEXT NOT NULL CHECK(length(trim(title)) BETWEEN 1 AND 200),
    kind TEXT NOT NULL CHECK(kind IN ('onboarding','offboarding')),
    completed_at TIMESTAMPTZ,
    completed_by UUID,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id,id),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id),
    FOREIGN KEY(company_id,completed_by) REFERENCES company_memberships(company_id,user_id)
);
CREATE TABLE hr_checklist_items (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    checklist_id UUID NOT NULL,
    label TEXT NOT NULL CHECK(length(trim(label)) BETWEEN 1 AND 300),
    position INTEGER NOT NULL,
    completed_at TIMESTAMPTZ,
    FOREIGN KEY(company_id,checklist_id) REFERENCES hr_checklists(company_id,id) ON DELETE CASCADE
);
