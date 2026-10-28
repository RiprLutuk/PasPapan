CREATE TABLE integration_providers (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    purpose TEXT NOT NULL CHECK(purpose IN ('biometric_enrollment','document_signature')),
    driver TEXT NOT NULL CHECK(driver IN ('signed_bridge_v1')),
    name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 100),
    endpoint TEXT NOT NULL,
    encrypted_secret TEXT,
    active BOOLEAN NOT NULL DEFAULT false,
    version BIGINT NOT NULL DEFAULT 1,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(company_id,id),
    CHECK(NOT active OR encrypted_secret IS NOT NULL)
);
CREATE UNIQUE INDEX integration_provider_active_idx ON integration_providers(company_id,purpose) WHERE active;
ALTER TABLE provider_verifications ADD COLUMN provider_id UUID,ADD COLUMN provider_version BIGINT,
    ADD FOREIGN KEY(company_id,provider_id) REFERENCES integration_providers(company_id,id);
