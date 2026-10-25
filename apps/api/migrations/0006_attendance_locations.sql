CREATE TABLE attendance_locations (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    name TEXT NOT NULL CHECK (length(trim(name)) > 0),
    qr_token TEXT NOT NULL CHECK (length(qr_token) >= 32),
    latitude DOUBLE PRECISION NOT NULL CHECK (latitude BETWEEN -90 AND 90),
    longitude DOUBLE PRECISION NOT NULL CHECK (longitude BETWEEN -180 AND 180),
    radius_meters INTEGER NOT NULL CHECK (radius_meters BETWEEN 1 AND 10000),
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (company_id, id),
    UNIQUE (company_id, qr_token)
);

CREATE INDEX attendance_locations_company_active_idx
    ON attendance_locations (company_id, active);

ALTER TABLE attendances
    ADD COLUMN check_in_location_id UUID,
    ADD COLUMN check_out_location_id UUID,
    ADD CONSTRAINT attendances_check_in_location_fk
        FOREIGN KEY (company_id, check_in_location_id)
        REFERENCES attendance_locations (company_id, id) ON DELETE RESTRICT,
    ADD CONSTRAINT attendances_check_out_location_fk
        FOREIGN KEY (company_id, check_out_location_id)
        REFERENCES attendance_locations (company_id, id) ON DELETE RESTRICT;
