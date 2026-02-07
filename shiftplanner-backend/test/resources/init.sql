-- Approved users
INSERT INTO approved_users
    (email, is_admin, is_counted) VALUES
    ('u1@test.te' , TRUE , TRUE ),  -- admin user
    ('u2@test.te' , FALSE, FALSE), -- not counted
    ('u3@test.te' , FALSE, FALSE), -- not counted
    ('u4@test.te' , FALSE, FALSE), -- not counted
    ('u5@test.te' , FALSE, TRUE ),
    ('u6@test.te' , FALSE, TRUE ),
    ('u7@test.te' , FALSE, TRUE ),
    ('u8@test.te' , FALSE, TRUE ),
    ('u9@test.te' , FALSE, TRUE ),
    ('u10@test.te', FALSE, TRUE );

-- Users
INSERT INTO users
    (id, email, password, is_email_confirmed, fname, lname, employment_date, has_specialization, login_attempts, is_notified_shift_change) VALUES
    (1 , 'u1@test.te' , 'password1' , TRUE, 'User', 'One'  , '2025-01-01', FALSE, 0, FALSE), -- admin user, not experienced
    (2 , 'u2@test.te' , 'password2' , TRUE, 'User', 'Two'  , '2025-01-01', FALSE, 0, FALSE), -- not counted, not experienced
    (3 , 'u3@test.te' , 'password3' , TRUE, 'User', 'Three', '2025-01-01', TRUE , 0, FALSE), -- not counted, experienced (specialization)
    (4 , 'u4@test.te' , 'password4' , TRUE, 'User', 'Four' , '2020-01-01', FALSE, 0, FALSE), -- not counted, experienced (date of employment)
    (5 , 'u5@test.te' , 'password5' , TRUE, 'User', 'Five' , '2025-01-01', FALSE, 0, FALSE), -- not experienced
    (6 , 'u6@test.te' , 'password6' , TRUE, 'User', 'Six'  , '2025-01-01', FALSE, 0, FALSE), -- not experienced
    (7 , 'u7@test.te' , 'password7' , TRUE, 'User', 'Seven', '2025-01-01', FALSE, 0, FALSE), -- not experienced
    (8 , 'u8@test.te' , 'password8' , TRUE, 'User', 'Eight', '2025-01-01', TRUE , 0, FALSE), -- experienced (specialization)
    (9 , 'u9@test.te' , 'password9' , TRUE, 'User', 'Nine' , '2025-01-01', TRUE , 0, FALSE), -- experienced (specialization)
    (10, 'u10@test.te', 'password10', TRUE, 'User', 'Ten'  , '2020-01-01', FALSE, 0, FALSE); -- experienced (date of employment)

-- Shifts
INSERT INTO shifts
    (id, shift_start_h, shift_end_h, name, display_name, max_workers, min_experienced_workers, max_experienced_workers, is_working_shift) VALUES
    (1, 8   , 16  , 'morning', 'Morning Shift', 4, 1, 2, TRUE ),
    (2, NULL, NULL, 'leave'  , 'Leave'        , 0, 0, 0, FALSE);