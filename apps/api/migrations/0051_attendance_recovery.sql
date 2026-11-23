-- Attendance records are legal/audit history.  Keep the scheduling and location
-- policy that was in effect at check-in instead of resolving mutable master data
-- again at check-out.
ALTER TABLE shifts
    ADD COLUMN checkout_grace_minutes INTEGER NOT NULL DEFAULT 240
        CHECK (checkout_grace_minutes BETWEEN 0 AND 1440),
    ADD COLUMN max_work_minutes INTEGER NOT NULL DEFAULT 960
        CHECK (max_work_minutes BETWEEN 60 AND 1440);

ALTER TABLE shifts
    ADD CONSTRAINT shifts_time_direction_check CHECK (
        (crosses_midnight AND ends_at < starts_at)
        OR (NOT crosses_midnight AND ends_at > starts_at)
    );

ALTER TABLE attendances
    ADD COLUMN session_index SMALLINT NOT NULL DEFAULT 1 CHECK (session_index > 0),
    ADD COLUMN shift_snapshot JSONB NOT NULL DEFAULT '{}'::jsonb
        CHECK (jsonb_typeof(shift_snapshot) = 'object'),
    ADD COLUMN attendance_timezone TEXT,
    ADD COLUMN checkout_grace_minutes INTEGER NOT NULL DEFAULT 240
        CHECK (checkout_grace_minutes BETWEEN 0 AND 1440),
    ADD COLUMN check_in_policy_snapshot JSONB NOT NULL DEFAULT '{}'::jsonb
        CHECK (jsonb_typeof(check_in_policy_snapshot) = 'object'),
    ADD COLUMN checkout_recovery_used BOOLEAN NOT NULL DEFAULT false;

ALTER TABLE attendances
    DROP CONSTRAINT attendances_company_id_user_id_attendance_date_key;
ALTER TABLE attendances
    ADD CONSTRAINT attendances_company_user_work_session_unique
        UNIQUE (company_id, user_id, attendance_date, session_index);

CREATE INDEX attendances_open_exception_idx
    ON attendances (company_id, attendance_date, checked_in_at)
    WHERE checked_in_at IS NOT NULL AND checked_out_at IS NULL;

-- Old records were created under the then-current company timezone.  They do not
-- gain a retroactive shift, but retain a deterministic timezone and policy marker.
UPDATE attendances a
SET attendance_timezone = c.timezone,
    shift_snapshot = jsonb_build_object('id',s.id,'name',s.name,'starts_at',s.starts_at::text,'ends_at',s.ends_at::text,'crosses_midnight',s.crosses_midnight,'checkout_grace_minutes',s.checkout_grace_minutes,'max_work_minutes',s.max_work_minutes,'source','assignment_backfill'),
    checkout_grace_minutes = s.checkout_grace_minutes,
    check_in_policy_snapshot = jsonb_build_object('source', 'legacy')
FROM companies c, shift_assignments sa, shifts s
WHERE c.id = a.company_id
  AND sa.company_id=c.id AND sa.user_id=a.user_id AND sa.work_date=a.attendance_date
  AND s.id=sa.shift_id
  AND a.attendance_timezone IS NULL;

UPDATE attendances a
SET attendance_timezone = c.timezone,
    shift_snapshot = jsonb_build_object('source', 'legacy'),
    check_in_policy_snapshot = jsonb_build_object('source', 'legacy')
FROM companies c
WHERE c.id = a.company_id AND a.attendance_timezone IS NULL;
