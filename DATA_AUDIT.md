# HSE SaaS — Data Point Audit

> Maps every data point in the system to its source, current usage, and potential additional applications.

---

## 1. DATA POINT INVENTORY

### Core Entity Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **TRIR** | kpi_values (computed) | value | ✅ KPI page, Dashboard gauge, OSHA page | Benchmarking across projects, email alerts |
| **LTIFR** | kpi_values (computed) | value | Dashboard gauge, OSHA page | Same as TRIR, regulatory reporting |
| **Near Miss Rate** | kpi_values (computed) | value | Dashboard | Safety culture scoring, leading indicator alerts |
| **Action Closure Rate** | kpi_values (computed) | value | Dashboard | Manager performance KPI, escalation triggers |
| **Inspection Compliance** | kpi_values (computed) | value | Dashboard, Compliance | Project risk scoring, client reporting |
| **Permit Compliance** | kpi_values (computed) | value | Dashboard | Permit renewal automation, audit readiness |
| **Training Completion** | kpi_values (computed) | value | Dashboard, Training | Worker eligibility for assignments, compliance gaps |
| **PPE Compliance** | kpi_values (computed) | value | Dashboard | Reorder automation, worker assignment checks |

### Event Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Event count by type** | hse_events | type | Dashboard charts, SOR page | ✅ Safety culture heatmap, type-specific interventions |
| **Event severity** | hse_events | severity | Dashboard, SOR page | Risk-based prioritization, auto-escalation |
| **Event status** | hse_events | status | Dashboard, SOR page | SLA tracking, overdue alerts, bottleneck detection |
| **Event location** | hse_events | location | SOR detail | Location-based risk heatmaps, geographic clustering |
| **Event occurrence time** | hse_events | occurred_at | Dashboard trends | Time-of-day analysis, shift-based risk patterns |
| **Escalation level** | hse_events | escalation_level | — (unused) | Auto-escalation rules, management visibility |
| **Risk item linkage** | hse_events | risk_item_id | — (unused) | Risk-event correlation, risk verification |
| **Photos/Attachments** | hse_events | photos, attachments | SOR detail | AI-based hazard detection, evidence archive |
| **Assigned user** | hse_events | assigned_to | Dashboard activity | Workload balancing, response time tracking |

### Action Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Action type** | event_actions | type (corrective/preventive) | ✅ HseEventController (addAction) | CAPA ratio analysis, preventive culture scoring |
| **Action priority** | event_actions | priority | — (unused) | Priority-based SLA, resource allocation |
| **Action status** | event_actions | status | Dashboard stats, EventActionController | Bottleneck detection, overdue escalation |
| **Action due date** | event_actions | due_date | Alerts | SLA compliance, auto-escalation |
| **Action completion time** | event_actions | completed_at - created_at | — (unused) | Response time analytics, team efficiency |
| **Polymorphic source** | event_actions | source_type, source_id | — (unused) | Cross-module action tracking |

### Worker Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Worker status** | workers | status | Dashboard count | Workforce availability, project staffing |
| **Worker function/role** | workers | function | Workers page | Role-based risk analysis, training targeting |
| **Medical fitness** | workers | medical_fitness_date | Alerts | Expiry tracking, assignment eligibility |
| **CIN (national ID)** | workers | cin (encrypted) | Worker detail | Duplicate detection, regulatory reporting |
| **Project assignments** | worker_project_assignments | project_id, role, status | — (unused) | Multi-project workload, cross-project risk |
| **Documents** | worker_documents | type, status, expiry_date | — (unused) | Expiry alerts, compliance dashboard, onboarding checklist |
| **PPE issues** | worker_ppe_issues | ppe_item_id, quantity, size | — (unused) | PPE compliance per worker, return tracking |
| **Sanctions** | worker_sanctions | type, severity, status | — (unused) | Behavioral risk scoring, repeat offender detection |
| **Training attendance** | training_participants | status, score, result | — (unused) | Competency tracking, training effectiveness |
| **KPI definitions** | kpi_definitions | code, name, formula | ✅ KPI page (definition cards) | KPI engine seed, formula editing UI |

### Project Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Project status** | projects | status | Dashboard count | Portfolio overview, resource planning |
| **Project dates** | projects | start_date, end_date | Projects page | Timeline tracking, milestone alerts |
| **Team members** | project_teams | user_id, role_in_project | Project detail | Workload analysis, coverage gaps |
| **Daily headcount** | daily_headcounts | total_count, date | KPI computation | Man-hour trends, staffing optimization |
| **Project stats** | (aggregated) | — | Project detail | Cross-project benchmarking |

