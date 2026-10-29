-- Fix plot_renewal_requests table to support admin direct renewals
-- Add or_number column and make visitor_id nullable

-- Add or_number column (will fail silently if already exists - that's ok)
SET @column_exists = (
  SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'plot_renewal_requests' 
  AND COLUMN_NAME = 'or_number'
);

SET @sql = IF(@column_exists = 0,
  'ALTER TABLE plot_renewal_requests ADD COLUMN or_number VARCHAR(100) DEFAULT NULL AFTER requested_years',
  'SELECT "or_number column already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make visitor_id nullable
ALTER TABLE plot_renewal_requests MODIFY COLUMN visitor_id INT DEFAULT NULL;

-- Update the foreign key to allow NULL visitor_id
-- First, get the actual foreign key name
SET @fk_name = (
  SELECT CONSTRAINT_NAME 
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'plot_renewal_requests' 
  AND COLUMN_NAME = 'visitor_id' 
  AND REFERENCED_TABLE_NAME IS NOT NULL
  LIMIT 1
);

-- Drop the old foreign key if it exists
SET @sql = IF(@fk_name IS NOT NULL,
  CONCAT('ALTER TABLE plot_renewal_requests DROP FOREIGN KEY ', @fk_name),
  'SELECT "No foreign key to drop" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the new foreign key with SET NULL on delete
ALTER TABLE plot_renewal_requests
  ADD CONSTRAINT plot_renewal_requests_ibfk_1
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE SET NULL;
