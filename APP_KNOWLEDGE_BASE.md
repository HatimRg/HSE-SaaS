# HSE SaaS Platform — Comprehensive Knowledge Base

> Last updated: May 2, 2026  
> Stack: Laravel 11 (PHP 8.3) + React 18 + TypeScript + Vite + TailwindCSS + Recharts  
> DB: MySQL 8 via XAMPP  
> Auth: Sanctum (SPA token-based)  
> Multi-tenancy: Shared DB with `company_id` global scope  

---

## 1. PRODUCT DEFINITION

**Name**: SafeSite HSE  
**Type**: B2B SaaS — Health, Safety & Environment management for construction/industrial companies  
**Target Users**:  
- **Primary**: HSE Safety Managers (on-site, laptop/tablet, needs data fast)  
- **Secondary**: Project Managers (office, multi-project monitoring)  
- **Tertiary**: Workers (read-only, phone/kiosk, quick status checks)  

**Brand Tone**: Professional but human. Trustworthy, confident, clear. Not corporate sterile, not playful.  
**Anti-references**: No crypto neon, no generic SaaS blue gradients, no healthcare pastels, no construction clichés.  

---

## 2. DESIGN SYSTEM

### Color Palette
- **Primary**: Maritime Blue `hsl(217, 72%, 42%)` / `#1e5f9e` — deep confident naval trust  
- **Neutrals**: Warm-tinted (hue 40), not cold grey  
- **Dark mode**: Warm charcoal `hsl(220, 15%, 8%)`, not cold blue-black  
- **Semantic**: Success=confident green, Warning=amber not yellow, Danger=deep red, Info=tinted toward primary  

### Typography
- **Font**: Inter with OpenType features (cv02, cv03, cv04, cv11)  
- **Mono**: JetBrains Mono / Fira Code  
- **Radius**: 0.75rem (12px) for cards  

### Layout
- Sidebar: 260px fixed (desktop), drawer on mobile  
- TopBar: h-14 (56px)  
- Main content: `max-w-[1600px] mx-auto`  
- Content padding: p-6 (24px)  

### Animation
- Framer Motion for page transitions (150ms) and list staggers  
- Only transform/opacity animated, never layout properties  
- Hover: 150ms, Page transitions: 350ms  

---

## 3. ARCHITECTURE

### Backend (Laravel 11)
```
app/
├── Http/Controllers/Api/     # 22 API controllers
│   ├── AuthController         # login, logout, user, profile, password
│   ├── DashboardController    # index, stats, charts (aggregated data)
│   ├── AnalyticsController    # overview, kpis, trends, predictive, cost, risk, compliance
│   ├── KpiReportController    # CRUD + submit/approve/reject workflow
│   ├── SorReportController    # CRUD + close + photo upload
│   ├── WorkPermitController   # CRUD + approve/reject/suspend/renew
│   ├── InspectionController   # CRUD + verify
│   ├── WorkerController       # CRUD + qualifications/trainings/ppe + import
│   ├── ProjectController      # CRUD + team management + stats
│   ├── TrainingController     # CRUD + attendance + certificates
│   ├── PpeController          # items, stock, assign, assignments, low-stock
│   ├── LibraryController      # folders, documents, download, search
│   ├── NotificationController # list, unread-count, mark-read, delete
│   ├── CommunityController    # posts, like, comment
│   ├── RiskAssessmentController # CRUD + matrix
│   ├── IncidentInvestigationController # CRUD + investigate + close
│   ├── OshaComplianceController # compliance, recordables, TRIR, DART, LTIFR, 300-log
│   ├── CompanyBrandingController # show, update, logo upload/remove
│   ├── SuperAdminController   # companies CRUD, suspend/activate, stats, audit-logs
│   ├── UserController         # CRUD + activate/deactivate (admin only)
│   ├── ImportExportController  # import workers, export kpi/workers/sor/inspections/permits
│   └── BaseController          # successResponse, errorResponse, cache, getDateRange
├── Middleware/
│   ├── TenantMiddleware       # Sets company_id global scope on all models
│   └── SecurityHeadersMiddleware # CSP, XSS, clickjacking headers
├── Models/                    # 21 Eloquent models, all extend BaseModel
│   └── BaseModel              # SoftDeletes, encrypted fields, auto-audit
└── Services/
    ├── CacheService            # Redis/file caching with tags
    └── EncryptionService       # AES-256-GCM field-level encryption
```

