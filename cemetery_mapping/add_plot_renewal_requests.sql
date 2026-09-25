-- Plot renewal requests table
-- Visitors can request renewal of expiring/expired plots; admin approves or rejects.

CREATE TABLE IF NOT EXISTS plot_renewal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    record_id INT DEFAULT NULL,
    plot_id INT DEFAULT NULL,
    plot_type ENUM('burial', 'available') NOT NULL,
    current_expiration_date DATE DEFAULT NULL,
    requested_years INT DEFAULT 5,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    INDEX idx_visitor (visitor_id),
    INDEX idx_status (status),
    INDEX idx_plot_type (plot_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
