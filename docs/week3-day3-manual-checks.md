# Week 3, Day 3: Manual Verification Notes

These are manual checks for the native Opportunity reporting and save-hook behavior found in this checkout. They do not verify optional Workflow/BPM modules.

## Opportunity stage hook

1. Sign in to the local EspoCRM instance with a user allowed to create and edit Opportunities.
2. Create an Opportunity with a valid Account, an open stage from the configured stage list, an amount, and a close date. Leave Probability empty.
3. Save and reopen the record. Confirm Probability was filled using the configured probability map for that stage.
4. Change the stage to another open stage and save. Confirm the stage change is retained and the last-stage value follows the `LastStage` hook behavior.

## Native report API

1. While authenticated as a user with Opportunity read access and access to the amount field, request:

   ```http
   GET /api/v1/Opportunity/action/reportByStage?dateFilter=ever
   ```

   The controller requires `dateFilter`; `ever` asks the date range helper for an unbounded range.

2. Confirm the response is grouped by configured Opportunity stage and contains aggregate converted amounts. Won and lost stages are intentionally excluded by `ByStage`.
3. Request the same action without `dateFilter`. Expected result: a client error (`400 Bad Request`) from the controller's input validation.
4. Repeat with a user who cannot read Opportunities or the amount field. Expected result: forbidden access (`403`), enforced by the controller/report service.
5. Capture a screenshot of the response or the report view and attach it to the lesson log after running these checks in the local instance.

## Report shape observed in source

| Entity | Filters | Columns / aggregation | Grouping |
| --- | --- | --- | --- |
| Opportunity | Date range on `closeDate`; won and lost stages excluded | `SUM(amountConverted)` returned as amount | `stage` |
| Opportunity sales pipeline | Date range on `closeDate`; lost stages excluded; optional team filter | `SUM(amountConverted)` returned as amount | `stage`, or `lastStage` when `useLastStage=true` |

## Evidence status

- Source review: completed against the files listed in `week3-day3-architecture.md`.
- Manual checks above: procedures documented; they still need to be run in the local instance.
- Screenshot: not captured in this source-only review.
- Workflow/BPM records and configurable report definitions: not present in this checkout; inspect the running instance only if those modules are installed.
