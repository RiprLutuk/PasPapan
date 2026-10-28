ALTER TABLE workforce_requests
    DROP CONSTRAINT IF EXISTS workforce_requests_kind_check;

ALTER TABLE workforce_requests
    ADD CONSTRAINT workforce_requests_kind_check
        CHECK (kind IN (
            'leave',
            'overtime',
            'attendance_correction',
            'reimbursement',
            'wfh',
            'cash_advance',
            'shift_swap'
        )),
    ADD COLUMN amount_cents BIGINT CHECK (amount_cents IS NULL OR amount_cents > 0),
    ADD COLUMN target_user_id UUID,
    ADD COLUMN attachment_file_id UUID REFERENCES private_files(id) ON DELETE SET NULL,
    ADD COLUMN metadata JSONB NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
    ADD CONSTRAINT workforce_requests_target_membership_fk
        FOREIGN KEY (company_id, target_user_id)
        REFERENCES company_memberships(company_id, user_id)
        ON DELETE RESTRICT;

CREATE INDEX workforce_requests_target_idx
    ON workforce_requests (company_id, target_user_id, created_at DESC)
    WHERE target_user_id IS NOT NULL;
