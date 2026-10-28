CREATE TABLE performance_cycles (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 100),
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL CHECK(ends_on>=starts_on),
    status TEXT NOT NULL DEFAULT 'open' CHECK(status IN ('open','closed')),
    version BIGINT NOT NULL DEFAULT 1,
    UNIQUE(company_id,id),
    UNIQUE(company_id,name)
);
ALTER TABLE performance_goals ADD COLUMN cycle_id UUID, ADD COLUMN weight INTEGER NOT NULL DEFAULT 100 CHECK(weight BETWEEN 1 AND 1000),
    ADD FOREIGN KEY(company_id,cycle_id) REFERENCES performance_cycles(company_id,id);
