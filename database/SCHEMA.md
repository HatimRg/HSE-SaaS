# HSE SaaS Database Schema — Complete Reference

## Architecture Principles

1. **KPI values are DERIVED, never manually entered** — computed from `hse_events`, `event_actions`, `work_permits`, `inspections`, `workers`, `daily_headcounts`
2. **No duplicated tables** — training/awareness/competency unified via `worker_documents`; observations/incidents/near-misses unified via `hse_events`
3. **No boolean explosion** — permit types use `permit_type_assignments` pivot table instead of `is_hot_work`, `is_confined_space`, etc.
4. **All modules connected through risk** — `hse_events.risk_item_id`, `work_permits.risk_assessment_id`, `risk_items` link everything
5. **Multi-tenant** — every table has `company_id`; multi-project via `project_id` or pivot tables
6. **Soft deletes** on all major entities (`deleted_at`)
7. **Polymorphic actions** — `event_actions` serves both `hse_events` and `inspections`

---

## Entity Relationship Diagram (Text)

```
companies ──────────────────────────────────────────────────────────
  │                                                                 │
  ├── users (role_id → roles)                                       │
  │     └── project_teams → projects                               │
  │                                                                 │
  ├── projects                                                      │
  │     ├── hse_events ──┐                                          │
  │     ├── work_permits │── risk_assessments ── risk_items ── hazards
  │     ├── inspections  │       (risk_item_id)                     │
  │     ├── daily_headcounts                                      │
  │     ├── worker_project_assignments → workers                    │
  │     ├── ppe_stocks → ppe_items                                  │
  │     ├── environmental_readings                                  │
  │     ├── waste_exports                                           │
  │     ├── community_reports                                        │
  │     └── kpi_values → kpi_definitions                            │
  │                                                                 │
  ├── event_actions (polymorphic: hse_event / inspection)           │
  │                                                                 │
  ├── workers                                                       │
  │     ├── worker_documents (training certs, medical, etc.)        │
  │     ├── worker_ppe_issues → ppe_items                           │
  │     ├── worker_sanctions                                         │
  │     └── training_participants → training_sessions               │
  │                                                                 │
  ├── community_posts                                               │
  │     ├── community_post_comments (threaded)                      │
  │     └── community_post_reactions                                │
  │                                                                 │
  ├── permit_types ← permit_type_assignments → work_permits         │
  │                                                                 │
  ├── inspection_templates → template_items                          │
  │     └── inspection_items (filled checklist)                     │
  │                                                                 │
  └── documents (library)                                           │
```

---

## Table Inventory

### Core (existing, unchanged)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `companies` | Multi-tenant root | id, name, domain, subscription_plan |
| `roles` | RBAC roles | id, name, permissions (JSON) |
| `users` | System users | id, company_id, role_id, first_name, last_name, email |
| `projects` | Construction projects | id, company_id, manager_id, name, code, status |
| `project_teams` | User ↔ Project pivot | project_id, user_id, role_in_project |

### Core (existing, modified)

| Table | Change |
|-------|--------|
| `work_permits` | + `risk_assessment_id` FK |
| `inspections` | + `inspection_template_id` FK |

### New Tables

| Table | Purpose | Module |
|-------|---------|--------|
| `hse_events` | Unified safety events (replaces sor_reports) | SOR/Events |
| `event_actions` | Corrective/preventive actions (polymorphic) | All modules |
| `hazards` | Master hazard catalog | Risk |
| `risk_assessments` | Assessment sessions per project | Risk |
| `risk_items` | Individual risk entries | Risk |
| `kpi_definitions` | Formula-based KPI specs | KPI Engine |
| `kpi_values` | Cached computed KPI values | KPI Engine |
| `permit_types` | Permit type catalog | Permits |
| `permit_type_assignments` | Permit ↔ Type pivot | Permits |
| `inspection_templates` | Reusable checklist templates | Inspections |
| `template_items` | Template checklist items | Inspections |
| `inspection_items` | Filled inspection checklist | Inspections |
| `worker_documents` | Unified document/cert storage | Workers/Training |
| `worker_project_assignments` | Worker ↔ Project pivot | Workers |
| `worker_sanctions` | Disciplinary records | Workers |
| `worker_ppe_issues` | PPE issuance records | PPE |
| `ppe_stocks` | PPE inventory per project | PPE |
| `environmental_readings` | Sensor/reading data | Environment |
| `waste_exports` | Waste disposal records | Environment |
| `community_reports` | External stakeholder reports | Community |
| `community_post_comments` | Threaded comments | Community |
| `community_post_reactions` | Post reactions | Community |
| `training_participants` | Training attendance | Training |

