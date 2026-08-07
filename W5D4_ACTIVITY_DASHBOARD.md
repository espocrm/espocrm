# Week 5 Day 4 - Activity Dashboard

## Objective

Build an Activity Dashboard using SQL queries and reporting concepts.

## Sample SQL Queries

### Activities per Account (Last 30 Days)

SELECT account_id, COUNT(*) AS total_activities
FROM activity
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY account_id;

### Activities Grouped by Type

SELECT type, COUNT(*) AS total
FROM activity
GROUP BY type;

### Top Accounts by Activity

SELECT account_id, COUNT(*) AS total
FROM activity
GROUP BY account_id
ORDER BY total DESC;

## Dashboard Components

- Bar Chart
- Activity Count
- Account Summary
- Activity Type Distribution

## Reporting Notes

- Use SQL aggregation for summaries.
- Display grouped activity data using charts.
- Refresh dashboard after new activity creation.