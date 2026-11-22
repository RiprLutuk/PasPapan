ALTER TABLE custom_form_templates
 ADD COLUMN automation_project_id UUID REFERENCES operations_projects(id) ON DELETE SET NULL,
 ADD COLUMN automation_task_title TEXT NOT NULL DEFAULT '' CHECK(length(automation_task_title)<=180),
 ADD COLUMN automation_priority TEXT NOT NULL DEFAULT 'normal' CHECK(automation_priority IN ('low','normal','high'));
