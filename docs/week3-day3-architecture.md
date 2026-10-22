# Week 3, Day 3: CRM Workflow and Reporting Architecture

## Scope

This note records what is present in the EspoCRM source checkout. The checked-in application contains native CRM hooks and Opportunity reports. A source search did not find Workflow or BPM modules in this checkout, so this note does not invent rules or process flows that cannot be inspected here. Availability of those administrator features depends on the installed edition and extensions.

## Architecture map

```text
CRM request
  -> API controller (application/Espo/Modules/Crm/Controllers/Opportunity.php)
  -> report service (application/Espo/Modules/Crm/Tools/Opportunity/Report/*)
  -> access-aware select builder
  -> ORM EntityManager / query executor
  -> database
```

Record lifecycle logic follows a separate path:

```text
Opportunity save
  -> ORM repository and save hooks
  -> stage/probability business rules
  -> persisted Opportunity
```

## Findings from the code

1. **The ORM is the data access layer.** `application/Espo/ORM/EntityManager.php` owns repository, mapper, query, and transaction collaborators. It receives an `EventDispatcher`; the dispatcher shown in `application/Espo/ORM/EventDispatcher.php` publishes metadata-update events so the entity manager can clear cached repositories and mappers. This dispatcher is not the CRM workflow engine.

2. **CRM save hooks express record-level automation.** `application/Espo/Modules/Crm/Hooks/Opportunity/Probability.php` fills in probability for a new Opportunity when it was not supplied, using the configured stage probability map. `LastStage.php` reacts when stage changes and tracks the last non-lost stage. These hooks implement focused domain behavior during saves; they are not evidence of configurable BPM flows.

3. **Native reporting is explicit, API-backed CRM code.** `application/Espo/Modules/Crm/Controllers/Opportunity.php` exposes report actions for lead source, stage, monthly sales, and sales pipeline. Each action validates `dateFilter`, constructs a `DateRange`, and delegates to a report class. The report classes check read access to Opportunity and the amount field, use a strict-access select builder, and aggregate the converted amount. `ByStage` groups by stage and excludes won/lost stages; `SalesPipeline` can group by current or last stage and optionally filter by team.

4. **Metadata supplies stage behavior.** Opportunity stage options and probability mappings are read from entity metadata. The reporting code uses the same stage definitions to order report results and determine which stages count as won or lost.

## Workflow, BPM, and report-builder boundary

The `application/Espo` source tree includes hook interfaces and CRM report code, but no Workflow or BPM implementation was found in this checkout. Therefore the requested review of three workflow rules, two BPM flows, and two configurable Reports cannot be completed from these source files alone. If those menus are present in the running instance, inspect their configured records in the UI and record the actual trigger, condition, action, entity, filters, columns, and grouping; do not infer their configuration from core hooks or Opportunity report endpoints.

## Practical architecture takeaways

- Use a hook for a small, deterministic rule tied directly to an entity save.
- Put reusable report query and aggregation behavior in a report service, not in the controller.
- Keep API authorization checks at the endpoint and enforce field-level access in the query/report layer as well.
- Treat Workflow/BPM as configurable automation only when the corresponding module is installed; ordinary save hooks are not a substitute for that feature.

## Source files reviewed

- `application/Espo/ORM/EntityManager.php`
- `application/Espo/ORM/EventDispatcher.php`
- `application/Espo/Modules/Crm/Controllers/Opportunity.php`
- `application/Espo/Modules/Crm/Hooks/Opportunity/Probability.php`
- `application/Espo/Modules/Crm/Hooks/Opportunity/LastStage.php`
- `application/Espo/Modules/Crm/Tools/Opportunity/Report/DateRange.php`
- `application/Espo/Modules/Crm/Tools/Opportunity/Report/ByStage.php`
- `application/Espo/Modules/Crm/Tools/Opportunity/Report/SalesPipeline.php`
