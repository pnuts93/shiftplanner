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
     OR employment_date <= CURRENT_DATE - CAST(:experienced_years_threshold AS INTERVAL)
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
      assignment_count.total < :max_people_per_shift
      AND (
        new_user_info.has_specialization = true
        OR new_user_info.employment_date <= CURRENT_DATE - CAST(:experienced_years_threshold AS INTERVAL)
        OR expert_count.expert_count >= :min_experts_per_shift
        OR assignment_count.total < (:max_people_per_shift - 1)
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
