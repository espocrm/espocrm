# W5D2 API Documentation — Lead to Closure + API Inspection

## Overview

This document records the EspoCRM business process inspection and the three REST API calls completed for W5D2.

### Business Process

**Lead → Opportunity → Account → Activity**

Verified records:
- Lead: **Mr. Rahul Sharma**
- Opportunity: **Tech Solutions CRM Project**
- Account: **Tech Solutions**
- Activity: **Follow up with Rahul Sharma**

## API Inspection

### 1. Opportunity API

**Endpoint**

`GET /api/v1/Opportunity?select=id,name,stage,amount,probability&maxSize=10`

**Purpose:** Retrieve Opportunity records and inspect name, stage, amount, and probability.

**Verified response:**
- Total records: `1`
- Name: `Tech Solutions CRM Project`
- Stage: `Prospecting`
- Amount: `50000`
- Probability: `10`
- Account Name: `Tech Solutions`

**Result:** Successful.

### 2. Account API

**Endpoint**

`GET /api/v1/Account?select=id,name&maxSize=10`

**Purpose:** Retrieve Account records and verify the account associated with the opportunity.

**Verified response:**
- Total records: `1`
- Name: `Tech Solutions`
- Account ID matched the `accountId` returned by the Opportunity API.

**Result:** Successful.

### 3. Meeting API

**Endpoint**

`GET /api/v1/Meeting?select=id,name,status,dateStart,parentType,parentId&maxSize=10`

**Purpose:** Retrieve Meeting activity details and inspect its status, dates, and parent record.

**Verified response:**
- Total records: `1`
- Name: `Follow up with Rahul Sharma`
- Status: `Planned`
- Start: `2026-09-28 00:00:00`
- End: `2026-09-29 00:00:00`
- Parent Type: `Account`
- Parent Name: `Tech Solutions`

**Result:** Successful.

> Note: The API response shows the meeting's `parentType` as `Account` and `parentName` as `Tech Solutions`. This document records the API response as returned.

## Verification Summary

| Item | Status |
|---|---|
| EspoCRM running locally | Verified |
| Lead created and converted | Verified |
| Opportunity created | Verified |
| Account created | Verified |
| Activity/Meeting created | Verified |
| Opportunity API | Successful |
| Account API | Successful |
| Meeting API | Successful |

## Environment

- Application: EspoCRM
- Runtime: Docker / Docker Compose
- API Base URL: `http://localhost:8080/api/v1/`
- API Method: HTTP GET
- Client: PowerShell `curl.exe`

## Security Note

The API commands were executed locally using administrator authentication. Credentials are intentionally not included in this documentation.
