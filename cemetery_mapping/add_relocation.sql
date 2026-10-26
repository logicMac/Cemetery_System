-- ============================================
-- Relocation support for expired, unrenewed burial plots
-- ============================================

ALTER TABLE burial_records
    ADD COLUMN is_relocated TINYINT(1) DEFAULT 0,
    ADD COLUMN orig_latitude DECIMAL(10, 8) DEFAULT NULL,
    ADD COLUMN orig_longitude DECIMAL(11, 8) DEFAULT NULL,
    ADD COLUMN relocated_at TIMESTAMP NULL DEFAULT NULL;

CREATE INDEX idx_relocated ON burial_records (is_relocated);

-- Generic key-value settings (relocation point, future toggles)
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
