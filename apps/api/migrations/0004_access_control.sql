-- Additive: preserve existing memberships and their default role behavior.
CREATE TABLE access_roles (
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    id UUID NOT NULL,
    name TEXT NOT NULL CHECK (length(trim(name)) BETWEEN 1 AND 80),
    permissions TEXT[] NOT NULL DEFAULT '{}',
    PRIMARY KEY (company_id, id)
);
CREATE UNIQUE INDEX access_roles_name ON access_roles(company_id, lower(name));

CREATE TABLE member_access (
    company_id UUID NOT NULL,
    user_id UUID NOT NULL,
    role_id UUID,
    grants TEXT[] NOT NULL DEFAULT '{}',
    denies TEXT[] NOT NULL DEFAULT '{}',
    PRIMARY KEY (company_id, user_id),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id, role_id) REFERENCES access_roles(company_id, id) ON DELETE RESTRICT
);
