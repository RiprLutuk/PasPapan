ALTER TABLE custom_form_templates ADD COLUMN schema_version INTEGER NOT NULL DEFAULT 1;
ALTER TABLE custom_form_submissions ADD COLUMN review_note TEXT NOT NULL DEFAULT '' CHECK(length(review_note)<=1000), ADD COLUMN automation_task_id UUID REFERENCES operations_tasks(id) ON DELETE SET NULL;
CREATE INDEX custom_form_submissions_task_idx ON custom_form_submissions(automation_task_id) WHERE automation_task_id IS NOT NULL;
