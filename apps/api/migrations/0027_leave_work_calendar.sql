CREATE TABLE company_work_calendars (
    company_id UUID PRIMARY KEY REFERENCES companies(id) ON DELETE CASCADE,
    weekdays INTEGER[] NOT NULL DEFAULT ARRAY[1,2,3,4,5,6,7],
    exclude_holidays BOOLEAN NOT NULL DEFAULT false,
    version BIGINT NOT NULL DEFAULT 1,
    CHECK(cardinality(weekdays) BETWEEN 1 AND 7 AND weekdays <@ ARRAY[1,2,3,4,5,6,7])
);
ALTER TABLE workforce_requests ADD COLUMN charged_leave_days INTEGER CHECK(charged_leave_days>0);
UPDATE workforce_requests SET charged_leave_days=ends_on-starts_on+1 WHERE kind='leave';
CREATE FUNCTION leave_charge_days(tenant UUID, starts DATE, ends DATE) RETURNS BIGINT LANGUAGE SQL STABLE AS $$
    SELECT count(*) FROM generate_series(starts,ends,interval '1 day') day
    LEFT JOIN company_work_calendars c ON c.company_id=tenant
    WHERE extract(isodow FROM day)::int = ANY(COALESCE(c.weekdays,ARRAY[1,2,3,4,5,6,7]))
    AND (NOT COALESCE(c.exclude_holidays,false) OR NOT EXISTS(
        SELECT 1 FROM organization_reference_data h WHERE h.company_id=tenant AND h.kind='holiday' AND h.active AND h.holiday_date=day::date
    ))
$$;
