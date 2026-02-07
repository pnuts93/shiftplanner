CREATE TABLE shifts (
    id SMALLINT PRIMARY KEY,
    shift_start_h INT, -- hour of the day (0-23)
    shift_end_h INT,   -- hour of the day (0-23)
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(50) NOT NULL,
    max_workers INT NOT NULL,
    min_experienced_workers INT NOT NULL,
    max_experienced_workers INT NOT NULL,
    is_working_shift BOOLEAN NOT NULL
);

ALTER TABLE assignments
    ADD FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    ADD COLUMN is_marked_important BOOLEAN DEFAULT FALSE NOT NULL,
    ADD COLUMN user_comment TEXT DEFAULT '' NOT NULL;

CREATE TABLE month_blockers (
    year INT NOT NULL,
    month INT NOT NULL,
    PRIMARY KEY (year, month)
);