### Permit Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Permit types** | permit_types + assignments | name, code | — (unused) | Type-based analytics, template selection |
| **Permit status** | work_permits | status | Dashboard, Permits page | Renewal automation, compliance tracking |
| **Permit dates** | work_permits | commence_date, end_date, expiry_date | Alerts | Expiry prediction, renewal scheduling |
| **Risk assessment link** | work_permits | risk_assessment_id | — (unused) | Permit-risk correlation, pre-permit risk check |
| **Required safety measures** | permit_types | required_safety_measures | — (unused) | Checklist generation, compliance verification |
| **Required PPE** | permit_types | required_ppe | — (unused) | Auto PPE assignment on permit creation |

### Inspection Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Inspection template** | inspection_templates + template_items | name, category | — (unused) | Template analytics, scoring standardization |
| **Inspection items** | inspection_items | status, severity, note | — (unused) | Non-conformance tracking, item-level analytics |
| **Inspection score** | inspections | score | Dashboard | Trend analysis, inspector performance |
| **Inspection result** | inspections | result | Dashboard, Compliance | Pass rate trends, project comparison |

### Environment Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Reading value** | environmental_readings | value, type | ✅ Environment page (overview + readings tab) | Trend prediction, threshold alerting, regulatory reporting |
| **Exceedance flag** | environmental_readings | is_exceedance | ✅ Environment page + Dashboard alerts | Compliance tracking, penalty risk, auto-notifications |
| **Thresholds** | environmental_readings | threshold_min, threshold_max | — (unused) | Auto-exceedance detection, threshold management UI |
| **Waste quantity** | waste_exports | quantity, waste_type | ✅ Environment page (waste tab) | Diversion rate calculation, regulatory reporting |
| **Waste treatment** | waste_exports | treatment | ✅ Environment page (waste tab) | Recycling rate, circular economy metrics |
| **Hazardous flag** | waste_exports | is_hazardous | ✅ Environment page (waste tab) | Compliance tracking, manifest generation |
| **Manifest number** | waste_exports | manifest_number | — (unused) | Regulatory document generation, chain-of-custody |

### Risk Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Risk score (before)** | risk_items | risk_score_before | Risk page | Pre-control risk distribution, hazard ranking |
| **Risk score (after)** | risk_items | risk_score_after | Risk page | Control effectiveness, residual risk tracking |
| **Risk level** | risk_items | risk_level_before/after | Risk page, Dashboard matrix | Risk heat map, critical risk alerts |
| **Control type** | risk_items | control_type | — (unused) | Hierarchy of controls analysis, control effectiveness |
| **Hazard catalog** | hazards | name, category | — (unused) | Cross-project hazard patterns, auto-suggestion |
| **Assessment methodology** | risk_assessments | methodology | — (unused) | Method-based analytics, template selection |
| **Assessment status** | risk_assessments | status | Risk page | Review tracking, approval workflow |

### Community Data Points

| Data Point | Source Table | Source Field(s) | Used In | Potential Uses |
|---|---|---|---|---|
| **Post content** | community_posts | content, hashtags | Community page | Sentiment analysis, trending topics |
| **Comments** | community_post_comments | content | Community page | Engagement metrics, threaded discussions |
| **Reactions** | community_post_reactions | type | — (unused) | Engagement analytics, popular content |
| **Community reports** | community_reports | type, severity, status | — (unused) | Stakeholder complaint tracking, resolution SLA |

---

## 2. UNUSED DATA POINTS (High-Value Opportunities)

These data points exist in the database but have **no frontend display or API consumption yet**:

