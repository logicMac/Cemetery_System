-- Link burial records to visitors so visitors can renew their plots
ALTER TABLE burial_records
    ADD COLUMN visitor_id INT DEFAULT NULL AFTER family_name,
    ADD INDEX idx_visitor (visitor_id),
    ADD CONSTRAINT fk_burial_visitor FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE SET NULL;
