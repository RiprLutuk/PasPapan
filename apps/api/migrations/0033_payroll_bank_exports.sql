CREATE TABLE employee_payroll_accounts (
    company_id UUID NOT NULL,
    user_id UUID NOT NULL,
    bank_code TEXT NOT NULL,
    account_number TEXT NOT NULL,
    account_name TEXT NOT NULL,
    version BIGINT NOT NULL DEFAULT 1,
    PRIMARY KEY(company_id,user_id),
    FOREIGN KEY(company_id,user_id) REFERENCES company_memberships(company_id,user_id) ON DELETE CASCADE
);
CREATE TABLE payroll_bank_export_settings (
    company_id UUID PRIMARY KEY REFERENCES companies(id) ON DELETE CASCADE,
    columns JSONB NOT NULL,
    delimiter TEXT NOT NULL CHECK(delimiter IN (',',';')),
    version BIGINT NOT NULL DEFAULT 1
);
ALTER TABLE payroll_records ADD COLUMN bank_account_snapshot JSONB;
