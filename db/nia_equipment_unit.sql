-- Add 'accomplished' as a possible value for status in transportation_requests if not already present (if using ENUM, alter the column)
-- Also ensure drivers.status allows 'Available' (should already be present)

-- If status is VARCHAR, no change needed. If ENUM, run:
ALTER TABLE transportation_requests MODIFY status ENUM('pending','approved','rejected','accomplished') NOT NULL DEFAULT 'pending';

-- If drivers.status is ENUM, ensure 'Available' is present:
ALTER TABLE drivers MODIFY status ENUM('Available','On Trip','Inactive') NOT NULL DEFAULT 'Available';

-- Create a new table for driver travel logs if not exists
CREATE TABLE IF NOT EXISTS driver_travel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    transportation_request_id INT NOT NULL,
    log_date DATE NOT NULL,
    time_from TIME,
    time_to TIME,
    requester_name VARCHAR(100),
    requesting_unit VARCHAR(100),
    purpose TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (transportation_request_id) REFERENCES transportation_requests(id) ON DELETE CASCADE
);
-- (Optional) You may want to backfill this table with existing transportation_requests if needed.
