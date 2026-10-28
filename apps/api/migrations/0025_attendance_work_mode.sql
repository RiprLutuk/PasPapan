ALTER TABLE attendances ADD COLUMN work_mode TEXT NOT NULL DEFAULT 'onsite' CHECK(work_mode IN ('onsite','wfh'));
