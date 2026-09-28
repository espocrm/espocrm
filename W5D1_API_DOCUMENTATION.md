# Week 5 – Day 1: EspoCRM Local REST API Testing Documentation

**Program:** Cynaris Solutions Full Stack Development Internship  
**Module:** Week 5 – Day 1: Fork, Clone & Local Setup — EspoCRM  
**Environment:** Local Docker Compose Stack (`espocrm`, `espocrm-db`, `espocrm-daemon`, `espocrm-websocket`)  
**Base URL:** `http://localhost:8080/api/v1`  
**Authentication Method:** HTTP Basic Authentication (`Authorization: Basic <base64-credentials>`)  

---

## 📌 Overview

This document records the verification and testing of the EspoCRM REST API on the local Docker deployment. All requests were performed over HTTP on port `8080` against the containerized EspoCRM backend. Credentials in this documentation are safely abstracted using environment variables and placeholders to prevent credential leakage.

---

## 🔐 Authentication & Security Protocol

To ensure secure execution in PowerShell without exposing plain-text credentials in logs or shell history, requests can utilize session-scoped variables:

```powershell
# Store credentials in session variables (or enter interactively)
$Username = "admin"
$Password = Read-Host -Prompt "Enter EspoCRM Password" -AsSecureString
$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($Password)
$PlainTextPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

# Credentials passed safely via curl.exe
curl.exe -s -u "${Username}:${PlainTextPassword}" "http://localhost:8080/api/v1/<ENDPOINT>"
```

Alternatively, commands can be parameterized with `<USERNAME>:<PASSWORD>` placeholders.

---

## 🧪 Documented API Calls

### 1. Authenticated User & Session Telemetry

* **Endpoint:** `http://localhost:8080/api/v1/App/user`
* **HTTP Method:** `GET`
* **Purpose:** Validates user credentials, confirms active session validity, and retrieves core user metadata (user ID, username, admin privileges, operational status) along with global application preferences, permissions, and localization settings.

#### PowerShell curl Command (Credentials Masked)
```powershell
curl.exe -s -u "<USERNAME>:<PASSWORD>" "http://localhost:8080/api/v1/App/user"
```

#### Response Summary
* **HTTP Status Code:** `200 OK`
* **Content-Type:** `application/json; charset=UTF-8`
* **Description:** Returns an object containing the authenticated `user` record (`id`, `userName: "admin"`, `type: "admin"`), assigned roles, localized date/time configurations, and system feature flags.

```json
{
  "user": {
    "id": "6aba2dfca19ef7d70",
    "name": "Admin",
    "userName": "admin",
    "type": "admin",
    "isAdmin": true,
    "isActive": true
  },
  "preferences": {
    "dateFormat": "YYYY-MM-DD",
    "timeFormat": "HH:mm",
    "timeZone": "UTC",
    "weekStart": 1,
    "defaultCurrency": "USD"
  },
  "acl": {
    "Account": true,
    "Opportunity": true,
    "Lead": true,
    "Contact": true
  }
}
```

---

### 2. Retrieve CRM Accounts Collection

* **Endpoint:** `http://localhost:8080/api/v1/Account?select=id,name&maxSize=10`
* **HTTP Method:** `GET`
* **Purpose:** Queries the CRM `Account` entity repository to retrieve active client/business account records. Employs query projection (`select=id,name`) to minimize payload bandwidth and limits pagination (`maxSize=10`).

#### PowerShell curl Command (Credentials Masked)
```powershell
curl.exe -s -u "<USERNAME>:<PASSWORD>" "http://localhost:8080/api/v1/Account?select=id,name&maxSize=10"
```

#### Response Summary
* **HTTP Status Code:** `200 OK`
* **Content-Type:** `application/json; charset=UTF-8`
* **Description:** Returns a collection payload with the total matching record count (`total: 1`) and an array (`list`) containing the requested account records with their unique entity IDs and organization names.

