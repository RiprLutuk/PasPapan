CREATE TABLE announcements (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    author_id UUID NOT NULL,
    title TEXT NOT NULL CHECK (length(trim(title)) BETWEEN 1 AND 200),
    body TEXT NOT NULL CHECK (length(trim(body)) BETWEEN 1 AND 20000),
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
    version BIGINT NOT NULL DEFAULT 1,
    published_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (company_id, id),
    FOREIGN KEY (company_id, author_id) REFERENCES company_memberships(company_id, user_id)
);
CREATE INDEX announcements_feed_idx ON announcements(company_id, status, published_at DESC);

CREATE TABLE announcement_reads (
    company_id UUID NOT NULL,
    announcement_id UUID NOT NULL,
    user_id UUID NOT NULL,
    read_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (company_id, announcement_id, user_id),
    FOREIGN KEY (company_id, announcement_id) REFERENCES announcements(company_id, id) ON DELETE CASCADE,
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE
);
