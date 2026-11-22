CREATE TABLE custom_form_templates (
 id UUID PRIMARY KEY, company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
 title TEXT NOT NULL CHECK(length(title)<=180), category TEXT NOT NULL DEFAULT 'general' CHECK(length(category)<=80), description TEXT NOT NULL DEFAULT '' CHECK(length(description)<=1000),
 fields JSONB NOT NULL CHECK(jsonb_typeof(fields)='array'), is_active BOOLEAN NOT NULL DEFAULT true, created_by UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT, created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE INDEX custom_form_templates_company_idx ON custom_form_templates(company_id,is_active,created_at DESC);
CREATE TABLE custom_form_submissions (
 id UUID PRIMARY KEY, company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE, template_id UUID NOT NULL REFERENCES custom_form_templates(id) ON DELETE RESTRICT, submitted_by UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
 status TEXT NOT NULL DEFAULT 'submitted' CHECK(status IN ('submitted','reviewed')), payload JSONB NOT NULL CHECK(jsonb_typeof(payload)='object'), reviewed_by UUID REFERENCES users(id) ON DELETE SET NULL, reviewed_at TIMESTAMPTZ, created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
 FOREIGN KEY(company_id,submitted_by) REFERENCES company_memberships(company_id,user_id) ON DELETE RESTRICT);
CREATE INDEX custom_form_submissions_company_idx ON custom_form_submissions(company_id,status,created_at DESC);
