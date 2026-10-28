ALTER TABLE assets ADD COLUMN purchase_date DATE,ADD COLUMN purchase_amount BIGINT,ADD COLUMN salvage_amount BIGINT,ADD COLUMN useful_life_months INTEGER,
ADD CONSTRAINT asset_valuation_complete CHECK(
    (purchase_date IS NULL AND purchase_amount IS NULL AND salvage_amount IS NULL AND useful_life_months IS NULL)
    OR (purchase_date IS NOT NULL AND purchase_amount IS NOT NULL AND salvage_amount IS NOT NULL AND useful_life_months IS NOT NULL AND purchase_amount BETWEEN 0 AND 999999999999 AND salvage_amount BETWEEN 0 AND purchase_amount AND useful_life_months BETWEEN 1 AND 1200)
);
