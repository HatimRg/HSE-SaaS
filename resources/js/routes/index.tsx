import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../components/auth-provider';

// Lazy load pages for better performance
const LoginPage = React.lazy(() => import('../pages/login'));
const DashboardPage = React.lazy(() => import('../pages/dashboard'));
const KpiPage = React.lazy(() => import('../pages/kpi'));
const SorPage = React.lazy(() => import('../pages/sor'));
const PermitsPage = React.lazy(() => import('../pages/permits'));
const InspectionsPage = React.lazy(() => import('../pages/inspections'));
const WorkersPage = React.lazy(() => import('../pages/workers'));
const TrainingPage = React.lazy(() => import('../pages/training'));
const PpePage = React.lazy(() => import('../pages/ppe'));
const ProfilePage = React.lazy(() => import('../pages/profile'));
const SettingsPage = React.lazy(() => import('../pages/settings'));
const NotFoundPage = React.lazy(() => import('../pages/not-found'));

// Protected route wrapper
function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}

// Public route wrapper (redirects to dashboard if authenticated)
function PublicRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
      </div>
    );
  }

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  return <>{children}</>;
}

export function AppRoutes() {
  return (
    <Routes>
      {/* Public routes */}
      <Route
        path="/login"
        element={
          <PublicRoute>
            <LoginPage />
          </PublicRoute>
        }
      />

      {/* Protected routes */}
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <DashboardPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/kpi"
        element={
          <ProtectedRoute>
            <KpiPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/sor"
        element={
          <ProtectedRoute>
            <SorPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/permits"
        element={
          <ProtectedRoute>
            <PermitsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/inspections"
        element={
          <ProtectedRoute>
            <InspectionsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/workers"
        element={
          <ProtectedRoute>
            <WorkersPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/training"
        element={
          <ProtectedRoute>
            <TrainingPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/ppe"
        element={
          <ProtectedRoute>
            <PpePage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/profile"
        element={
          <ProtectedRoute>
            <ProfilePage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/settings"
        element={
          <ProtectedRoute>
            <SettingsPage />
          </ProtectedRoute>
        }
      />

      {/* Redirects */}
      <Route path="/" element={<Navigate to="/dashboard" replace />} />

      {/* 404 */}
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
