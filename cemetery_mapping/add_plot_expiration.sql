-- Add plot/burial record expiration support

ALTER TABLE burial_records
    ADD COLUMN expiration_date DATE DEFAULT NULL AFTER death_date,
    ADD COLUMN renewal_count INT DEFAULT 0 AFTER expiration_date,
    ADD INDEX idx_expiration_date (expiration_date);

ALTER TABLE available_plots
    ADD COLUMN expiration_date DATE DEFAULT NULL AFTER notes,
    ADD COLUMN renewal_count INT DEFAULT 0 AFTER expiration_date,
    ADD INDEX idx_expiration_date (expiration_date);
