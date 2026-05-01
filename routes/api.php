<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KpiReportController;
use App\Http\Controllers\Api\SorReportController;
use App\Http\Controllers\Api\WorkPermitController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\PpeController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\CompanyBrandingController;
use App\Http\Controllers\Api\OshaComplianceController;
use App\Http\Controllers\Api\RiskAssessmentController;
use App\Http\Controllers\Api\IncidentInvestigationController;
use App\Http\Controllers\Api\ImportExportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware(['auth:sanctum', 'tenant', 'security.headers'])->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('throttle:dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->middleware('throttle:dashboard');
    Route::get('/dashboard/charts', [DashboardController::class, 'charts'])->middleware('throttle:dashboard');
    
    // Analytics
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('/analytics/kpis', [AnalyticsController::class, 'kpis']);
    Route::get('/analytics/trends', [AnalyticsController::class, 'trends']);
    Route::get('/analytics/predictive', [AnalyticsController::class, 'predictive']);
    Route::get('/analytics/cost-analysis', [AnalyticsController::class, 'costAnalysis']);
    Route::get('/analytics/risk-matrix', [AnalyticsController::class, 'riskMatrix']);
    Route::get('/analytics/compliance', [AnalyticsController::class, 'compliance']);
    
    // KPI Reports
    Route::get('/kpi-reports', [KpiReportController::class, 'index']);
    Route::get('/kpi-reports/summary', [KpiReportController::class, 'summary']);
    Route::post('/kpi-reports', [KpiReportController::class, 'store']);
    Route::get('/kpi-reports/{id}', [KpiReportController::class, 'show']);
    Route::put('/kpi-reports/{id}', [KpiReportController::class, 'update']);
    Route::delete('/kpi-reports/{id}', [KpiReportController::class, 'destroy']);
    Route::post('/kpi-reports/{id}/submit', [KpiReportController::class, 'submit']);
    Route::post('/kpi-reports/{id}/approve', [KpiReportController::class, 'approve']);
    Route::post('/kpi-reports/{id}/reject', [KpiReportController::class, 'reject']);
    
    // SOR Reports
    Route::get('/sor-reports', [SorReportController::class, 'index']);
    Route::post('/sor-reports', [SorReportController::class, 'store']);
    Route::get('/sor-reports/{id}', [SorReportController::class, 'show']);
    Route::put('/sor-reports/{id}', [SorReportController::class, 'update']);
    Route::delete('/sor-reports/{id}', [SorReportController::class, 'destroy']);
    Route::post('/sor-reports/{id}/close', [SorReportController::class, 'close']);
    Route::post('/sor-reports/{id}/photos', [SorReportController::class, 'uploadPhotos']);
    
    // Work Permits
    Route::get('/work-permits', [WorkPermitController::class, 'index']);
    Route::get('/work-permits/types', [WorkPermitController::class, 'types']);
    Route::post('/work-permits', [WorkPermitController::class, 'store']);
    Route::get('/work-permits/{id}', [WorkPermitController::class, 'show']);
    Route::put('/work-permits/{id}', [WorkPermitController::class, 'update']);
    Route::delete('/work-permits/{id}', [WorkPermitController::class, 'destroy']);
    Route::post('/work-permits/{id}/approve', [WorkPermitController::class, 'approve']);
    Route::post('/work-permits/{id}/reject', [WorkPermitController::class, 'reject']);
    Route::post('/work-permits/{id}/suspend', [WorkPermitController::class, 'suspend']);
    Route::post('/work-permits/{id}/renew', [WorkPermitController::class, 'renew']);
    
    // Inspections
    Route::get('/inspections', [InspectionController::class, 'index']);
    Route::get('/inspections/types', [InspectionController::class, 'types']);
    Route::post('/inspections', [InspectionController::class, 'store']);
    Route::get('/inspections/{id}', [InspectionController::class, 'show']);
    Route::put('/inspections/{id}', [InspectionController::class, 'update']);
    Route::delete('/inspections/{id}', [InspectionController::class, 'destroy']);
    Route::post('/inspections/{id}/verify', [InspectionController::class, 'verify']);
    
    // Workers
    Route::get('/workers', [WorkerController::class, 'index']);
    Route::post('/workers', [WorkerController::class, 'store']);
    Route::get('/workers/{id}', [WorkerController::class, 'show']);
    Route::put('/workers/{id}', [WorkerController::class, 'update']);
    Route::delete('/workers/{id}', [WorkerController::class, 'destroy']);
    Route::get('/workers/{id}/qualifications', [WorkerController::class, 'qualifications']);
    Route::post('/workers/{id}/qualifications', [WorkerController::class, 'addQualification']);
    Route::get('/workers/{id}/trainings', [WorkerController::class, 'trainings']);
    Route::get('/workers/{id}/ppe', [WorkerController::class, 'ppe']);
    Route::post('/workers/import', [WorkerController::class, 'import'])->middleware('throttle:import');
    
    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::get('/projects/{id}/team', [ProjectController::class, 'team']);
    Route::post('/projects/{id}/team', [ProjectController::class, 'addTeamMember']);
    Route::delete('/projects/{id}/team/{userId}', [ProjectController::class, 'removeTeamMember']);
    Route::get('/projects/{id}/stats', [ProjectController::class, 'stats']);
    
    // Training Sessions (using TrainingController)
    Route::get('/training-sessions', [TrainingController::class, 'index']);
    Route::post('/training-sessions', [TrainingController::class, 'store']);
    Route::get('/training-sessions/{id}', [TrainingController::class, 'show']);
    Route::put('/training-sessions/{id}', [TrainingController::class, 'update']);
    Route::delete('/training-sessions/{id}', [TrainingController::class, 'destroy']);
    Route::post('/training-sessions/{id}/attendance', [TrainingController::class, 'markAttendance']);
    Route::get('/training-sessions/{id}/attendees', [TrainingController::class, 'addParticipants']);
    Route::get('/training-sessions/statistics', [TrainingController::class, 'statistics']);
    Route::post('/training-sessions/{id}/certificates', [TrainingController::class, 'generateCertificates']);
    
    // PPE
    Route::get('/ppe/items', [PpeController::class, 'items']);
    Route::post('/ppe/items', [PpeController::class, 'storeItem']);
    Route::get('/ppe/stock', [PpeController::class, 'stock']);
    Route::post('/ppe/stock', [PpeController::class, 'addStock']);
    Route::post('/ppe/assign', [PpeController::class, 'assignPpe']);
    Route::get('/ppe/assignments', [PpeController::class, 'assignments']);
    Route::get('/ppe/low-stock', [PpeController::class, 'lowStock']);
    
    // Library
    Route::get('/library/folders', [LibraryController::class, 'folders']);
    Route::post('/library/folders', [LibraryController::class, 'createFolder']);
    Route::get('/library/documents', [LibraryController::class, 'documents']);
    Route::post('/library/documents', [LibraryController::class, 'uploadDocument']);
    Route::get('/library/documents/{id}/download', [LibraryController::class, 'downloadDocument']);
    Route::delete('/library/documents/{id}', [LibraryController::class, 'deleteDocument']);
    Route::get('/library/search', [LibraryController::class, 'search']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    
    // Community
    Route::get('/community/posts', [CommunityController::class, 'posts']);
    Route::post('/community/posts', [CommunityController::class, 'createPost']);
    Route::delete('/community/posts/{id}', [CommunityController::class, 'deletePost']);
    Route::post('/community/posts/{id}/like', [CommunityController::class, 'likePost']);
    Route::post('/community/posts/{id}/comment', [CommunityController::class, 'addComment']);
    Route::get('/community/posts/{id}/comments', [CommunityController::class, 'comments']);
    
    // Risk Assessment
    Route::get('/risk-assessments', [RiskAssessmentController::class, 'index']);
    Route::post('/risk-assessments', [RiskAssessmentController::class, 'store']);
    Route::get('/risk-assessments/{id}', [RiskAssessmentController::class, 'show']);
    Route::put('/risk-assessments/{id}', [RiskAssessmentController::class, 'update']);
    Route::delete('/risk-assessments/{id}', [RiskAssessmentController::class, 'destroy']);
    Route::get('/risk-assessments/matrix', [RiskAssessmentController::class, 'matrix']);
    
    // Incident Investigation
    Route::get('/incidents', [IncidentInvestigationController::class, 'index']);
    Route::post('/incidents', [IncidentInvestigationController::class, 'store']);
    Route::get('/incidents/{id}', [IncidentInvestigationController::class, 'show']);
    Route::put('/incidents/{id}', [IncidentInvestigationController::class, 'update']);
    Route::delete('/incidents/{id}', [IncidentInvestigationController::class, 'destroy']);
    Route::post('/incidents/{id}/investigate', [IncidentInvestigationController::class, 'investigate']);
    Route::post('/incidents/{id}/close', [IncidentInvestigationController::class, 'closeIncident']);
    
    // OSHA Compliance
    Route::get('/osha/compliance', [OshaComplianceController::class, 'compliance']);
    Route::get('/osha/recordables', [OshaComplianceController::class, 'recordables']);
    Route::get('/osha/trir', [OshaComplianceController::class, 'trir']);
    Route::get('/osha/dart', [OshaComplianceController::class, 'dart']);
    Route::get('/osha/ltifr', [OshaComplianceController::class, 'ltifr']);
    Route::get('/osha/300-log', [OshaComplianceController::class, 'log300']);
    
    // Company Branding
    Route::get('/company/branding', [CompanyBrandingController::class, 'show']);
    Route::put('/company/branding', [CompanyBrandingController::class, 'update']);
    Route::post('/company/logo', [CompanyBrandingController::class, 'uploadLogo']);
    Route::delete('/company/logo', [CompanyBrandingController::class, 'removeLogo']);
    
    // Super Admin (requires super_admin role)
    Route::middleware('role:super_admin')->prefix('super-admin')->group(function () {
        Route::get('/companies', [SuperAdminController::class, 'companies']);
        Route::post('/companies', [SuperAdminController::class, 'createCompany']);
        Route::put('/companies/{id}', [SuperAdminController::class, 'updateCompany']);
        Route::post('/companies/{id}/suspend', [SuperAdminController::class, 'suspendCompany']);
        Route::post('/companies/{id}/activate', [SuperAdminController::class, 'activateCompany']);
        Route::get('/stats', [SuperAdminController::class, 'stats']);
        Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs']);
    });
    
    // Users (Admin only)
    Route::middleware('role:admin|super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::post('/users/{id}/activate', [UserController::class, 'activate']);
        Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
    });
    
    // Import/Export
    Route::post('/import/workers', [ImportExportController::class, 'importWorkers'])->middleware('throttle:import');
    Route::get('/export/kpi-reports', [ImportExportController::class, 'exportKpiReports'])->middleware('throttle:export');
    Route::get('/export/workers', [ImportExportController::class, 'exportWorkers'])->middleware('throttle:export');
    Route::get('/export/sor-reports', [ImportExportController::class, 'exportSorReports'])->middleware('throttle:export');
    Route::get('/export/inspections', [ImportExportController::class, 'exportInspections'])->middleware('throttle:export');
    Route::get('/export/work-permits', [ImportExportController::class, 'exportWorkPermits'])->middleware('throttle:export');
    
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
});
