WITH
existing_assignments AS (
  SELECT a.user_id, u.employment_date, u.has_specialization, au.is_counted
  FROM assignments a
  JOIN users u ON u.id = a.user_id
  JOIN approved_users au ON au.email = u.email
  WHERE a.date = :shift_date AND a.shift_id = :shift_id AND au.is_counted = true
),
expert_count AS (
  SELECT COUNT(*) AS expert_count
  FROM existing_assignments
  WHERE has_specialization = true
     OR employment_date <= CURRENT_DATE - INTERVAL '5 years'
),
assignment_count AS (
  SELECT COUNT(*) AS total FROM existing_assignments
),
new_user_info AS (
  SELECT id, u.has_specialization, u.employment_date, au.is_counted
  FROM users u
  JOIN approved_users au ON au.email = u.email
  WHERE id = :user_id
),
can_insert AS (
  SELECT
    is_counted = false 
    OR (
      assignment_count.total < 4
      AND (
        new_user_info.has_specialization = true
        OR expert_count.expert_count >= 1
        OR assignment_count.total < 3
      )
    ) AS allowed
  FROM assignment_count, expert_count, new_user_info
)
-- Only insert if allowed = true
INSERT INTO assignments (user_id, date, shift_id)
SELECT :user_id, :shift_date, :shift_id
FROM can_insert
WHERE allowed
ON CONFLICT (user_id, date) DO UPDATE SET shift_id = :shift_id
RETURNING *;
