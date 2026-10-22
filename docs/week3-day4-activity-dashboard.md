# Week 3, Day 4: Activities per Account Dashboard

## What was added

- A custom dashboard chart for Calls, Meetings, and Emails linked directly to Accounts during the last 30 days.
- A custom read API at `GET /api/v1/ActivitySummary/action/report`.
- A top-10 account bar chart, grouped by activity type. Each source query and the Account-name query use EspoCRM strict access control.
- Dashlet registration at `custom/Espo/Custom/Resources/metadata/dashlets/ActivitySummary.json`.

The report uses dateStart for Calls and Meetings and dateSent for Emails. Activities linked to Contacts or other parent types are excluded, because this report is specifically per Account.

## SQL for Report Builder review

The EspoCRM ORM report endpoint uses the equivalent grouped queries below. This version is written for MariaDB/MySQL and is suitable for manually checking the report totals in a database console. Use the same `from` and `to` timestamps for the three date filters.

```sql
SELECT a.name AS account,
       SUM(x.activity_type = 'Call') AS calls,
       SUM(x.activity_type = 'Meeting') AS meetings,
       SUM(x.activity_type = 'Email') AS emails,
       COUNT(*) AS total
FROM (
    SELECT parent_id, 'Call' AS activity_type
    FROM `call`
    WHERE deleted = 0 AND parent_type = 'Account'
      AND date_start BETWEEN :from AND :to
    UNION ALL
    SELECT parent_id, 'Meeting' AS activity_type
    FROM `meeting`
    WHERE deleted = 0 AND parent_type = 'Account'
      AND date_start BETWEEN :from AND :to
    UNION ALL
    SELECT parent_id, 'Email' AS activity_type
    FROM `email`
    WHERE deleted = 0 AND parent_type = 'Account'
      AND date_sent BETWEEN :from AND :to
) AS x
JOIN `account` a ON a.id = x.parent_id AND a.deleted = 0
GROUP BY a.id, a.name
ORDER BY COUNT(*) DESC, a.name ASC
LIMIT 10;
```

Run it with `:from` set to the timestamp from 30 days ago and `:to` set to the current timestamp. The custom API intentionally uses the EspoCRM query builder instead of executing raw SQL, so ACL rules are applied to each entity query.

## Manual verification

1. Clear EspoCRM cache / run the standard rebuild after adding custom PHP, client JS, and metadata.
2. Open `GET /api/v1/ActivitySummary/action/report` as a CRM user with read access to Account, Call, Meeting, and Email.
3. Compare the API totals with the SQL result above using the identical timestamp.
4. Add **Activities per Account (Last 30 Days)** from the dashboard's Add Dashlet dialog and confirm the three series are visible.
5. Check an account with one known Call, Meeting, and Email, then verify the three values against its records.
6. Confirm a user without read access receives a forbidden response and cannot view the dashlet data.

## Review notes

- The API returns at most ten accounts ranked by total activity.
- A zero-activity period displays EspoCRM's standard no-data state.
- The chart labels and tooltip values are escaped/formatted through EspoCRM's chart helpers.
- Screenshots and live-instance results should be attached after completing the manual steps above; this source checkout does not include a connected CRM login session.
