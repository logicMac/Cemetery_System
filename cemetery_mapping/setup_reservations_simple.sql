-- ============================================
-- SIMPLE RESERVATION SYSTEM SETUP
-- This creates the reservation tables without compartment FK
-- ============================================

USE cemetery_mapping1;

-- Drop existing tables if they have issues
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS plot_reservations;
DROP TABLE IF EXISTS plot_pricing;

-- ============================================
-- Table: plot_reservations
-- Purpose: Store plot reservation/purchase requests
-- NOTE: compartment_id is just a reference number, not a foreign key
-- ============================================
CREATE TABLE plot_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    plot_id INT NOT NULL,
    compartment_id INT DEFAULT NULL COMMENT 'Simple reference to compartment number, not a FK',
    reservation_type ENUM('standard', 'premium', 'family', 'lawn') DEFAULT 'standard',
    purpose VARCHAR(255),
    intended_for VARCHAR(255),
    contact_number VARCHAR(20),
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    reservation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT DEFAULT NULL,
    approved_date TIMESTAMP NULL,
    rejection_reason TEXT,
    notes TEXT,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    FOREIGN KEY (plot_id) REFERENCES available_plots(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_visitor (visitor_id),
    INDEX idx_plot (plot_id),
    INDEX idx_status (status),
    INDEX idx_payment (payment_status),
    INDEX idx_date (reservation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Plot reservations without compartment FK constraint';

-- ============================================
-- Table: payments
-- Purpose: Track payment transactions
-- ============================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'gcash', 'paymaya', 'credit_card', 'other') DEFAULT 'cash',
    amount DECIMAL(10, 2) NOT NULL,
    reference_number VARCHAR(100),
    proof_of_payment VARCHAR(255),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT DEFAULT NULL,
    verified_date TIMESTAMP NULL,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (reservation_id) REFERENCES plot_reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_reservation (reservation_id),
    INDEX idx_status (verification_status),
    INDEX idx_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: plot_pricing
-- Purpose: Store pricing configuration
-- ============================================
CREATE TABLE plot_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plot_type ENUM('standard', 'premium', 'family', 'lawn') NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_plot_type (plot_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Insert default pricing
-- ============================================
INSERT INTO plot_pricing (plot_type, price, description, is_active) VALUES
('standard', 5000.00, 'Standard burial plot - single compartment', 1),
('premium', 15000.00, 'Premium fenced burial plot with marker', 1),
('family', 25000.00, 'Family plot - multiple compartments', 1),
('lawn', 8000.00, 'Lawn-type burial plot', 1);

-- ============================================
-- Verify tables created
-- ============================================
SELECT 
    'plot_reservations' as table_name,
    COUNT(*) as record_count
FROM plot_reservations
UNION ALL
SELECT 
    'payments' as table_name,
    COUNT(*) as record_count
FROM payments
UNION ALL
SELECT 
    'plot_pricing' as table_name,
    COUNT(*) as record_count
FROM plot_pricing;

SELECT 'Setup completed successfully!' as Status;
