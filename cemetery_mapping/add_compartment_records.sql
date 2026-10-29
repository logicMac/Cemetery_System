-- ============================================
-- Migration: Add support for multiple burial records per compartment
-- Purpose: Allow compartments to hold multiple burial records (family plots, multi-level)
-- ============================================

USE cemetery_mapping1;

-- Create junction table for many-to-many relationship between compartments and burial records
CREATE TABLE IF NOT EXISTS compartment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compartment_id INT NOT NULL,
    record_id INT NOT NULL,
    burial_level VARCHAR(20) DEFAULT 'ground',  -- ground, first, second, etc.
    position_notes TEXT,  -- Additional info like "north side", "top tier", etc.
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (compartment_id) REFERENCES plot_compartments(id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES burial_records(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_compartment_record (compartment_id, record_id),
    INDEX idx_compartment (compartment_id),
    INDEX idx_record (record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Junction table for multiple records per compartment';

-- Update plot_compartments to track total capacity and occupied count
ALTER TABLE plot_compartments 
    ADD COLUMN IF NOT EXISTS capacity INT DEFAULT 1 COMMENT 'How many records this compartment can hold',
    ADD COLUMN IF NOT EXISTS occupied_count INT DEFAULT 0 COMMENT 'Current number of records in compartment';

-- Migrate existing record_id relationships to the new table
INSERT IGNORE INTO compartment_records (compartment_id, record_id, burial_level)
SELECT id, record_id, 'ground'
FROM plot_compartments 
WHERE record_id IS NOT NULL;

-- Update occupied_count based on migrated data
UPDATE plot_compartments pc
SET occupied_count = (
    SELECT COUNT(*) 
    FROM compartment_records cr 
    WHERE cr.compartment_id = pc.id
)
WHERE id IN (SELECT compartment_id FROM compartment_records);

-- Note: We keep the old record_id column for backwards compatibility
-- but the new system will use compartment_records table

COMMIT;