### Existing Tables (kept as-is)

| Table | Purpose |
|-------|---------|
| `workers` | Personnel records |
| `training_sessions` | Training events |
| `ppe_items` | PPE catalog |
| `daily_headcounts` | Daily workforce counts |
| `community_posts` | Internal social posts |
| `library_folders` | Document folders |
| `library_documents` | Uploaded documents |
| `notifications` | User notifications |
| `audit_logs` | System audit trail |

### Deprecated (to be migrated from)

| Table | Replacement |
|-------|-------------|
| `sor_reports` | `hse_events` (type = observation/incident/near_miss/etc.) |
| `kpi_reports` | `kpi_definitions` + `kpi_values` (computed, not manual) |

---

## Relationship Map

### hse_events (Central Event Hub)

```
hse_events.company_id       → companies.id
hse_events.project_id       → projects.id
hse_events.reported_by      → users.id
hse_events.assigned_to       → users.id
hse_events.risk_item_id      → risk_items.id  (← connects to risk engine)
hse_events ← event_actions   (source_type='hse_event', source_id=hse_events.id)
```

### Risk Engine

```
hazards.company_id           → companies.id
risk_assessments.company_id  → companies.id
risk_assessments.project_id  → projects.id
risk_assessments.created_by   → users.id
risk_assessments.approved_by  → users.id
risk_items.hazard_id          → hazards.id
risk_items.risk_assessment_id → risk_assessments.id
risk_items.responsible_person_id → users.id
risk_items ← hse_events.risk_item_id  (events link back to risks)
risk_assessments ← work_permits.risk_assessment_id  (permits link to risks)
```

### Work Permits

```
work_permits.company_id       → companies.id
work_permits.project_id       → projects.id
work_permits.user_id          → users.id (creator)
work_permits.risk_assessment_id → risk_assessments.id
work_permits.issuing_authority_id → users.id
work_permits.approver_id      → users.id
work_permits.fire_watch_assigned_to → users.id
work_permits.renewal_of       → work_permits.id (self-ref)
work_permits ← permit_type_assignments.permit_id → permit_types.id
```

### Inspections

```
inspections.company_id              → companies.id
inspections.project_id              → projects.id
inspections.user_id                 → users.id
inspections.inspection_template_id  → inspection_templates.id
inspections ← inspection_items.inspection_id
inspection_items.template_item_id   → template_items.id
inspections ← event_actions (source_type='inspection')
```

### Workers

```
workers.company_id → companies.id
workers ← worker_project_assignments.worker_id → projects.id
workers ← worker_documents.worker_id
workers ← worker_ppe_issues.worker_id → ppe_items.id
workers ← worker_sanctions.worker_id
workers ← training_participants.worker_id → training_sessions.id
```

### KPI Engine

```
kpi_definitions.company_id → companies.id
kpi_values.company_id       → companies.id
kpi_values.kpi_definition_id → kpi_definitions.id
kpi_values.project_id       → projects.id
```

---

## KPI Derivation Formulas

All KPIs are computed from operational data — **no manual entry**.

