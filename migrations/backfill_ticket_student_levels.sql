UPDATE tickets t
JOIN ticket_events te
  ON te.ticket_id = t.id
 AND te.`event` = 'ticket_issued'
SET t.student_level = JSON_UNQUOTE(JSON_EXTRACT(te.meta, '$.student_level'))
WHERE t.student_level IS NULL
  AND JSON_VALID(te.meta)
  AND JSON_UNQUOTE(JSON_EXTRACT(te.meta, '$.student_level')) IN ('shs', 'college');