| Data Point | Table | Recommended Action |
|---|---|---|
| escalation_level | hse_events | Add to event detail + auto-escalation rules |
| risk_item_id | hse_events | Link events to risk items in UI |
| photos/attachments | hse_events | Add file upload to event modal |
| event_actions (all) | event_actions | Build Actions page or tab in Events |
| permit_types | permit_types | Add type selector to permit modal |
| required_safety_measures | permit_types | Show in permit detail |
| required_ppe | permit_types | Auto-suggest PPE on permit creation |
| inspection_templates | inspection_templates | Add template selector to inspection modal |
| inspection_items | inspection_items | Show checklist items in inspection detail |
| worker_documents | worker_documents | Add Documents tab to Worker detail |
| worker_ppe_issues | worker_ppe_issues | Add PPE tab to Worker detail |
| worker_sanctions | worker_sanctions | Add Sanctions tab to Worker detail |
| worker_project_assignments | worker_project_assignments | Add Assignments tab to Worker detail |
| training_participants | training_participants | Add Participants tab to Training detail |
| environmental_readings (full) | environmental_readings | ✅ Now built in Environment page |
| waste_exports | waste_exports | ✅ Now built in Environment page |
| community_reports | community_reports | Add Reports tab to Community page |
| community_post_comments | community_post_comments | Add comments to Community posts |
| community_post_reactions | community_post_reactions | Add reaction buttons to posts |
| hazards | hazards | Add hazard catalog to Risk page |
| risk_items | risk_items | Show risk items in assessment detail |
| ppe_stocks | ppe_stocks | Add stock management to PPE page |
| manifest_number | waste_exports | Add to waste export detail + PDF generation |

---

## 3. DASHBOARD DATA LINKAGE MAP

| Dashboard Widget | Current Data Source | Should Use | Status |
|---|---|---|---|
| TRIR Gauge | kpi_values (TRIR) + hse_events | kpi_values (TRIR) + hse_events | ✅ Updated |
| LTIFR Gauge | kpi_values (LTIFR) + hse_events | kpi_values (LTIFR) + hse_events | ✅ Updated |
| Compliance Radar | inspections.result | inspections + kpi_values | ✅ Working |
| Incident Trend | hse_events monthly by type | hse_events monthly by type | ✅ Updated |
| PPE Status | ppe_items | ppe_stocks + ppe_items | Needs update |
| Training Completion | training_participants / workers | training_participants / workers | Needs update |
| Risk Heat Map | (hardcoded) | risk_items (likelihood × severity) | Needs update |
| Performance Score | kpi_values aggregated | kpi_values aggregated | Needs update |
| Environmental Metrics | environmental_readings + waste_exports | environmental_readings + waste_exports | ✅ Updated |
| Recent Activity | hse_events + kpi_values | hse_events + kpi_values | ✅ Updated |
| Active Alerts | hse_events + event_actions + env_readings | hse_events + event_actions + env_readings | ✅ Updated |

---

## 4. CROSS-MODULE DATA FLOWS

### Data that should trigger actions in other modules:

1. **hse_events (type=incident, severity=high/critical)** → Auto-create event_action (corrective)
2. **hse_events (type=near_miss)** → Link to risk_assessment for risk review
3. **environmental_readings (is_exceedance=true)** → Create notification + hse_event
4. **work_permits (expiry_date < +3 days)** → Create notification alert
5. **worker_documents (expiry_date < +30 days)** → Create notification + flag worker
6. **training_participants (result=fail)** → Create worker_sanction (re_training)
7. **ppe_stocks (quantity <= min_stock_level)** → Create notification for reorder
8. **risk_items (risk_level_after=high/critical)** → Escalate to management
9. **daily_headcounts** → Feed into KPI computation (total hours)
10. **inspection_items (status=non_conform)** → Auto-create event_action (corrective)

---

## 5. MISSING DATA FOR B2B LAUNCH

| Missing Data | Impact | Recommendation |
|---|---|---|
| **kpi_definitions seed data** | KPI engine returns empty | ✅ KpiDefinitionSeeder created (7 definitions) |
| **hse_events seed data** | Dashboard charts empty | ✅ HseEventSeeder created (12 events + actions) |
| **environmental_readings seed data** | Environment page empty | ✅ EnvironmentSeeder created (30 days × 9 types) |
| **waste_exports seed data** | Waste tab empty | ✅ EnvironmentSeeder includes waste records |
| **permit_types seed data** | Permit type selector empty | Seed standard permit types |
| **inspection_templates seed data** | Template selector empty | Seed standard templates |
| **hazards seed data** | Hazard catalog empty | Seed common construction hazards |
| **worker_documents seed data** | Documents tab empty | Seed sample certs for demo workers |
| **training_participants seed data** | Training completion KPI empty | Seed attendance records |
| **Scheduled KPI computation job** | kpi_values never computed | ✅ ComputeKpis command + daily scheduler |
| **Notification triggers** | No automated alerts | Create observers/listeners for key events |
| **sor_reports → hse_events migration** | Old data stranded | ✅ MigrateSorToHseEvents command created |
