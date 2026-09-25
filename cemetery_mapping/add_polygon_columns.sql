-- Add polygon boundary columns to support free-form plot drawing
-- Works on MySQL 5.7+ / MariaDB 10.2+ (avoids unsupported IF NOT EXISTS for columns)

USE cemetery_mapping;

SET @add_poly_available = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE available_plots ADD COLUMN polygon TEXT DEFAULT NULL AFTER longitude',
        'SELECT "polygon already exists in available_plots" AS message'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'available_plots'
      AND column_name = 'polygon'
);
PREPARE stmt FROM @add_poly_available;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_poly_burial = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE burial_records ADD COLUMN polygon TEXT DEFAULT NULL AFTER longitude',
        'SELECT "polygon already exists in burial_records" AS message'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'burial_records'
      AND column_name = 'polygon'
);
PREPARE stmt FROM @add_poly_burial;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
