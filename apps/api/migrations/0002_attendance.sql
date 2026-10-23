CREATE TABLE employee_profiles (
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    employee_code TEXT NOT NULL CHECK (length(trim(employee_code)) > 0),
    manager_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    started_on DATE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (company_id, user_id),
    UNIQUE (company_id, employee_code),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id, manager_user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE RESTRICT
);

CREATE INDEX employee_profiles_company_manager_idx ON employee_profiles (company_id, manager_user_id);

CREATE TABLE shifts (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    name TEXT NOT NULL CHECK (length(trim(name)) > 0),
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    crosses_midnight BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX shifts_company_id_idx ON shifts (company_id);

CREATE TABLE shift_assignments (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    shift_id UUID NOT NULL REFERENCES shifts(id) ON DELETE RESTRICT,
    work_date DATE NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (company_id, user_id, work_date),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE
);

CREATE INDEX shift_assignments_company_date_idx ON shift_assignments (company_id, work_date);

CREATE TABLE attendances (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    attendance_date DATE NOT NULL,
    checked_in_at TIMESTAMPTZ,
    checked_out_at TIMESTAMPTZ,
    check_in_latitude DOUBLE PRECISION,
    check_in_longitude DOUBLE PRECISION,
    check_out_latitude DOUBLE PRECISION,
    check_out_longitude DOUBLE PRECISION,
    photo_key TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (company_id, user_id, attendance_date),
    FOREIGN KEY (company_id, user_id) REFERENCES company_memberships(company_id, user_id) ON DELETE CASCADE,
    CHECK ((check_in_latitude IS NULL) = (check_in_longitude IS NULL)),
    CHECK ((check_out_latitude IS NULL) = (check_out_longitude IS NULL)),
    CHECK (checked_out_at IS NULL OR checked_in_at IS NOT NULL),
    CHECK (checked_out_at IS NULL OR checked_out_at >= checked_in_at)
);

CREATE INDEX attendances_company_date_idx ON attendances (company_id, attendance_date);
