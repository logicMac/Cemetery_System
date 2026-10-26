-- ============================================
-- Table: expiration_notices
-- Purpose: Track which expiration notice emails have been sent,
--          so each notice is only sent once per expiration date.
-- ============================================
CREATE TABLE IF NOT EXISTS expiration_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL DEFAULT 0,        -- burial_records.id (0 = admin digest)
    notice_type VARCHAR(20) NOT NULL,        -- 'reminder' | 'expired' | 'digest'
    for_date DATE NOT NULL,                  -- the expiration_date the notice covers (or run date for digest)
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_notice (record_id, notice_type, for_date),
    INDEX idx_record (record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
