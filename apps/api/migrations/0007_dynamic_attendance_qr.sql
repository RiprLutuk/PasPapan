-- Existing printed tokens expire immediately. Displays must request a fresh QR.
ALTER TABLE attendance_locations
    ADD COLUMN qr_expires_at TIMESTAMPTZ NOT NULL DEFAULT now();