| KPI | Formula | Data Sources |
|-----|---------|-------------|
| **TRIR** | (Recordable Incidents × 200,000) / Total Hours Worked | `hse_events WHERE type='incident' AND severity IN ('high','critical')`, `daily_headcounts.total_count × 8` |
| **LTIFR** | (Lost Time Incidents × 1,000,000) / Total Hours Worked | `hse_events WHERE type='incident' AND lost_time > 0`, `daily_headcounts` |
| **Near Miss Rate** | Near Misses / Total Events | `hse_events WHERE type='near_miss'`, `hse_events COUNT` |
| **Observation Rate** | Observations / Total Workers | `hse_events WHERE type='observation'`, `workers WHERE status='active'` |
| **Action Closure Rate** | Closed Actions / Total Actions | `event_actions WHERE status IN ('completed','verified')`, `event_actions COUNT` |
| **Overdue Actions** | Actions WHERE due_date < NOW() AND status NOT IN ('completed','verified') | `event_actions` |
| **Inspection Compliance** | Passed Inspections / Total Inspections | `inspections WHERE result='pass'`, `inspections COUNT` |
| **Permit Compliance** | Active Permits Not Expired / Total Active Permits | `work_permits WHERE status='active' AND expiry_date >= NOW()` |
| **Training Completion** | Workers Trained / Total Active Workers | `training_participants WHERE status='attended'`, `workers WHERE status='active'` |
| **PPE Compliance** | Workers with Required PPE / Total Workers | `worker_ppe_issues`, `workers` |
| **Environmental Exceedance** | Readings Above Threshold / Total Readings | `environmental_readings WHERE is_exceedance=true` |
| **Waste Diversion** | Recycled Waste / Total Waste | `waste_exports WHERE treatment='recycling'`, `waste_exports SUM(quantity)` |

---

## Migration Order (Dependency Chain)

```
001  companies                    (root)
002  roles                        (standalone)
003  users                        (→ companies, roles)
004  projects                     (→ companies, users)
005  project_teams                (→ projects, users)
006  workers                      (→ companies)
007  training_sessions            (→ companies, projects, users)
008  ppe_items                    (→ companies)
009  daily_headcounts             (→ companies, projects, users)
010  community_posts              (→ companies, users)
011  library_folders / documents  (→ companies, projects, users)
012  notifications                (→ companies, users)
013  audit_logs                   (→ companies, users)

--- NEW MIGRATIONS ---

020  hazards                      (→ companies)
021  risk_assessments + risk_items (→ companies, projects, users, hazards)
022  hse_events                   (→ companies, projects, users, risk_items)
023  event_actions                (→ companies, users) [polymorphic]
024  kpi_definitions + kpi_values (→ companies, projects)
025  permit_types + assignments   (→ companies, work_permits)
026  inspection_templates/items   (→ companies, users, inspections)
027  worker_documents             (→ companies, workers, training_sessions)
028  worker_project_assignments   (→ companies, workers, projects)
029  worker_sanctions             (→ companies, workers, projects, users)
030  worker_ppe_issues + stocks   (→ companies, workers, projects, ppe_items)
031  environmental_readings       (→ companies, projects, users)
032  waste_exports                (→ companies, projects, users)
033  community_reports            (→ companies, projects, users)
034  community_post_comments      (→ companies, community_posts, users)
035  community_post_reactions     (→ community_posts, users)
036  training_participants        (→ companies, workers, training_sessions)
```

---

## Data Migration Notes

### sor_reports → hse_events

```sql
INSERT INTO hse_events (company_id, project_id, reported_by, reference, type, severity, status,
    title, description, location, assigned_to, due_date, closed_at, occurred_at, photos, attachments,
    created_at, updated_at, deleted_at)
SELECT company_id, project_id, user_id, reference,
    type, severity,
    CASE status WHEN 'in-progress' THEN 'in_progress' ELSE status END,
    title, description, location, responsible_person_id, due_date, completed_at, date,
    photos, attachments, created_at, updated_at, deleted_at
FROM sor_reports;
```

### kpi_reports → kpi_values (computed)

The old `kpi_reports` table with manual `injuries`, `first_aids`, etc. columns is deprecated.
New approach: `kpi_definitions` define formulas, `kpi_values` are computed by scheduled jobs
from `hse_events`, `daily_headcounts`, etc. No manual entry.
