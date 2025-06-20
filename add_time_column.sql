USE barber_db;

-- Add the appointment_time column to the appointments table if it doesn't exist
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS appointment_time TIME AFTER appointment_date;

-- If your MySQL version doesn't support IF NOT EXISTS for columns, use this:
-- First check if the column exists
-- SET @columnExists = 0;
-- SELECT COUNT(*) INTO @columnExists 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'barber_db' 
-- AND TABLE_NAME = 'appointments' 
-- AND COLUMN_NAME = 'appointment_time';
--
-- -- Add column only if it doesn't exist
-- SET @query = IF(@columnExists = 0, 
--                'ALTER TABLE appointments ADD COLUMN appointment_time TIME AFTER appointment_date', 
--                'SELECT "Column already exists"');
-- PREPARE stmt FROM @query;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;

-- Show the updated table structure
DESCRIBE appointments; 