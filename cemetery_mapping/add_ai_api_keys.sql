-- AI API Keys table
-- Database-driven storage for AI provider credentials (Groq, etc.)
CREATE TABLE IF NOT EXISTS ai_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL DEFAULT 'groq',
    label VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    model VARCHAR(100) DEFAULT NULL,
    api_url VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_active (provider, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
