# Week 5 Day 3 - Enterprise CRM Architecture

## CRM Workflow

Lead
↓
Opportunity
↓
Account
↓
Activity
↓
Closed Won / Closed Lost

## Workflow Automation

- Automatically assign new Leads to Sales Team.
- Send email notification when Opportunity is created.
- Schedule follow-up Activity after Lead qualification.

## Reporting

- Total Leads
- Open Opportunities
- Closed Opportunities
- Revenue by Account

## Entity Manager

Entity Manager allows administrators to create and customize entities, fields, layouts, and relationships without writing database code.

## Permission System

EspoCRM uses Role-Based Access Control (RBAC) to manage Create, Read, Update, Delete, Export, and Assignment permissions.

## Workflow vs BPM

Workflow:
- Event-based automation
- Simple business rules

BPM:
- Multi-step business process
- Approvals
- Decision points
- Timers