### Frontend (React 18 + TypeScript)
```
resources/js/
├── app.tsx                    # Root: BrowserRouter, routes, MainLayout, LoginPage
├── components/
│   ├── auth-provider.tsx      # AuthContext: login/logout/user/permissions
│   ├── sidebar.tsx            # 260px collapsible, mobile drawer
│   ├── top-bar.tsx            # Search, notifications, user menu
│   ├── modal.tsx              # Reusable modal component
│   ├── empty-state.tsx        # Empty state placeholder
│   ├── theme-provider.tsx     # Dark/light mode toggle
│   ├── cookie-consent.tsx     # GDPR cookie banner
│   ├── skeleton.tsx           # Loading skeletons
│   └── ui/                    # Reusable UI primitives
├── lib/
│   ├── api.ts                 # Axios instance with auth + tenant interceptors
│   ├── i18n.ts                # i18next initialization (en + fr)
│   ├── notifications.ts      # Push notification helpers
│   └── utils.ts               # cn() class merge utility
├── locales/{en,fr}/
│   ├── common.json            # Shared strings
│   ├── dashboard.json         # Dashboard-specific
│   ├── messages.json          # Success/error messages
│   ├── modules.json           # Module-specific strings
│   └── navigation.json        # Sidebar labels
└── pages/                     # 26 page components (see Section 5)
```

### API Layer
- **Base URL**: `/api`  
- **Auth**: Bearer token (Sanctum), stored in `localStorage.auth_token`  
- **Tenant**: `X-Company-ID` header auto-set from `localStorage.user.company.id`  
- **Interceptors**: 401 → redirect to login, 403 → log, 429 → rate limit warning  
- **QueryClient**: staleTime=5min, gcTime=30min, retry=1, no refetchOnWindowFocus  

---

## 4. MULTI-TENANCY

### Implementation
- **Shared database** with `company_id` column on every table  
- `TenantMiddleware` adds global scope `where('company_id', $user->company_id)` to all models  
- Super Admin (`role:super_admin`) bypasses tenant isolation  
- Company context stored in `request()->attributes['tenant_id']`  

### Models with Tenant Scope
Project, Worker, KpiReport, SorReport, WorkPermit, Inspection, TrainingSession, PpeItem, LibraryDocument, LibraryFolder, CommunityPost, IncidentInvestigation, RiskAssessment  

### New tables also need tenant scope (NOT YET ADDED):
HseEvent, EventAction, Hazard, RiskItem, KpiDefinition, KpiValue, PermitType, InspectionTemplate, WorkerDocument, WorkerPpeIssue, PpeStock, EnvironmentalReading, WasteExport, CommunityReport, TrainingParticipant, WorkerProjectAssignment, WorkerSanction  

---

## 5. PAGES & ROUTES

