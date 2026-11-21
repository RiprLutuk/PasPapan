CREATE TABLE operations_visit_evidence (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    project_id UUID NOT NULL REFERENCES operations_projects(id) ON DELETE CASCADE,
    task_id UUID REFERENCES operations_tasks(id) ON DELETE SET NULL,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    visited_at TIMESTAMPTZ NOT NULL,
    latitude DOUBLE PRECISION,
    longitude DOUBLE PRECISION,
    accuracy_meters INTEGER CHECK(accuracy_meters IS NULL OR accuracy_meters >= 0),
    address TEXT NOT NULL DEFAULT '' CHECK(length(address) <= 1000),
    notes TEXT NOT NULL DEFAULT '' CHECK(length(notes) <= 3000),
    file_id UUID REFERENCES private_files(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CHECK((latitude IS NULL AND longitude IS NULL) OR (latitude BETWEEN -90 AND 90 AND longitude BETWEEN -180 AND 180)),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id) ON DELETE RESTRICT
);
CREATE INDEX operations_visit_evidence_company_visited_idx ON operations_visit_evidence(company_id, visited_at DESC);
CREATE INDEX operations_visit_evidence_project_idx ON operations_visit_evidence(project_id, visited_at DESC);
