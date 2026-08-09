-- Matinao Memorial Cemetery Database Schema
-- Character Set: utf8mb4 for full Unicode support
-- Engine: InnoDB for transaction support and foreign key constraints

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: cemetery_mapping
CREATE DATABASE IF NOT EXISTS cemetery_mapping DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cemetery_mapping;

-- ============================================
-- Table: admin_users
-- Purpose: Store administrative user credentials
-- ============================================
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: visitors
-- Purpose: Store visitor/public user accounts
-- ============================================
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: burial_records
-- Purpose: Store deceased person burial information
-- ============================================
CREATE TABLE burial_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    decedent_name VARCHAR(255) NOT NULL,
    birth_date DATE,
    death_date DATE,
    barangay VARCHAR(100),
    plot_number VARCHAR(50),
    memory_space TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_fenced TINYINT(1) DEFAULT 0,
    family_name VARCHAR(255),
    photo VARCHAR(255),
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (decedent_name),
    INDEX idx_plot (plot_number),
    INDEX idx_death_date (death_date),
    INDEX idx_barangay (barangay),
    INDEX idx_family (family_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: available_plots
-- Purpose: Store available burial plot locations
-- ============================================
CREATE TABLE available_plots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plot_number VARCHAR(50),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    notes TEXT,
    photo VARCHAR(255),
    added_by VARCHAR(50),
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    has_grid TINYINT(1) DEFAULT 0,
    grid_rows INT DEFAULT NULL,
    grid_cols INT DEFAULT NULL,
    grid_data TEXT DEFAULT NULL,
    compartment_count INT DEFAULT 1,
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: plot_compartments
-- Purpose: Store individual compartments within plots
-- ============================================
CREATE TABLE plot_compartments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plot_id INT NOT NULL,
    compartment_number VARCHAR(20) NOT NULL,
    row_index INT NOT NULL,
    col_index INT NOT NULL,
    is_occupied TINYINT(1) DEFAULT 0,
    record_id INT DEFAULT NULL,
    notes TEXT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plot_id) REFERENCES available_plots(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plot_position (plot_id, row_index, col_index),
    INDEX idx_plot_id (plot_id),
    INDEX idx_occupied (is_occupied)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: visitor_activity_log
-- Purpose: Track visitor activities for analytics
-- ============================================
CREATE TABLE visitor_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    activity_type VARCHAR(50),
    record_id INT DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    INDEX idx_visitor (visitor_id),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