| Route | Page File | API Endpoints | Status |
|-------|-----------|---------------|--------|
| `/login` | login.tsx (in app.tsx) | POST /login | ✅ Working |
| `/dashboard` | dashboard.tsx | GET /dashboard, /dashboard/stats, /dashboard/charts | ✅ Working |
| `/kpi` | kpi.tsx | GET /kpi-reports, POST /kpi-reports | ✅ Has modal |
| `/sor` | sor.tsx | GET /sor-reports, POST /sor-reports | ✅ Has modal |
| `/permits` | permits.tsx | GET /work-permits, POST /work-permits | ✅ Has modal |
| `/inspections` | inspections.tsx | GET /inspections, POST /inspections | ✅ Has modal |
| `/workers` | workers.tsx | GET /workers, POST /workers | ✅ Has modal |
| `/training` | training.tsx | GET /training-sessions, POST /training-sessions | ✅ Has modal |
| `/ppe` | ppe.tsx | GET /ppe/items, POST /ppe/items | ✅ Has modal |
| `/environment` | environment.tsx | GET /environment (MISSING) | ⚠️ Stub only |
| `/community` | community.tsx | GET /community/posts, POST /community/posts | ✅ Working |
| `/library` | library.tsx | GET /library/folders, /library/documents | ✅ Working |
| `/users` | users.tsx | GET /users, POST /users | ✅ Has modal |
| `/projects` | projects.tsx | GET /projects, POST /projects | ✅ Has modal |
| `/settings` | settings.tsx | None | ⚠️ Stub only |
| `/profile` | profile.tsx | GET /user, PUT /user/profile | ✅ Working |
| `/risk` | risk-assessment.tsx | GET /risk-assessments, POST /risk-assessments | ✅ Working |
| `/investigation` | incident-investigation.tsx | GET /incidents, POST /incidents | ✅ Working |
| `/osha` | osha-compliance.tsx | GET /osha/* | ✅ Working |
| `/analytics` | analytics.tsx | GET /analytics/* | ✅ Working |
| `/admin` | admin-dashboard.tsx | GET /dashboard (admin view) | ✅ Working |
| `/enterprise` | enterprise-monitoring.tsx | GET /dashboard (enterprise) | ✅ Working |
| `/branding` | company-branding.tsx | GET/PUT /company/branding | ✅ Working |
| `/super-admin` | super-admin.tsx | GET /super-admin/* | ✅ Working |

### Missing Pages / Incomplete
- **Environment**: Only shows metric cards, no readings table, no waste exports, no charts, no API endpoint  
- **Settings**: Only shows 4 buttons, no actual settings forms  
- **No route guard**: No redirect to /login when unauthenticated on protected routes  
- **Risk Assessment**: Not in sidebar navigation  
- **Incident Investigation**: Not in sidebar navigation  
- **OSHA Compliance**: Not in sidebar navigation  
- **Analytics**: Not in sidebar navigation  

---

## 6. DATABASE SCHEMA

### Core Tables (Original)
| Table | Key Columns | Notes |
|-------|-------------|-------|
| companies | id, name, domain, subscription_plan, color_* | Multi-tenant root |
| roles | id, name, permissions (JSON) | RBAC: admin, hse_manager, engineer, supervisor, hr_director |
| users | id, company_id, role_id, email, project_access_type | project_access_type: all/pole/projects |
| projects | id, company_id, manager_id, code (unique), status | status: active/completed/on_hold/cancelled |
| project_teams | project_id, user_id, role_in_project | User ↔ Project pivot |
| workers | id, company_id, cin (unique), function, status | Personnel, no project_id (multi-project via pivot) |
| sor_reports | id, company_id, project_id, reference, type, severity | **DEPRECATED → hse_events** |
| kpi_reports | id, company_id, project_id, period_start/end, injuries, etc | **DEPRECATED → kpi_definitions + kpi_values** |
| work_permits | id, company_id, project_id, permit_number, type, status | +risk_assessment_id FK added |
| inspections | id, company_id, project_id, reference, type, result, score | +inspection_template_id FK added |
| training_sessions | id, company_id, project_id, title, type, category | status: planned/in_progress/completed/cancelled |
| ppe_items | id, company_id, name, category, size_options, unit_cost | PPE catalog |
| daily_headcounts | id, company_id, project_id, date, total_count | Unique: (project_id, date, shift) |
| community_posts | id, company_id, user_id, content, hashtags | Internal social feed |
| library_folders | id, company_id, parent_id, name | Document tree |
| library_documents | id, company_id, folder_id, file_path, version | Uploaded files |
| notifications | id, company_id, user_id, type, read_at | User notifications |

### New Tables (Schema Redesign)
| Table | Key Columns | Module | Migration |
|-------|-------------|--------|-----------|
| hse_events | id, company_id, project_id, type (enum), severity, risk_item_id | Events | 000020 |
| event_actions | id, company_id, source_type, source_id, status, due_date | All | 000019 |
| hazards | id, company_id, name, category | Risk | 000022 |
| risk_assessments | id, company_id, project_id, title, status | Risk | 000022 |
| risk_items | id, risk_assessment_id, hazard_id, likelihood, severity, risk_score | Risk | 000022 |
| kpi_definitions | id, company_id, name, formula_reference, frequency | KPI | 000023 |
| kpi_values | id, kpi_definition_id, project_id, period_start/end, value | KPI | 000023 |
| permit_types | id, company_id, name | Permits | 000024 |
| permit_type_assignments | id, permit_id, permit_type_id | Permits | 000024 |
| inspection_templates | id, company_id, name | Inspections | 000025 |
| template_items | id, inspection_template_id, item_text, category | Inspections | 000025 |
| inspection_items | id, inspection_id, template_item_id, status, note | Inspections | 000025 |
| worker_documents | id, worker_id, type, name, issue_date, expiry_date | Workers | 000026 |
| worker_project_assignments | id, worker_id, project_id, role, start_date | Workers | 000031 |
| worker_sanctions | id, worker_id, project_id, type, description | Workers | 000031 |
| worker_ppe_issues | id, worker_id, ppe_item_id, quantity, size | PPE | 000027 |
| ppe_stocks | id, ppe_item_id, project_id, quantity, reorder_level | PPE | 000027 |
| environmental_readings | id, project_id, type, value, measured_at | Environment | 000028 |
| waste_exports | id, project_id, waste_type, quantity, treatment | Environment | 000028 |
| community_reports | id, project_id, type, description | Community | 000029 |
| community_post_comments | id, post_id, user_id, content | Community | 000029 |
| community_post_reactions | id, post_id, user_id, type | Community | 000029 |
| training_participants | id, training_session_id, worker_id, status | Training | 000030 |

### KPI Derivation Formulas (No Manual Entry)
| KPI | Formula | Sources |
|-----|---------|---------|
| TRIR | (Recordable Incidents × 200,000) / Total Hours | hse_events + daily_headcounts |
| LTIFR | (Lost Time Incidents × 1,000,000) / Total Hours | hse_events + daily_headcounts |
| Near Miss Rate | Near Misses / Total Events | hse_events |
| Action Closure Rate | Closed Actions / Total Actions | event_actions |
| Inspection Compliance | Passed / Total | inspections |
| Permit Compliance | Active Not Expired / Total Active | work_permits |
| Training Completion | Attended / Total Active Workers | training_participants + workers |
| PPE Compliance | Workers with PPE / Total Workers | worker_ppe_issues + workers |

---

## 7. AUTH & PERMISSIONS

### Roles
| Role | Access | Permissions |
|------|--------|-------------|
| super_admin | All companies, no tenant scope | Full system access |
| admin | Own company, all projects | is_admin, is_admin_like, can_approve_kpi, can_approve_permit, can_manage_users, can_export |
| hse_manager | Own company, all projects | is_hse, can_approve_kpi, can_approve_permit, can_export |
| engineer | Own company, specific projects | can_export |
| supervisor | Own company, specific projects | — |
| hr_director | Own company, all projects | can_manage_users |

### Auth Flow
1. POST `/api/login` → receives token + user object  
2. Token stored in `localStorage.auth_token`  
3. Axios interceptor adds `Authorization: Bearer {token}` + `X-Company-ID` header  
4. `AuthProvider` fetches `/api/user` on mount to hydrate user state  
5. 401 response → clear token, redirect to `/login?expired=true`  

### Route Guards
- API: `auth:sanctum` + `tenant` middleware on all protected routes  
- Super Admin routes: `role:super_admin` middleware  
- User management: `role:admin|super_admin` middleware  
- **Frontend has NO route guard** — any logged-out user can navigate to /dashboard (shows loading)  

---

## 8. DASHBOARD DATA FLOW

### API Endpoints
- `GET /api/dashboard` → overview (user info, summary, safety_metrics, compliance, recent_activity, alerts)  
- `GET /api/dashboard/stats` → stat cards (trir, ltifr, daily_headcount, incidents, near_miss, permit_compliance)  
- `GET /api/dashboard/charts` → chart data (incident_trend, compliance_radar, incident_by_type, performance_score, training_completion, ppe_status, risk_matrix)  

### Frontend Queries
- `dashboard-stats` (with timeRange param: 7d/30d/90d/1y)  
- `dashboard-overview`  
- `dashboard-charts` (with timeRange param)  
- `dashboard-activities`  
- `dashboard-alerts`  

### Charts Rendered
1. **KPI Gauges** (4): TRIR, LTIFR, Compliance, Training — SVG circular gauges  
2. **Incident Distribution** — PieChart (inner donut)  
3. **Compliance Radar** — RadarChart (current vs target)  
4. **Risk Heat Map** — ScatterChart (likelihood × severity)  
5. **Incident & Near Miss Trend** — ComposedChart (Area + Bar + Line)  
6. **PPE Inventory Status** — BarChart (horizontal stacked)  
7. **HSE Performance Score** — AreaChart + Lines (overall, safety, env, health)  
8. **Training Completion** — Progress bars (not chart)  
9. **Recent Activity** — Timeline list  
10. **Active Alerts** — Alert cards  
11. **Environmental Metrics** — Small stat cards  

---

## 9. SIDEBAR NAVIGATION

Current nav items (in order):
1. Dashboard (`/dashboard`)  
2. KPI (`/kpi`)  
3. Observations (`/sor`)  
4. Permits (`/permits`)  
5. Inspections (`/inspections`)  
6. Workers (`/workers`)  
7. Training (`/training`)  
8. PPE (`/ppe`)  
9. Environment (`/environment`)  
10. Community (`/community`)  
11. Documents (`/library`)  
12. Users (`/users`)  
13. Projects (`/projects`)  

**Missing from sidebar** (but routes exist):  
- Risk Assessment (`/risk`)  
- Incident Investigation (`/investigation`)  
- OSHA Compliance (`/osha`)  
- Analytics (`/analytics`)  
- Admin Dashboard (`/admin`)  
- Enterprise Monitoring (`/enterprise`)  
- Company Branding (`/branding`)  
- Super Admin (`/super-admin`)  

---

## 10. MODALS & DATA INJECTION

All modals use the shared `Modal` component from `components/modal.tsx`.  
Each modal uses `useMutation` from react-query to POST to the API.  

| Page | Modal Fields | API Endpoint |
|------|-------------|--------------|
| SOR | type, severity, description, location, date | POST /sor-reports |
| KPI | period_start, period_end, total_hours, injuries, first_aids, near_misses, observations | POST /kpi-reports |
| Permits | type, title, description, location, commence_date, end_date | POST /work-permits |
| Inspections | type, location, inspector, date | POST /inspections |
| Workers | first_name, last_name, role, company, cin, phone | POST /workers |
| Training | title, type, category, start_date, end_date, duration, location, max_participants | POST /training-sessions |
| PPE | name, category, description, unit_cost, reorder_level | POST /ppe/items |
| Users | first_name, last_name, email, role, phone, password | POST /users |
| Projects | name, code, description, location, client_name, start_date, end_date, status | POST /projects |

---

## 11. I18N (Internationalization)

- **Languages**: English (en), French (fr)  
- **Library**: react-i18next  
- **Namespace files**: common, dashboard, messages, modules, navigation  
- **User language**: Set from `user.language` on auth, stored in company settings  
- **All `t()` calls use namespaced keys**: `common:`, `modules:`, `navigation:`, `messages:`, `dashboard:`  
- **EN/FR parity verified**: All keys present in both languages, tested via Vitest  
- **No hardcoded UI strings remaining**: Full audit completed across all pages  

---

## 12. FIELD-LEVEL ENCRYPTION

- `BaseModel` auto-encrypts fields listed in `$encrypted` array before saving  
- `EncryptionService` uses AES-256-GCM with per-field IV  
- Toggle: `config('app.enable_e2e_encryption')`  
- Encrypted models: Worker, WorkPermit, SorReport, KpiReport, Inspection  

---

## 13. KEY GAPS FOR B2B LAUNCH

### ✅ Resolved
1. ~~No route guard~~ — ProtectedRoute added in app.tsx  
2. ~~Environment page is a stub~~ — Full implementation with tabs, tables, charts, modals  
3. ~~Dashboard data not linked to new schema~~ — DashboardController now uses HseEvent + KpiValue  
4. ~~New tables missing from TenantMiddleware~~ — All 17 new models added to global scope  
5. ~~No Eloquent models for new tables~~ — 22 models created (HseEvent, EventAction, Hazard, etc.)  
6. ~~No API endpoints for new tables~~ — 4 new controllers + routes (HseEvent, EventAction, Environment, KpiEngine)  
7. ~~Sidebar missing key modules~~ — Risk, Investigation, OSHA, Analytics added  
8. ~~No seed data for new tables~~ — KpiDefinitionSeeder, HseEventSeeder, EnvironmentSeeder created  
9. ~~No scheduled KPI computation~~ — ComputeKpis artisan command + daily scheduler  
10. ~~No data migration script~~ — MigrateSorToHseEvents artisan command  
11. ~~SOR modal posts to /sor-reports~~ — Updated to /hse-events with correct field mapping  
12. ~~KPI modal has manual entry~~ — Replaced with computed KPI dashboard + compute modal  
13. ~~Settings page is a stub~~ — Full implementation with appearance, notifications, language, security tabs  
14. ~~No notification triggers~~ — 4 observers (HseEvent, EnvironmentalReading, WorkPermit, WorkerDocument) registered in AppServiceProvider  
15. ~~Worker detail missing tabs~~ — Detail drawer with Documents, PPE, Sanctions, Assignments tabs  
16. ~~Training detail missing Participants tab~~ — Detail drawer with participants list (status, score, result)  
17. ~~Permit modal missing type selector~~ — Dynamic type selector from /permit-types API with fallback  
18. ~~Inspection detail missing checklist items~~ — Detail drawer with checklist items (conform/non_conform/na)  
19. ~~Community page missing reports~~ — Report dropdown (inappropriate/spam) on each post  
20. ~~Missing seeders~~ — PermitTypeSeeder (7 types), InspectionTemplateSeeder (4 templates), HazardSeeder (16 hazards), WorkerDataSeeder (12 documents + 8 participants)  

### ✅ Resolved (Session 5)
23. ~~Hardcoded strings~~ — Full i18n audit completed, all pages use namespaced `t()` keys

### Medium Priority (Still Open)
21. **No audit log integration** — New tables don't log changes  
22. **No CSV/PDF export for new modules**  
24. **Backend SorReport/KpiReport references** — Controllers still reference old models (SorReportController, OshaComplianceController, AnalyticsController, ImportExportController)  

---

## 14. TEST SUITE

- **Framework**: Vitest + React Testing Library + jsdom  
- **Config**: `vite.config.ts` → `test` section  
- **Setup**: `resources/js/test/setup.ts` — global mocks for react-i18next, react-query, framer-motion, auth, theme, API, router  
- **Test files**:  
  - `resources/js/test/app.test.tsx` — 27 tests (i18n keys, page rendering, components, API, runtime translation)  
  - `resources/js/test/pages.test.tsx` — 19 tests (15 page smoke tests, modal interactions, API methods)  
- **Total**: 46 tests, all passing  
- **Run**: `npm test` or `npm run test:watch`  

---

## 15. FILE QUICK REFERENCE

### Backend Controllers
`app/Http/Controllers/Api/` — 22 files, all extend BaseController  

### Frontend Pages
`resources/js/pages/` — 26 files  

### Migrations
`database/migrations/` — 29 files (000-018 original, 019-032 new)  

### Models
`app/Models/` — 21 files (existing), need ~12 more for new tables  

### Routes
`routes/api.php` — 231 lines, all API routes  

### Seeders
`database/seeders/` — 14 files (all now idempotent)  

### Config
`.env` — DB=mysql/hse_saas, APP_KEY set, no Redis configured  
`config/` — 8 files (auth, cors, database, etc.)  
