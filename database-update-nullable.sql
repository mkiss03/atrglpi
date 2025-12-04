-- ÁTR Beragadt Betegek - Database Update
-- Make atr_nursing_cycle_id nullable (field removed from registration form)

USE atr_betegek;

-- Modify column to allow NULL values with DEFAULT NULL
ALTER TABLE atr_records
MODIFY COLUMN atr_nursing_cycle_id VARCHAR(100) NULL DEFAULT NULL COMMENT 'ÁTR ápolási ciklus azonosító (opcionális)';

-- Verify the change
DESCRIBE atr_records;
