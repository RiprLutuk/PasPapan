CREATE TABLE provider_verifications (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    purpose TEXT NOT NULL CHECK (purpose IN ('biometric_enrollment','document_signature')),
    resource_id UUID NOT NULL REFERENCES private_files(id) ON DELETE CASCADE,
    resource_sha256 TEXT NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL DEFAULT now() + interval '10 minutes',
    consumed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY (company_id,user_id) REFERENCES company_memberships(company_id,user_id) ON DELETE CASCADE
);
CREATE INDEX provider_verifications_expiry_idx ON provider_verifications(expires_at);
ALTER TABLE biometric_profiles ADD COLUMN verified_at TIMESTAMPTZ;
ALTER TABLE hr_document_requests ADD COLUMN signature_reference TEXT;