```json
{
  "total": 1,
  "list": [
    {
      "id": "6aba41452c2dccf82",
      "name": "Tech Solutions",
      "createdAt": "2026-09-28 10:28:21",
      "createdById": "6aba2dfca19ef7d70",
      "assignedUserId": null,
      "isStarred": false
    }
  ]
}
```

---

### 3. Retrieve CRM Opportunities Collection

* **Endpoint:** `http://localhost:8080/api/v1/Opportunity?select=id,name,stage&maxSize=10`
* **HTTP Method:** `GET`
* **Purpose:** Queries the sales deal pipeline (`Opportunity` entity) to retrieve opportunities filtered by deal stage, project name, and record identifier. Uses field filtering (`select=id,name,stage`) and record limit (`maxSize=10`).

#### PowerShell curl Command (Credentials Masked)
```powershell
curl.exe -s -u "<USERNAME>:<PASSWORD>" "http://localhost:8080/api/v1/Opportunity?select=id,name,stage&maxSize=10"
```

#### Response Summary
* **HTTP Status Code:** `200 OK`
* **Content-Type:** `application/json; charset=UTF-8`
* **Description:** Returns a collection payload detailing active sales pipeline entries, including total matching opportunities (`total: 1`), unique opportunity ID, deal name, current pipeline stage (`"Prospecting"`), and associated timestamps.

```json
{
  "total": 1,
  "list": [
    {
      "id": "6aba404e385fbe725",
      "name": "Tech Solutions CRM Project",
      "stage": "Prospecting",
      "createdAt": "2026-09-28 10:24:14",
      "amount": 50000,
      "amountCurrency": "USD",
      "amountConverted": 50000,
      "closeDate": "2026-12-31",
      "accountId": null,
      "accountName": null,
      "createdById": "6aba2dfca19ef7d70",
      "assignedUserId": null
    }
  ]
}
```

---

## 📊 Summary Table of Tested Endpoints

| # | Endpoint URL | HTTP Method | Target Entity / Resource | Primary Response Fields | Status |
|---|---|---|---|---|---|
| **1** | `/api/v1/App/user` | `GET` | User & Application Context | `user.id`, `user.userName`, `user.type`, `preferences`, `acl` | `200 OK` |
| **2** | `/api/v1/Account?select=id,name&maxSize=10` | `GET` | Accounts Collection | `total`, `list[].id`, `list[].name`, `list[].createdAt` | `200 OK` |
| **3** | `/api/v1/Opportunity?select=id,name,stage&maxSize=10` | `GET` | Opportunities Pipeline | `total`, `list[].id`, `list[].name`, `list[].stage` | `200 OK` |

---

## 🛡️ Verification & Security Compliance

1. **Zero Credential Exposure:** Neither passwords nor sensitive auth tokens are committed or hardcoded in this documentation.
2. **Standard REST Protocol:** All interactions followed EspoCRM's standard REST API specification over JSON.
3. **Application Integrity:** Executed strictly against live container endpoints; no core application files or framework classes were modified.

---

## ✅ W5D1 Verification

* **Docker Environment Verification:**
  - The local multi-container Docker Compose environment (`espocrm`, `espocrm-db`, `espocrm-daemon`, `espocrm-websocket`) was thoroughly verified in an operational and healthy state.
  - Persistent volumes for MariaDB and EspoCRM data (`data`, `custom`, `client/custom`) and environment configurations were confirmed functional.
* **API Testing Execution:**
  - All three documented REST API calls (`/api/v1/App/user`, `/api/v1/Account`, and `/api/v1/Opportunity`) were tested locally via PowerShell `curl.exe` and returned valid `200 OK` JSON responses.
* **CRM Core Workflow Completion:**
  - The required **Lead → Opportunity → Account → Activity** workflow was executed and verified within the local EspoCRM instance, validating end-to-end data creation, conversion, and relationship linking.
