CREATE TABLE operations_clients (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 180),
    contact_name TEXT NOT NULL DEFAULT '' CHECK(length(contact_name) <= 160),
    contact_phone TEXT NOT NULL DEFAULT '' CHECK(length(contact_phone) <= 60),
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','inactive')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id,name)
);
CREATE TABLE operations_branches (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 160),
    kind TEXT NOT NULL CHECK(kind IN ('branch','store','office','warehouse','site')),
    address TEXT NOT NULL DEFAULT '' CHECK(length(address) <= 1000),
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','inactive')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id,name)
);
CREATE TABLE operations_projects (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    client_id UUID REFERENCES operations_clients(id) ON DELETE SET NULL,
    branch_id UUID REFERENCES operations_branches(id) ON DELETE SET NULL,
    manager_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    code TEXT NOT NULL CHECK(length(trim(code)) BETWEEN 1 AND 40),
    name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 180),
    description TEXT NOT NULL DEFAULT '' CHECK(length(description) <= 1500),
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','on_hold','completed','cancelled')),
    version BIGINT NOT NULL DEFAULT 1, created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id,code),
    FOREIGN KEY(company_id,manager_user_id) REFERENCES company_memberships(company_id,user_id) ON DELETE RESTRICT
);
CREATE TABLE operations_tasks (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    project_id UUID NOT NULL REFERENCES operations_projects(id) ON DELETE CASCADE,
    assigned_to UUID REFERENCES users(id) ON DELETE SET NULL,
    title TEXT NOT NULL CHECK(length(trim(title)) BETWEEN 1 AND 180),
    priority TEXT NOT NULL DEFAULT 'normal' CHECK(priority IN ('low','normal','high')),
    due_on DATE, status TEXT NOT NULL DEFAULT 'todo' CHECK(status IN ('todo','in_progress','done')),
    version BIGINT NOT NULL DEFAULT 1, created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY(company_id,assigned_to) REFERENCES company_memberships(company_id,user_id) ON DELETE RESTRICT
);
CREATE TABLE operations_task_checklist_items (
    id UUID PRIMARY KEY, company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    task_id UUID NOT NULL REFERENCES operations_tasks(id) ON DELETE CASCADE,
    label TEXT NOT NULL CHECK(length(trim(label)) BETWEEN 1 AND 300), position INTEGER NOT NULL CHECK(position >= 0),
    completed_at TIMESTAMPTZ, UNIQUE(task_id,position)
);
CREATE INDEX operations_projects_company_status_idx ON operations_projects(company_id,status);
CREATE INDEX operations_tasks_company_status_idx ON operations_tasks(company_id,status);
