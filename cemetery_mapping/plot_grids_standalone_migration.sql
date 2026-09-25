-- ============================================================
-- Migration: Make plot_grids standalone (not tied to records)
-- Purpose: Allow grids to exist independently of burial records
-- ============================================================

USE cemetery_mapping1;

-- Make record_id nullable (grids can exist without a record)
ALTER TABLE plot_grids MODIFY COLUMN record_id INT NULL;

-- Drop the foreign key constraint so grids are not cascaded when records are deleted
-- (Find and drop the FK — name may vary, so we use a prepared approach)
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'plot_grids'
      AND COLUMN_NAME = 'record_id'
      AND REFERENCED_TABLE_NAME = 'burial_records'
    LIMIT 1
);
SET @sql = IF(@fk_name IS NOT NULL,
    CONCAT('ALTER TABLE plot_grids DROP FOREIGN KEY ', @fk_name),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add center coordinates so standalone grids can be positioned on the map
ALTER TABLE plot_grids ADD COLUMN center_lat DECIMAL(12,8) NULL AFTER name;
ALTER TABLE plot_grids ADD COLUMN center_lng DECIMAL(12,8) NULL AFTER center_lat;
