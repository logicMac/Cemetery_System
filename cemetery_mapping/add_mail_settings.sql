-- ============================================
-- Table: mail_settings
-- Purpose: Database-driven SMTP configuration for outgoing email
--          (expiration notices, admin digests). Single-row table.
-- ============================================
CREATE TABLE IF NOT EXISTS mail_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(255) NOT NULL DEFAULT 'smtp.gmail.com',
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_encryption VARCHAR(10) NOT NULL DEFAULT 'tls',
    smtp_username VARCHAR(255) NOT NULL DEFAULT '',
    smtp_password VARCHAR(255) NOT NULL DEFAULT '',
    from_name VARCHAR(255) NOT NULL DEFAULT 'Matinao Memorial Cemetery',
    admin_notify_email VARCHAR(255) NOT NULL DEFAULT '',
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed a single settings row if empty
INSERT INTO mail_settings (id)
SELECT 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mail_settings);
