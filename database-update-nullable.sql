-- ÁTR Beragadt Betegek - Database Update
-- Make atr_nursing_cycle_id nullable (field removed from registration form)

USE atr_betegek;

-- Modify column to allow NULL values
ALTER TABLE atr_records
MODIFY COLUMN atr_nursing_cycle_id VARCHAR(100) NULL COMMENT 'ÁTR ápolási ciklus azonosító (opcionális)';

-- Verify the change
DESCRIBE atr_records;
