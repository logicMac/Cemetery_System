-- ============================================================
-- Migration: plot_grids and plot_grid_cells
-- Purpose: Database-driven grid drawing for burial records
-- Links: burial_records -> plot_grids -> plot_grid_cells
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

USE cemetery_mapping1;

-- ============================================
-- Table: plot_grids
-- Purpose: Store grid configuration for a burial record/plot
-- ============================================
CREATE TABLE IF NOT EXISTS plot_grids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    start_x DECIMAL(12,4) NOT NULL,
    start_y DECIMAL(12,4) NOT NULL,
    end_x DECIMAL(12,4) NOT NULL,
    end_y DECIMAL(12,4) NOT NULL,
    grid_width DECIMAL(12,4) NOT NULL DEFAULT 0,
    grid_height DECIMAL(12,4) NOT NULL DEFAULT 0,
    rows_count INT NOT NULL DEFAULT 1,
    cols_count INT NOT NULL DEFAULT 1,
    cell_width DECIMAL(12,4) NOT NULL DEFAULT 0,
    cell_height DECIMAL(12,4) NOT NULL DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES burial_records(id) ON DELETE CASCADE,
    INDEX idx_record (record_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: plot_grid_cells
-- Purpose: Store individual cells within a grid
-- ============================================
CREATE TABLE IF NOT EXISTS plot_grid_cells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grid_id INT NOT NULL,
    row_idx INT NOT NULL,
    col_idx INT NOT NULL,
    start_x DECIMAL(12,4) NOT NULL,
    start_y DECIMAL(12,4) NOT NULL,
    end_x DECIMAL(12,4) NOT NULL,
    end_y DECIMAL(12,4) NOT NULL,
    is_selected TINYINT(1) DEFAULT 0,
    notes TEXT,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grid_id) REFERENCES plot_grids(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grid_cell (grid_id, row_idx, col_idx),
    INDEX idx_grid (grid_id),
    INDEX idx_selected (is_selected)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
