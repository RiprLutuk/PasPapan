ALTER TABLE employee_leave_entitlements ADD COLUMN carryover_days INTEGER NOT NULL DEFAULT 0 CHECK(carryover_days BETWEEN 0 AND 366);
ALTER TABLE employee_leave_entitlements ADD CONSTRAINT entitlement_total_days CHECK(days+carryover_days<=366);
