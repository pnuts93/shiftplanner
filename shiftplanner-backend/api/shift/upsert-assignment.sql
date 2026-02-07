WITH
existing_assignments AS (
  SELECT a.user_id, a.shift_id, u.employment_date, u.has_specialization, au.is_counted, s.*
  FROM assignments a
  JOIN users u ON u.id = a.user_id
  JOIN approved_users au ON au.email = u.email
  JOIN shifts s ON s.id = a.shift_id
  WHERE a.date = :shift_date AND a.shift_id = :shift_id AND au.is_counted = true AND s.is_working_shift = true
),
expert_count AS (
  SELECT COUNT(*) AS expert_count
  FROM existing_assignments
  WHERE has_specialization = true
     OR employment_date <= :shift_date - CAST(:experienced_years_threshold AS INTERVAL)
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
is_new_user_expert AS (
  SELECT
      new_user_info.has_specialization = true
      OR new_user_info.employment_date <= :shift_date - CAST(:experienced_years_threshold AS INTERVAL)
  AS is_expert
  FROM new_user_info
),
shift_info AS (
  SELECT max_workers, min_experienced_workers, max_experienced_workers, is_working_shift
  FROM shifts
  WHERE id = :shift_id
),
can_insert AS (
  SELECT
    new_user_info.is_counted = false
    OR shift_info.is_working_shift = false
    OR (
      assignment_count.total < shift_info.max_workers
      AND (
        (
          is_new_user_expert.is_expert = false
          AND (
            expert_count.expert_count >= shift_info.min_experienced_workers
            OR assignment_count.total < (shift_info.max_workers - shift_info.min_experienced_workers)
          )
        ) OR (
          is_new_user_expert.is_expert = true
          AND expert_count.expert_count < shift_info.max_experienced_workers
        )
      )
    ) AS allowed
  FROM assignment_count, expert_count, new_user_info, is_new_user_expert, shift_info
)
-- Only insert if allowed = true
INSERT INTO assignments (user_id, date, shift_id)
SELECT :user_id, :shift_date, :shift_id
FROM can_insert
WHERE allowed
ON CONFLICT (user_id, date) DO UPDATE SET shift_id = :shift_id
RETURNING *;
