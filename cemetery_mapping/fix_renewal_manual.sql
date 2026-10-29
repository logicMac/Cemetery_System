-- Manual Fix for Renewal System
-- Run these commands one by one in phpMyAdmin if the automated fix doesn't work

-- Step 1: Make visitor_id nullable (THIS IS THE CRITICAL FIX)
ALTER TABLE plot_renewal_requests 
MODIFY COLUMN visitor_id INT DEFAULT NULL;

-- Step 2: Add or_number column (skip if error says it already exists)
ALTER TABLE plot_renewal_requests 
ADD COLUMN or_number VARCHAR(100) DEFAULT NULL AFTER requested_years;

-- Step 3: Drop old foreign key (get the actual name first)
-- Run this query first to see the constraint name:
-- SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
-- WHERE TABLE_NAME = 'plot_renewal_requests' AND COLUMN_NAME = 'visitor_id' 
-- AND REFERENCED_TABLE_NAME = 'visitors';

-- Then replace 'plot_renewal_requests_ibfk_1' with the actual name if different:
ALTER TABLE plot_renewal_requests 
DROP FOREIGN KEY plot_renewal_requests_ibfk_1;

-- Step 4: Re-add foreign key with SET NULL
ALTER TABLE plot_renewal_requests
ADD CONSTRAINT plot_renewal_requests_ibfk_1
  FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE SET NULL;

-- Done! Now test the renewal system.
