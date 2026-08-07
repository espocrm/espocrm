# SQL Queries

## Query 1

SELECT account_id, COUNT(*) AS total_activities
FROM activity
GROUP BY account_id;

## Query 2

SELECT type, COUNT(*) AS total
FROM activity
GROUP BY type;

## Query 3

SELECT *
FROM activity
ORDER BY created_at DESC
LIMIT 10;