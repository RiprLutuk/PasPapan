CREATE TABLE attendance_integrity_events (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    attendance_id UUID,
    event_type TEXT NOT NULL CHECK(event_type IN ('low_accuracy','impossible_speed','reused_coordinates','device_signal')),
    severity TEXT NOT NULL CHECK(severity IN ('info','warning','high')),
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb CHECK(jsonb_typeof(metadata)='object'),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id) ON DELETE RESTRICT
);
CREATE INDEX attendance_integrity_events_company_idx ON attendance_integrity_events(company_id,severity,created_at DESC);
