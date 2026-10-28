CREATE TABLE biometric_profiles (
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    photo_file_id UUID NOT NULL REFERENCES private_files(id) ON DELETE RESTRICT,
    descriptor REAL[] NOT NULL CHECK (array_length(descriptor, 1) = 128),
    model_version TEXT NOT NULL CHECK (length(trim(model_version)) BETWEEN 1 AND 80),
    capture_evidence JSONB NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(capture_evidence) = 'object'),
    consented_at TIMESTAMPTZ NOT NULL,
    enrolled_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (company_id, user_id),
    UNIQUE (photo_file_id),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE
);

CREATE INDEX biometric_profiles_company_idx ON biometric_profiles (company_id, enrolled_at DESC);
