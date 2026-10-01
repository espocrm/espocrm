# W5D4 — Activity Summary Dashboard & REST API

## Objective

Implement an end-to-end Activity Summary dashboard feature in EspoCRM v10.0.9 that aggregates activities (`Meeting`, `Call`, `Task`, `Email`) per `Account` for the past 30 days and renders them using a native Flotr2 bar chart dashlet on the dashboard.

---

## 1. Backend Activity Summary Service

- **File:** `custom/Espo/Custom/Services/ActivitySummaryService.php`
- **Class:** `Espo\Custom\Services\ActivitySummaryService`
- **Architecture:** Reuses EspoCRM's native `SelectBuilderFactory` and `QueryExecutor` with `withStrictAccessControl()`.
- **Account Relationship Handling:**
  - `Meeting`: Linked via `parentType = 'Account'` and `parentId = account.id` (no `account_id` column in `meeting` table).
  - `Email`: Linked via `parentType = 'Account'` and `parentId = account.id` (no `account_id` column in `email` table).
  - `Call` & `Task`: Handles both `parentType = 'Account' AND parentId IS NOT NULL` and direct `accountId IS NOT NULL` fallbacks using mutually exclusive clauses to prevent duplicate counting.
- **Security & ACL:** Checks `Acl::checkScope(...)` for each activity scope and batch-queries `Account` names respecting row-level ACL permissions.
- **Soft Deletes:** Explicitly excludes soft-deleted records (`deleted = false`).

---

## 2. REST API Endpoint

- **Controller:** `custom/Espo/Custom/Controllers/ActivitySummary.php`
- **Route Definition:** `custom/Espo/Custom/Resources/routes.json`
- **Method:** `GET`
- **Path:** `/api/v1/ActivitySummary`
- **Parameters:**
  - `days` (optional, integer): Time window in days. Defaults to `30`.
- **Error Handling:**
  - `400 Bad Request`: If `days <= 0`.
  - `401 Unauthorized`: For unauthenticated requests.
  - `403 Forbidden`: When user lacks read access to `Account`.
- **Payload Structure:**
  ```json
  {
    "list": [
      {
        "accountId": "6aba41452c2dccf82",
        "accountName": "Tech Solutions",
        "activityType": "Meeting",
        "count": 1
      }
    ],
    "byAccount": [
      {
        "accountId": "6aba41452c2dccf82",
        "accountName": "Tech Solutions",
        "Meeting": 1,
        "Call": 0,
        "Task": 0,
        "Email": 0,
        "total": 1
      }
    ],
    "total": {
      "Meeting": 1,
      "Call": 0,
      "Task": 0,
      "Email": 0,
      "all": 1
    },
    "meta": {
      "days": 30,
      "from": "2026-09-01 00:00:00",
      "accountCount": 1,
      "activityTypes": [
        "Meeting",
        "Call",
        "Task",
        "Email"
      ]
    }
  }
  ```

---

## 3. 30-Day Filtering

- Computes cutoff threshold: `$thresholdDate = (new DateTime())->modify("-{$days} days")->format('Y-m-d 00:00:00')`.
- Activity date filters:
  - `Meeting`: `dateStart >= thresholdDate`
  - `Call`: `dateStart >= thresholdDate`
  - `Task`: `dateEnd >= thresholdDate OR dateStart >= thresholdDate OR createdAt >= thresholdDate`
  - `Email`: `dateSent >= thresholdDate OR (dateSent IS NULL AND createdAt >= thresholdDate)`

---

## 4. Custom Dashlet & Flotr2 Chart

- **Metadata:** `custom/Espo/Custom/Resources/metadata/dashlets/ActivitySummary.json`
  - Registered as `ActivitySummary` dashlet with configurable `title` and `days` (default 30).
  - Scope: `Account`.
- **Translation:** `custom/Espo/Custom/Resources/i18n/en_US/Global.json`
  - Dashlet name: `Activity Summary`.
- **View:** `client/custom/src/views/dashlets/activity-summary.js`
  - Extends `crm:views/dashlets/abstract/chart`.
  - Consumes `/api/v1/ActivitySummary?days={days}` via `Espo.Ajax.getRequest`.
  - Uses native **Flotr2** bar chart with stacked series:
    - `Meeting` (Blue `#4E6CAD`)
    - `Call` (Sky Blue `#6FA8D6`)
    - `Task` (Amber `#EDC555`)
    - `Email` (Coral `#DE6666`)
  - Features interactive mouse hover tooltips, responsive resizing, custom color legend, and zero-data state (`showNoData`).
  - No external charting dependencies installed (pure Flotr2).

---

## 5. Verification Results

| Check / Test | Command / Method | Expected | Actual Result |
|---|---|---|---|
| Service Unit Tests | `php tests/unit/verify_activity_summary.php` | 22/22 pass | **PASS (22/22)** |
| REST API Unit Tests | `php tests/unit/verify_activity_summary_api.php` | 18/18 pass | **PASS (18/18)** |
| Live API Authenticated | `curl.exe -i -u "admin:..." /api/v1/ActivitySummary` | HTTP 200 OK | **PASS (HTTP 200 OK, valid JSON)** |
| Custom Period (`days=60`) | `curl.exe -u "admin:..." /api/v1/ActivitySummary?days=60` | `meta.days = 60` | **PASS (days: 60 returned)** |
| Invalid Period Validation | `curl.exe -i -u "admin:..." /api/v1/ActivitySummary?days=-5` | HTTP 400 Bad Request | **PASS (HTTP 400 Bad Request)** |
| Unauthenticated Access | `curl.exe -i /api/v1/ActivitySummary` | HTTP 401 Unauthorized | **PASS (HTTP 401 Unauthorized)** |
| Metadata Registration | `GET /api/v1/Metadata` | `dashlets.ActivitySummary` | **PASS (Registered)** |
| Translation Registration | `GET /api/v1/I18n` | `Activity Summary` | **PASS (Registered)** |
| Client JS Delivery | `GET /client/custom/.../activity-summary.js` | HTTP 200 JS | **PASS (HTTP 200 JS delivered)** |
| JS Syntax Check | `node -c .../activity-summary.js` | Syntax Valid | **PASS (No errors)** |

---

## 6. Limitations

- Only accounts with activities in the selected time window (or matched through parent/account relationships) are charted to prevent visual clutter on the x-axis.
- For high numbers of accounts (>50), horizontal scrolling or pagination can be added in future iterations.
