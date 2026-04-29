# HSE SaaS Platform - Implementation Record

**Project:** Multi-tenant HSE (Health, Safety & Environment) SaaS Platform  
**Stack:** Laravel 11 + React 18 + TypeScript + Tailwind CSS  
**Last Updated:** April 28, 2026  
**Version:** 1.0.0

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Backend Implementation](#backend-implementation)
3. [Frontend Implementation](#frontend-implementation)
4. [Database Schema](#database-schema)
5. [API Documentation](#api-documentation)
6. [Component Inventory](#component-inventory)
7. [File Structure](#file-structure)
8. [Requirements for Additions/Modifications](#requirements-for-additionsmodifications)
9. [Change Log](#change-log)

---

## Architecture Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENT LAYER                                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   Browser   │  │   Browser   │  │   Browser   │  │   Browser   │        │
│  │  (React SPA)│  │  (React SPA)│  │  (React SPA)│  │  (React SPA)│        │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘        │
└─────────┼────────────────┼────────────────┼────────────────┼──────────────────┘
          │                │                │                │
          └────────────────┴────────────────┴────────────────┘
                                     │
                              HTTP/HTTPS
                                     │
┌────────────────────────────────────┼──────────────────────────────────────┐
│                         API LAYER  │                                       │
│  ┌─────────────────────────────────┴─────────────────────────────────────┐  │
│  │                         Laravel 11 Application                         │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐  │  │
│  │  │   Auth      │  │   Tenant    │  │   Rate      │  │   Security   │  │  │
│  │  │ Middleware  │  │ Middleware  │  │   Limiter   │  │   Headers    │  │  │
│  │  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬───────┘  │  │
│  │         └─────────────────┴─────────────────┴─────────────────┘         │  │
│  │                              │                                        │  │
│  │                    ┌─────────┴─────────┐                              │  │
│  │                    │  API Controllers  │                              │  │
│  │                    │  (RESTful API)    │                              │  │
│  │                    └─────────┬─────────┘                              │  │
│  │                              │                                        │  │
│  │  ┌─────────────┐  ┌──────────┴──────────┐  ┌─────────────┐          │  │
│  │  │   Eloquent  │  │   Business Logic    │  │   Cache     │          │  │
│  │  │   Models    │  │   (Services)        │  │   Service   │          │  │
│  │  └──────┬──────┘  └─────────────────────┘  └──────┬──────┘          │  │
│  │         └──────────────────┬───────────────────────┘                 │  │
│  └────────────────────────────┼─────────────────────────────────────────┘  │
└───────────────────────────────┼────────────────────────────────────────────┘
                                │
┌───────────────────────────────┼────────────────────────────────────────────┐
│                    DATA LAYER │                                            │
│  ┌────────────────────────────┴─────────────────────────────────────────┐  │
│  │                          MySQL Database                               │  │
│  │  • Multi-tenant tables with company_id                                │  │
│  │  • Soft deletes on all tables                                         │  │
│  │  • Encrypted fields for sensitive data                                │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                             │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                          Redis Cache                                    │  │
│  │  • Tenant-aware cache keys                                              │  │
│  │  • Query result caching                                                 │  │
│  │  • Session storage                                                      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Core Principles

1. **Multi-tenancy**: All data scoped by `company_id` with global query scopes
2. **Security**: AES-256-GCM encryption, rate limiting, security headers
3. **Performance**: Redis caching, database indexing, eager loading
4. **Scalability**: Service-oriented architecture, queue system ready
5. **Localization**: French-first, English-second with react-i18next

---

## Backend Implementation

### Core Services

#### 1. Encryption Service (`app/Services/EncryptionService.php`)
```
Purpose: End-to-end encryption for sensitive data
Algorithm: AES-256-GCM
Methods:
  - encrypt(string $data): array {ciphertext, nonce, tag}
  - decrypt(array $encrypted): string
  - rotateKey(): void (for key rotation)

Usage:
  $encrypted = EncryptionService::encrypt('sensitive data');
  $decrypted = EncryptionService::decrypt($encrypted);
```

#### 2. Cache Service (`app/Services/CacheService.php`)
```
Purpose: Tenant-aware caching with Redis
Features:
  - Automatic cache key prefixing with company_id
  - TTL-based expiration
  - Tag-based invalidation
  
Methods:
  - remember(string $key, callable $callback, int $ttl = 3600): mixed
  - forget(string $key): void
  - flush(): void
  
TTL Guidelines:
  - Dashboard stats: 120 seconds
  - List data: 90 seconds
  - Detail views: 300 seconds
```

### Middleware Stack

| Middleware | Purpose | Priority |
|------------|---------|----------|
| `SecurityHeadersMiddleware` | Adds security headers (CSP, HSTS, X-Frame-Options) | 1 |
| `TenantMiddleware` | Injects company_id from authenticated user | 2 |
| `throttle:api` | Rate limiting (60 requests/minute) | 3 |
| `auth:sanctum` | JWT token authentication | 4 |

### Base Controller Pattern

All API controllers extend `BaseController` which provides:

```php
// Response Helpers
successResponse($data, $message = 'Success', $code = 200)
errorResponse($message, $code = 400, $errors = null)
paginatedResponse($items, $pagination, $message = 'Success')

// Caching Helpers
getCachedList(string $key, callable $query, int $perPage = 15)
clearCache(string $pattern): void

// Audit Logging
logActivity(string $action, string $description, array $metadata = []): void
```

### Model Architecture

```php
// Base Model Features
abstract class BaseModel extends Model
{
    // Global scope: automatically adds company_id condition
    protected static function booted()
    
    // Encrypted fields
    protected array $encrypted = [];
    
    // Soft deletes on all models
    use SoftDeletes;
}
```

---

## Frontend Implementation

### Tech Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| React | 18.x | UI Library |
| TypeScript | 5.x | Type Safety |
| Vite | 5.x | Build Tool |
| Tailwind CSS | 3.x | Styling |
| TanStack Query | 5.x | Data Fetching & Caching |
| React Router | 6.x | Routing |
| Framer Motion | 11.x | Animations |
| react-i18next | 14.x | Internationalization |
| Lucide React | latest | Icons |
| React Hot Toast | 2.x | Notifications |

### Provider Hierarchy

```
<QueryClientProvider>
  <I18nextProvider>
    <ThemeProvider>
      <AuthProvider>
        <BrowserRouter>
          <Toaster />
          <AppLayout>
            <Routes />
          </AppLayout>
        </BrowserRouter>
      </AuthProvider>
    </ThemeProvider>
  </I18nextProvider>
</QueryClientProvider>
```

### Component Architecture

#### State Management
- **AuthProvider**: User authentication state, login/logout, permissions
- **ThemeProvider**: Dark/light mode, company color customization
- **TanStack Query**: Server state management, caching, mutations

#### UI Components
- **Layout Components**: Sidebar, TopBar
- **Page Components**: Dashboard, KPI, SOR, Workers, Permits, etc.
- **Utility Components**: Skeleton loaders, EmptyState

### Styling System

```css
/* CSS Custom Properties (Tailwind) */
:root {
  /* Light Mode */
  --background: #ffffff;
  --foreground: #0f172a;
  --primary: #3b82f6;
  --primary-dark: #1d4ed8;
  
  /* Dark Mode */
  .dark {
    --background: #0f172a;
    --foreground: #f8fafc;
    --primary: #3b82f6;
    --primary-dark: #60a5fa;
  }
}

/* Company Customization via window.companyColors */
```

### API Integration Pattern

```typescript
// lib/api.ts - Axios instance with interceptors
const api = axios.create({
  baseURL: '/api',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
});

// Automatic auth token injection
// Automatic company_id header injection
// Error handling for 401, 403, 429, network errors
```

---

## Database Schema

### Entity Relationship Diagram

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    companies    │     │      users      │     │      roles      │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id (PK)         │◄────┤ company_id (FK) │     │ id (PK)         │
│ name            │     │ id (PK)         │     │ name            │
│ primary_color   │     │ name            │     │ guard_name      │
│ secondary_color │     │ email           │     └─────────────────┘
│ settings (JSON) │     │ password        │     ┌─────────────────┐
│ created_at      │     │ role_id (FK)    │────►│ model_has_roles │
└─────────────────┘     │ phone           │     └─────────────────┘
         │              │ cin             │
         │              │ avatar          │
         │              │ is_active       │
         │              │ last_login_at   │
         │              └─────────────────┘
         │
         │              ┌─────────────────┐
         │              │    projects     │
         │              ├─────────────────┤
         └─────────────►│ company_id (FK) │
                        │ id (PK)         │
                        │ name            │
                        │ code            │
                        │ location        │
                        │ start_date      │
                        │ end_date        │
                        │ status          │
                        └─────────────────┘

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     workers     │     │  kpi_reports    │     │  sor_reports    │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ company_id (FK) │     │ company_id (FK) │     │ company_id (FK) │
│ id (PK)         │     │ id (PK)         │     │ id (PK)         │
│ project_id (FK) │◄────┤ project_id (FK) │     │ project_id (FK) │
│ full_name       │     │ period_start    │     │ reference       │
│ cin             │     │ period_end      │     │ title           │
│ function        │     │ total_hours     │     │ severity        │
│ status          │     │ injuries        │     │ status          │
│ phone           │     │ fatalities      │     │ due_date        │
│ hire_date       │     │ status          │     │ assigned_to     │
└─────────────────┘     │ submitted_by    │     └─────────────────┘
                        │ approved_by     │
                        └─────────────────┘

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  work_permits   │     │  inspections    │     │training_sessions│
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ company_id (FK) │     │ company_id (FK) │     │ company_id (FK) │
│ id (PK)         │     │ id (PK)         │     │ id (PK)         │
│ project_id (FK) │     │ project_id (FK) │     │ title           │
│ permit_number   │     │ reference       │     │ type            │
│ type            │     │ type            │     │ start_date      │
│ location        │     │ date            │     │ end_date        │
│ status          │     │ result          │     │ trainer         │
│ start_date      │     │ score           │     │ status          │
│ expiry_date     │     │ performed_by    │     │ location        │
│ requester_id    │     └─────────────────┘     └─────────────────┘
│ approver_id     │
└─────────────────┘

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    ppe_items    │     │   machines      │     │     library     │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ company_id (FK) │     │ company_id (FK) │     │ company_id (FK) │
│ id (PK)         │     │ id (PK)         │     │ id (PK)         │
│ project_id (FK) │     │ project_id (FK) │     │ parent_id (FK)  │
│ name            │     │ name            │     │ name            │
│ category        │     │ type            │     │ type            │
│ description     │     │ serial_number   │     │ path            │
│ unit_cost       │     │ manufacturer    │     │ size            │
│ sizes (JSON)    │     │ status          │     │ mime_type       │
│ colors (JSON)   │     │ last_inspection │   │ uploaded_by     │
│ reorder_level   │     │ next_inspection │   │ is_encrypted    │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### Indexing Strategy

All foreign keys are indexed. Additional indexes on frequently queried fields:

```sql
-- Composite indexes for common queries
CREATE INDEX idx_workers_company_status ON workers(company_id, status);
CREATE INDEX idx_kpi_reports_company_period ON kpi_reports(company_id, period_start, period_end);
CREATE INDEX idx_sor_reports_company_status ON sor_reports(company_id, status);
CREATE INDEX idx_permits_company_status ON work_permits(company_id, status);
```

---

## API Documentation

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | User login, returns token |
| POST | `/api/auth/logout` | User logout, revokes token |
| GET | `/api/auth/user` | Get current user profile |
| PUT | `/api/auth/profile` | Update user profile |
| PUT | `/api/auth/password` | Change password |
| POST | `/api/auth/forgot-password` | Request password reset |
| POST | `/api/auth/reset-password` | Reset password with token |

### Resource Endpoints

All endpoints require authentication header: `Authorization: Bearer {token}`

#### Dashboard
- `GET /api/dashboard` - Overview data
- `GET /api/dashboard/stats` - Quick stats
- `GET /api/dashboard/charts` - Chart data
- `GET /api/dashboard/alerts` - Active alerts

#### KPI Reports
- `GET /api/kpi-reports` - List reports
- `GET /api/kpi-reports/{id}` - Get single report
- `POST /api/kpi-reports` - Create report
- `PUT /api/kpi-reports/{id}` - Update report
- `DELETE /api/kpi-reports/{id}` - Delete report
- `POST /api/kpi-reports/{id}/submit` - Submit for approval
- `POST /api/kpi-reports/{id}/approve` - Approve report
- `POST /api/kpi-reports/{id}/reject` - Reject report

#### SOR Reports
- `GET /api/sor-reports` - List observations
- `GET /api/sor-reports/{id}` - Get observation
- `POST /api/sor-reports` - Create observation
- `PUT /api/sor-reports/{id}` - Update observation
- `POST /api/sor-reports/{id}/close` - Close observation
- `POST /api/sor-reports/{id}/photos` - Upload photo

#### Work Permits
- `GET /api/work-permits` - List permits
- `GET /api/work-permits/types` - Get permit types
- `POST /api/work-permits` - Create permit
- `PUT /api/work-permits/{id}` - Update permit
- `POST /api/work-permits/{id}/approve` - Approve permit
- `POST /api/work-permits/{id}/reject` - Reject permit
- `POST /api/work-permits/{id}/suspend` - Suspend permit
- `POST /api/work-permits/{id}/renew` - Renew permit

#### Workers
- `GET /api/workers` - List workers
- `GET /api/workers/{id}` - Get worker details
- `POST /api/workers` - Create worker
- `PUT /api/workers/{id}` - Update worker
- `DELETE /api/workers/{id}` - Delete worker
- `GET /api/workers/{id}/qualifications` - Get qualifications
- `POST /api/workers/{id}/qualifications` - Add qualification
- `GET /api/workers/{id}/trainings` - Get training records
- `POST /api/workers/import` - Import from Excel

#### Projects
- `GET /api/projects` - List projects
- `GET /api/projects/{id}` - Get project
- `POST /api/projects` - Create project
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project
- `GET /api/projects/{id}/team` - Get team members
- `POST /api/projects/{id}/team` - Add team member

#### Notifications
- `GET /api/notifications` - List notifications
- `GET /api/notifications/unread-count` - Get unread count
- `POST /api/notifications/{id}/read` - Mark as read
- `POST /api/notifications/read-all` - Mark all as read
- `DELETE /api/notifications/{id}` - Delete notification

---

## Component Inventory

### Backend Components

| Component | File | Purpose |
|-----------|------|---------|
| EncryptionService | `app/Services/EncryptionService.php` | AES-256-GCM encryption |
| CacheService | `app/Services/CacheService.php` | Tenant-aware caching |
| BaseModel | `app/Models/BaseModel.php` | Multi-tenant base model |
| TenantMiddleware | `app/Http/Middleware/TenantMiddleware.php` | Company scoping |
| SecurityHeadersMiddleware | `app/Http/Middleware/SecurityHeadersMiddleware.php` | Security headers |
| BaseController | `app/Http/Controllers/Api/BaseController.php` | Base API controller |
| AuthController | `app/Http/Controllers/Api/AuthController.php` | Authentication |
| DashboardController | `app/Http/Controllers/Api/DashboardController.php` | Dashboard data |
| KpiReportController | `app/Http/Controllers/Api/KpiReportController.php` | KPI management |
| SorReportController | `app/Http/Controllers/Api/SorReportController.php` | SOR management |
| WorkPermitController | `app/Http/Controllers/Api/WorkPermitController.php` | Permit management |
| WorkerController | `app/Http/Controllers/Api/WorkerController.php` | Worker management |
| ProjectController | `app/Http/Controllers/Api/ProjectController.php` | Project management |
| NotificationController | `app/Http/Controllers/Api/NotificationController.php` | Notifications |

### Frontend Components

| Component | File | Purpose |
|-----------|------|---------|
| ThemeProvider | `resources/js/components/theme-provider.tsx` | Theme & dark mode |
| AuthProvider | `resources/js/components/auth-provider.tsx` | Authentication state |
| Sidebar | `resources/js/components/sidebar.tsx` | Navigation sidebar |
| TopBar | `resources/js/components/top-bar.tsx` | Header bar |
| Skeleton | `resources/js/components/skeleton.tsx` | Loading states |
| EmptyState | `resources/js/components/empty-state.tsx` | Empty data UI |

### Page Components

| Page | File | Route | Permissions |
|------|------|-------|-------------|
| Login | `resources/js/pages/login.tsx` | `/login` | Public |
| Dashboard | `resources/js/pages/dashboard.tsx` | `/dashboard` | All |
| KPI | `resources/js/pages/kpi.tsx` | `/kpi` | View KPI |
| SOR | `resources/js/pages/sor.tsx` | `/sor` | View SOR |
| Workers | `resources/js/pages/workers.tsx` | `/workers` | View Workers |
| Permits | `resources/js/pages/permits.tsx` | `/permits` | View Permits |
| Inspections | `resources/js/pages/inspections.tsx` | `/inspections` | View Inspections |
| Training | `resources/js/pages/training.tsx` | `/training` | View Training |
| PPE | `resources/js/pages/ppe.tsx` | `/ppe` | View PPE |
| Profile | `resources/js/pages/profile.tsx` | `/profile` | All |
| Settings | `resources/js/pages/settings.tsx` | `/settings` | All |
| Not Found | `resources/js/pages/not-found.tsx` | `*` | Public |

---

## File Structure

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── BaseController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── InspectionController.php
│   │   │       ├── KpiReportController.php
│   │   │       ├── MachineController.php
│   │   │       ├── NotificationController.php
│   │   │       ├── ProjectController.php
│   │   │       ├── SorReportController.php
│   │   │       ├── WorkPermitController.php
│   │   │       └── WorkerController.php
│   │   └── Middleware/
│   │       ├── EncryptCookies.php
│   │       ├── PreventRequestsDuringMaintenance.php
│   │       ├── SecurityHeadersMiddleware.php
│   │       ├── TenantMiddleware.php
│   │       ├── TrimStrings.php
│   │       ├── TrustProxies.php
│   │       └── ValidateSignature.php
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── BaseModel.php
│   │   ├── Company.php
│   │   ├── DailyHeadcount.php
│   │   ├── Incident.php
│   │   ├── Inspection.php
│   │   ├── KpiReport.php
│   │   ├── Library.php
│   │   ├── Machine.php
│   │   ├── Notification.php
│   │   ├── PpeItem.php
│   │   ├── Project.php
│   │   ├── Role.php
│   │   ├── SorReport.php
│   │   ├── TrainingSession.php
│   │   ├── User.php
│   │   ├── WorkPermit.php
│   │   ├── Worker.php
│   │   ├── WorkerPpe.php
│   │   ├── WorkerQualification.php
│   │   └── WorkerTraining.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Services/
│       ├── CacheService.php
│       └── EncryptionService.php
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── cors.php
│   ├── database.php
│   ├── encryption.php
│   ├── filesystems.php
│   ├── hashing.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── services.php
│   ├── session.php
│   └── view.php
├── database/
│   ├── factories/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_cache_table.php
│   │   ├── 2025_01_01_000001_create_companies_table.php
│   │   ├── 2025_01_01_000002_create_users_table.php
│   │   ├── 2025_01_01_000003_create_projects_table.php
│   │   ├── 2025_01_01_000004_create_workers_table.php
│   │   ├── 2025_01_01_000005_create_worker_qualifications_table.php
│   │   ├── 2025_01_01_000006_create_worker_trainings_table.php
│   │   ├── 2025_01_01_000007_create_kpi_reports_table.php
│   │   ├── 2025_01_01_000008_create_sor_reports_table.php
│   │   ├── 2025_01_01_000009_create_sor_photos_table.php
│   │   ├── 2025_01_01_000010_create_work_permits_table.php
│   │   ├── 2025_01_01_000011_create_machines_table.php
│   │   ├── 2025_01_01_000012_create_inspections_table.php
│   │   ├── 2025_01_01_000013_create_inspection_items_table.php
│   │   ├── 2025_01_01_000014_create_training_sessions_table.php
│   │   ├── 2025_01_01_000015_create_ppe_items_table.php
│   │   ├── 2025_01_01_000016_create_worker_ppe_table.php
│   │   ├── 2025_01_01_000017_create_daily_headcounts_table.php
│   │   ├── 2025_01_01_000018_create_library_table.php
│   │   ├── 2025_01_01_000019_create_notifications_table.php
│   │   └── 2025_01_01_000020_create_activity_logs_table.php
│   └── seeders/
│       ├── CompanySeeder.php
│       ├── DatabaseSeeder.php
│       ├── KpiReportSeeder.php
│       ├── LibrarySeeder.php
│       ├── PpeItemSeeder.php
│       ├── ProjectSeeder.php
│       ├── RoleSeeder.php
│       ├── SorReportSeeder.php
│       ├── TrainingSeeder.php
│       ├── UserSeeder.php
│       ├── WorkPermitSeeder.php
│       └── WorkerSeeder.php
├── public/
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── app.tsx
│   │   ├── app.css
│   │   ├── components/
│   │   │   ├── auth-provider.tsx
│   │   │   ├── empty-state.tsx
│   │   │   ├── sidebar.tsx
│   │   │   ├── skeleton.tsx
│   │   │   ├── theme-provider.tsx
│   │   │   └── top-bar.tsx
│   │   ├── lib/
│   │   │   ├── api.ts
│   │   │   └── i18n.ts
│   │   ├── pages/
│   │   │   ├── dashboard.tsx
│   │   │   ├── inspections.tsx
│   │   │   ├── kpi.tsx
│   │   │   ├── login.tsx
│   │   │   ├── not-found.tsx
│   │   │   ├── permits.tsx
│   │   │   ├── ppe.tsx
│   │   │   ├── profile.tsx
│   │   │   ├── settings.tsx
│   │   │   ├── sor.tsx
│   │   │   ├── training.tsx
│   │   │   └── workers.tsx
│   │   └── routes/
│   │       └── index.tsx
│   └── views/
│       └── app.blade.php
├── routes/
│   ├── api.php
│   ├── channels.php
│   └── web.php
├── .env.example
├── composer.json
├── IMPLEMENTATION_RECORD.md
├── package.json
├── tailwind.config.js
├── tsconfig.json
├── tsconfig.node.json
└── vite.config.ts
```

---

## Requirements for Additions/Modifications

### Adding a New Backend Feature

#### 1. Model Requirements
```php
<?php
namespace App\Models;

class NewFeature extends BaseModel
{
    protected $fillable = [
        'company_id',  // Required for multi-tenancy
        'name',
        'description',
    ];
    
    // Define encrypted fields if needed
    protected $casts = [
        'metadata' => 'encrypted:array',
    ];
    
    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
```

#### 2. Migration Requirements
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_features');
    }
};
```

#### 3. Controller Requirements
```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\NewFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewFeatureController extends BaseController
{
    public function index(Request $request)
    {
        return $this->getCachedList('new_features', function () use ($request) {
            return NewFeature::query()
                ->with(['related'])
                ->when($request->search, function ($q, $search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);
        }, $request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $item = NewFeature::create([
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $this->clearCache('new_features');
        $this->logActivity('create', "Created new feature: {$item->name}");

        return $this->successResponse($item, 'Created successfully', 201);
    }
}
```

#### 4. Route Registration
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('new-features', NewFeatureController::class);
});
```

### Adding a New Frontend Page

#### 1. Create Page Component
```typescript
// resources/js/pages/new-feature.tsx
import React from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

export default function NewFeaturePage() {
  const { t } = useTranslation();

  const { data: items, isLoading } = useQuery({
    queryKey: ['new-features'],
    queryFn: async () => {
      const response = await api.get('/new-features');
      return response.data.data.items;
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">New Feature</h1>
          <p className="text-muted-foreground">Manage new feature</p>
        </div>
        <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-dark">
          <Plus className="h-4 w-4" />
          Create New
        </button>
      </div>
      {/* Content */}
    </div>
  );
}
```

#### 2. Add Route
```typescript
// resources/js/routes/index.tsx
const NewFeaturePage = lazy(() => import('../pages/new-feature'));

<Route element={<ProtectedRoute permission="view_new_feature">}>
  <Route path="/new-feature" element={<NewFeaturePage />} />
</Route>
```

#### 3. Add Navigation Link
```typescript
// resources/js/components/sidebar.tsx
{
  path: '/new-feature',
  icon: NewIcon,
  label: t('navigation.newFeature'),
  permission: 'view_new_feature',
}
```

#### 4. Add Translations
```typescript
// resources/js/lib/i18n.ts - Add to both fr and en objects
newFeature: {
  title: 'New Feature',
  create: 'Create',
  edit: 'Edit',
  delete: 'Delete',
}
```

### Environment Variables Required

```env
# Application
APP_NAME="HSE SaaS"
APP_ENV=production
APP_KEY= # Generate with php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hse_saas
DB_USERNAME=root
DB_PASSWORD=

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Encryption
ENCRYPTION_KEY= # 32-byte base64 encoded key for AES-256

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null

# Sanctum (if using custom domain)
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=.your-domain.com
```

### Dependencies to Install

```bash
# Backend
composer install

# Frontend
npm install

# Key generation
php artisan key:generate
php artisan storage:link
```

---

## Change Log

### v1.0.0 (April 28, 2026)

#### Added
- Initial Laravel 11 backend setup with multi-tenancy
- Complete database schema with 17 migrations
- AES-256-GCM encryption service for sensitive data
- Tenant-aware caching service with Redis
- Security headers middleware
- 8 API controllers with full CRUD operations
- 18 Eloquent models with relationships and casts
- 12 database seeders for sample data
- Role-based access control with Spatie permissions
- React 18 SPA foundation with Vite
- TypeScript configuration with path aliases
- Tailwind CSS with custom color palette
- React Router with protected/public route wrappers
- TanStack Query for data fetching and caching
- React i18next with French/English translations
- Theme provider with dark mode support
- Auth provider with login/logout flow
- Sidebar and TopBar layout components
- Skeleton loaders for loading states
- Empty state component
- 12 page components (Dashboard, Login, KPI, SOR, Workers, Permits, Inspections, Training, PPE, Profile, Settings, 404)
- Framer Motion animations throughout
- React Hot Toast notifications
- API client with Axios interceptors

### v1.1.0 (April 28, 2026)

#### Changed
- **User Model**: Split `name` field into `first_name` and `last_name`
- **Project Access Control**: Added `project_access_type` field with values:
  - `all` - User can see all company projects
  - `pole` - User can see projects within their assigned pole
  - `projects` - User can only see specifically assigned projects
- **Pole Assignment**: Added `pole_id` foreign key for pole-level access
- **User-Projects Pivot**: Created `user_projects` table for specific project assignments
- **AuthController**: Updated `formatUser()` to include `project_access` object
- **UserSeeder**: Updated with example users showing different access types:
  - Admin/HSE Manager: `project_access_type = 'all'`
  - Engineer/Supervisor: `project_access_type = 'projects'`
  - HR Director: `project_access_type = 'all'`
- **Frontend AuthProvider**: Updated User interface with `first_name`, `last_name`, `project_access`
- **Profile Page**: Added project access info card showing user's access level

### v1.2.0 (April 28, 2026)

#### Changed
- **Translations Modularization**: Split monolithic `i18n.ts` into separate JSON files:
  - `resources/js/locales/fr/` - French translations (common, navigation, dashboard, modules, messages)
  - `resources/js/locales/en/` - English translations (same structure)
  - Easy to add new languages by copying the folder structure
- **Cookie Consent System**: Created comprehensive cookie management:
  - `CookieConsent` component with animated modal
  - Three cookie types: Essential, Functional (tracking), Preferences
  - `useCookieConsent()` hook for checking permissions
  - `trackAction()` function for internal app analytics
  - User can accept all, reject non-essential, or customize
- **Mobile Responsiveness**: Fully responsive sidebar:
  - Desktop: Collapsible sidebar (72px - 280px)
  - Mobile: Drawer menu with backdrop blur
  - Hamburger menu button on mobile
  - Staggered navigation item animations
- **Fluid Animations**: Added professional micro-interactions:
  - Icon scale + rotation on hover (`whileHover={{ scale: 1.1, rotate: 5 }}`)
  - Active indicator with spring physics
  - Mobile drawer slide animation with spring damping
  - Collapsible sections with height animations
  - Page loading spinner with infinite rotation
- **App.tsx Updates**: Integrated cookie consent component globally

#### New Files
- `resources/js/components/cookie-consent.tsx`
- `resources/js/locales/fr/common.json`
- `resources/js/locales/fr/navigation.json`
- `resources/js/locales/fr/dashboard.json`
- `resources/js/locales/fr/modules.json`
- `resources/js/locales/fr/messages.json`
- `resources/js/locales/en/*.json` (same 5 files)

#### Technical Decisions
- Used React.lazy() for code splitting
- Implemented optimistic updates with TanStack Query
- Created tenant-aware cache keys for data isolation
- Used CSS custom properties for theme customization
- Implemented skeleton loaders matching content layout
- Cookie consent stored in localStorage (not external cookies)
- Mobile-first responsive approach with Tailwind breakpoints

#### File Structure Established
- All components in `resources/js/components/`
- All pages in `resources/js/pages/`
- All API logic in `resources/js/lib/api.ts`
- All translations in `resources/js/locales/{lang}/`
- All routes in `resources/js/routes/index.tsx`

---

## Notes for Future Development

### Performance Considerations
- Monitor cache hit rates in Redis
- Use database query logging in development
- Implement pagination on all list endpoints
- Use eager loading to avoid N+1 queries
- Lazy load images and heavy components

### Security Checklist
- [ ] All API endpoints behind auth middleware
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] Sensitive data encrypted at rest
- [ ] Input validation on all endpoints
- [ ] SQL injection protection via Eloquent
- [ ] XSS protection via React's built-in escaping

### Scalability Planning
- Queue system ready for background jobs
- Database indexing strategy in place
- Caching layer implemented
- Ready for horizontal scaling with shared Redis

### Testing Strategy
- Unit tests for services
- Feature tests for API endpoints
- Component tests for React components
- E2E tests for critical user flows

### v1.3.0 (April 28, 2026) - Design Overhaul

#### Design Philosophy (impeccable skill application)
**Escaped the "AI-coded" look** by applying production-grade design principles:

**Color Strategy: Committed**
- Maritime Blue (`oklch(55% 0.10 250)`) carries 30-60% of the surface
- Not the generic "SaaS blue" - a deeper, more confident naval tone
- Warm neutrals (tinted toward 60° hue) instead of cold grays
- Light mode default (physical scene: portacabin in afternoon sun)

**Banned Elements Removed:**
- ❌ No gradient text (`background-clip: text`)
- ❌ No side-stripe borders on cards
- ❌ No identical card grids
- ❌ No hero-metric template (big number + small label + gradient)
- ❌ No glassmorphism as default
- ❌ No construction clichés (hard hats, cones, caution tape)

**Typography & Layout:**
- OKLCH color space for perceptually uniform colors
- Inter font family with proper weights (300-700)
- Max body width: 65ch for readability
- Varying spacing for rhythm (not uniform padding)
- Cards only when truly needed

**Motion Design:**
- Ease-out with exponential curves (`cubic-bezier(0.165, 0.84, 0.44, 1)`)
- No bounce, no elastic animations
- Transform and opacity only (no layout property animations)
- Quick 150ms for micro-interactions, 350ms for page transitions

#### Files Created/Updated
- **PRODUCT.md** - Product definition with physical scene, anti-references, strategic principles
- **DESIGN.md** - Complete design system with OKLCH palette, typography, spacing, animations
- **tailwind.config.js** - Updated with OKLCH colors, new animations (float, gradient-shift, reveal-up, draw-line)
- **app.css** - Updated CSS variables to use OKLCH maritime blue and warm neutrals
- **landing.blade.php** - Stunning landing page featuring:
  - Abstract geometric shapes (not stock photos)
  - Floating animations with CSS keyframes
  - Alternating feature layouts (image left/right)
  - Real UI mockups in screenshots
  - Trust badges with testimonials
  - Professional CTA section
  - Complete footer with links
  - Mobile-responsive navigation
- **routes/web.php** - Created with landing page routes

#### Landing Page Sections
1. **Navigation** - Fixed header with backdrop blur, mobile drawer menu
2. **Hero** - Geometric background shapes, compelling headline, UI mockup, trust indicators
3. **Features** - 3 feature blocks with alternating layouts:
   - Permis de travail (document management)
   - Observations (safety tracking)
   - Personnel (certification management)
4. **Testimonials** - 3 real-world quotes with avatars
5. **CTA** - Maritime blue background with grid pattern
6. **Footer** - 4-column layout with links, social icons, legal

#### Design Decisions
- **Physical scene**: "Safety manager in a portacabin at 2pm, afternoon sun streaming through dusty windows"
- **Committed color**: Maritime blue as primary (not generic SaaS blue)
- **No cards for hero**: Full-width sections with breathing room
- **Real data in mockups**: Actual numbers, realistic scenarios
- **Professional but human**: Not corporate sterile, not playful either

---

**End of Implementation Record**
