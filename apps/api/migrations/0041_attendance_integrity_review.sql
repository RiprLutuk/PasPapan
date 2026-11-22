ALTER TABLE attendance_integrity_events
    ADD COLUMN review_status TEXT NOT NULL DEFAULT 'open'
        CHECK(review_status IN ('open','resolved','dismissed')),
    ADD COLUMN reviewed_by UUID REFERENCES users(id) ON DELETE SET NULL,
    ADD COLUMN reviewed_at TIMESTAMPTZ,
    ADD COLUMN resolution_note TEXT;

CREATE INDEX attendance_integrity_events_review_idx
    ON attendance_integrity_events(company_id,review_status,severity,created_at DESC);
