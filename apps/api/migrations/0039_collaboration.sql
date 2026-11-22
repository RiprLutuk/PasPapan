CREATE TABLE collaboration_threads (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    project_id UUID REFERENCES operations_projects(id) ON DELETE SET NULL,
    created_by UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    kind TEXT NOT NULL DEFAULT 'group' CHECK(kind IN ('group','project','personal')),
    title TEXT NOT NULL CHECK(length(trim(title)) BETWEEN 1 AND 180),
    archived_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE collaboration_thread_members (
    thread_id UUID NOT NULL REFERENCES collaboration_threads(id) ON DELETE CASCADE,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role TEXT NOT NULL DEFAULT 'member' CHECK(role IN ('owner','member')),
    last_read_at TIMESTAMPTZ,
    PRIMARY KEY(thread_id,user_id),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id) ON DELETE RESTRICT
);
CREATE TABLE collaboration_messages (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    thread_id UUID NOT NULL REFERENCES collaboration_threads(id) ON DELETE CASCADE,
    author_id UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    body TEXT NOT NULL CHECK(length(trim(body)) BETWEEN 1 AND 5000),
    file_id UUID REFERENCES private_files(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX collaboration_threads_company_idx ON collaboration_threads(company_id, created_at DESC) WHERE archived_at IS NULL;
CREATE INDEX collaboration_members_user_idx ON collaboration_thread_members(company_id,user_id,last_read_at);
CREATE INDEX collaboration_messages_thread_idx ON collaboration_messages(thread_id,created_at DESC);
