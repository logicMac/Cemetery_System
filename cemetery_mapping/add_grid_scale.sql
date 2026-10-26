-- ============================================
-- Persisted scale for standalone plot grids
-- ============================================
-- The plot-grids editor lets admins resize a grid (25%-200%) but the
-- transform was never saved, so every map rendered the grid at 100%.
-- This column stores the editor scale so admin, visitor, and record
-- maps all draw the grid at the same size.

ALTER TABLE plot_grids
    ADD COLUMN grid_scale DECIMAL(6,3) NOT NULL DEFAULT 1.000 AFTER cols_count;
