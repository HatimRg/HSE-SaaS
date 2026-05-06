import React, { Suspense, useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter, Routes, Route, Outlet, useLocation, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster, toast } from 'react-hot-toast';
import { Sun, Moon, Languages, Shield } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import '../css/app.css';
import { ThemeProvider, useTheme } from './components/theme-provider';
import { useTranslation } from 'react-i18next';
import { AuthProvider, useAuth } from './components/auth-provider';
import './lib/i18n'; // Initialize i18n
import { Sidebar } from './components/sidebar';
import { TopBar } from './components/top-bar';
import DashboardPage from './pages/dashboard';
import AdminDashboardPage from './pages/admin-dashboard';
import EnterpriseMonitoringPage from './pages/enterprise-monitoring';
import OshaCompliancePage from './pages/osha-compliance';
import RiskAssessmentPage from './pages/risk-assessment';
import IncidentInvestigationPage from './pages/incident-investigation';
import CompanyBrandingPage from './pages/company-branding';
import SuperAdminPage from './pages/super-admin';
import AnalyticsPage from './pages/analytics';
import KpiPage from './pages/kpi';
import SorPage from './pages/sor';
import PermitsPage from './pages/permits';
import InspectionsPage from './pages/inspections';
import WorkersPage from './pages/workers';
import TrainingPage from './pages/training';
import PpePage from './pages/ppe';
import LibraryPage from './pages/library';
import CommunityPage from './pages/community';
import SettingsPage from './pages/settings';
import ProfilePage from './pages/profile';
import NotFoundPage from './pages/not-found';
import ErrorPage from './pages/error';
import EnvironmentPage from './pages/environment';
import UsersPage from './pages/users';
import ProjectsPage from './pages/projects';

// Create optimized QueryClient with performance settings
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      gcTime: 1000 * 60 * 30, // 30 minutes (renamed from cacheTime)
      retry: 1,
      refetchOnWindowFocus: false,
    },
    mutations: {
      retry: 0,
    },
  },
});

// Simple loading component
function AppLoading() {
  return (
    <div className="flex h-screen w-full items-center justify-center bg-background">
      <div className="text-center">
        <div className="h-12 w-12 mx-auto mb-4 rounded-full border-4 border-primary border-t-transparent animate-spin" />
        <p className="text-sm text-muted-foreground">Chargement...</p>
      </div>
    </div>
  );
}

// Page transition wrapper
function AnimatedOutlet() {
  const location = useLocation();
  return (
    <AnimatePresence mode="wait">
      <motion.div
        key={location.pathname}
        initial={{ opacity: 0, y: 8 }}
        animate={{ opacity: 1, y: 0 }}
        exit={{ opacity: 0, y: -8 }}
        transition={{ duration: 0.15 }}
      >
        <Outlet />
      </motion.div>
    </AnimatePresence>
  );
}

// Main layout with Sidebar and TopBar
function MainLayout() {
  return (
    <div className="flex min-h-screen bg-background font-app">
      <Sidebar />
      <div className="flex-1 flex flex-col min-w-0">
        <TopBar />
        <main className="flex-1 overflow-y-auto p-6 max-w-[1600px] w-full mx-auto">
          <AnimatedOutlet />
        </main>
      </div>
    </div>
  );
}

// SafeSite login component with animated background and fancy fonts
function LoginPage() {
  const { t, i18n } = useTranslation();
  const { isDark, toggle } = useTheme();
  const { login } = useAuth();
  const [isLoading, setIsLoading] = React.useState(false);
  const [showLangMenu, setShowLangMenu] = React.useState(false);
  const [email, setEmail] = React.useState('');
  const [password, setPassword] = React.useState('');
  
  const handleLanguageChange = (lang: string) => {
    i18n.changeLanguage(lang);
    setShowLangMenu(false);
    const langName = lang === 'fr' ? 'Français' : 'English';
    toast.success(`Language changed to ${langName}`);
  };
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    
    try {
      await login(email, password);
    } catch (error) {
      setIsLoading(false);
    }
  };

  return (
    <div className={`min-h-screen flex transition-colors duration-300 ${isDark ? 'bg-[hsl(220,15%,8%)]' : 'bg-[hsl(40,20%,99%)]'}`}>
      
      {/* Left panel - Brand presence */}
      <div className={`hidden lg:flex lg:w-[45%] flex-col justify-between p-12 relative overflow-hidden ${isDark ? 'bg-[hsl(217,72%,42%)]' : 'bg-[hsl(217,72%,42%)]'}`}>
        {/* Subtle geometric pattern */}
        <div className="absolute inset-0 opacity-10">
          <svg width="100%" height="100%">
            <defs>
              <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
                <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" strokeWidth="0.5" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
          </svg>
        </div>
        {/* Abstract safety structure shapes */}
        <div className="absolute top-20 right-20 opacity-20">
          <svg className="w-32 h-32 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="0.5">
            <path d="M12 2l8.66 5v10L12 22l-8.66-5V7L12 2z" />
          </svg>
        </div>
        <div className="absolute bottom-32 left-16 opacity-15">
          <svg className="w-24 h-24 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="0.5">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
          </svg>
        </div>

        {/* Brand content */}
        <div className="relative z-10">
          <div className="flex items-center gap-3 mb-2">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
              <Shield className="h-6 w-6 text-white" />
            </div>
            <span className="text-white text-xl font-bold tracking-tight">{t('appName')}</span>
          </div>
        </div>

        <div className="relative z-10">
          <h1 className="text-4xl font-extrabold text-white leading-tight mb-4" style={{ letterSpacing: '-0.03em' }}>
            {t('login.heroTitle1')}<br />{t('login.heroTitle2')}<br />{t('login.heroTitle3')}
          </h1>
          <p className="text-white/70 text-lg max-w-md leading-relaxed">
            {t('login.heroSubtitle')}
          </p>
        </div>

        <div className="relative z-10 flex items-center gap-4">
          <div className="flex -space-x-2">
            {['S', 'M', 'A', 'K'].map((letter, i) => (
              <div key={i} className="h-8 w-8 rounded-full bg-white/20 border-2 border-[hsl(217,72%,42%)] flex items-center justify-center text-xs font-semibold text-white">
                {letter}
              </div>
            ))}
          </div>
          <p className="text-white/60 text-sm">{t('login.trustedBy')}</p>
        </div>
      </div>

      {/* Right panel - Login form */}
      <div className="flex-1 flex flex-col">
        {/* Top controls */}
        <div className="flex items-center justify-between p-4">
          <div className="lg:hidden flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
              <Shield className="h-5 w-5" />
            </div>
            <span className="font-bold text-lg">{t('appName')}</span>
          </div>
          <div className="flex items-center gap-2 ml-auto">
            <button
              onClick={() => {
                toggle();
              }}
              className="flex h-9 w-9 items-center justify-center rounded-lg border border-border hover:bg-muted transition-colors"
              title={isDark ? t('theme.light', 'Light Mode') : t('theme.dark', 'Dark Mode')}
            >
              <AnimatePresence mode="wait" initial={false}>
                {isDark ? (
                  <motion.div key="sun" initial={{ rotate: -90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} exit={{ rotate: 90, opacity: 0 }} transition={{ duration: 0.2 }}>
                    <Sun className="h-4 w-4" />
                  </motion.div>
                ) : (
                  <motion.div key="moon" initial={{ rotate: 90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} exit={{ rotate: -90, opacity: 0 }} transition={{ duration: 0.2 }}>
                    <Moon className="h-4 w-4" />
                  </motion.div>
                )}
              </AnimatePresence>
            </button>
            <div className="relative">
              <button
                onClick={() => setShowLangMenu(!showLangMenu)}
                className="flex h-9 items-center gap-1.5 rounded-lg border border-border px-3 text-sm hover:bg-muted transition-colors"
              >
                <Languages className="h-4 w-4" />
                <span className="font-medium">{i18n.language.toUpperCase()}</span>
              </button>
              <AnimatePresence>
                {showLangMenu && (
                  <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -8 }}
                    className="absolute right-0 top-full mt-2 rounded-lg border border-border bg-card shadow-lg overflow-hidden min-w-[120px] z-50"
                  >
                    <button onClick={() => handleLanguageChange('fr')} className={`w-full px-4 py-2.5 text-left text-sm hover:bg-muted transition-colors ${i18n.language === 'fr' ? 'text-primary font-medium' : ''}`}>
                      Français
                    </button>
                    <button onClick={() => handleLanguageChange('en')} className={`w-full px-4 py-2.5 text-left text-sm hover:bg-muted transition-colors ${i18n.language === 'en' ? 'text-primary font-medium' : ''}`}>
                      English
                    </button>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>
          </div>
        </div>

        {/* Centered form */}
        <div className="flex-1 flex items-center justify-center p-6">
          <div className="w-full max-w-sm">
            {/* Mobile-only brand */}
            <div className="lg:hidden mb-8 text-center">
              <h1 className="text-3xl font-extrabold tracking-tight">{t('appName')}</h1>
              <p className="text-muted-foreground text-sm mt-1">{t('login.tagline')}</p>
            </div>

            <div className="mb-8">
              <h2 className="text-2xl font-bold tracking-tight">{t('login.title')}</h2>
              <p className="text-muted-foreground mt-1.5 text-sm">{t('login.subtitle')}</p>
            </div>
            
            <form onSubmit={handleSubmit} className="space-y-5">
              <div>
                <label htmlFor="email" className="block text-sm font-medium mb-2">
                  {t('login.email')}
                </label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  autoComplete="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="block w-full px-3 py-2.5 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all"
                  placeholder="admin@example.com"
                />
              </div>

              <div>
                <label htmlFor="password" className="block text-sm font-medium mb-2">
                  {t('login.password')}
                </label>
                <input
                  id="password"
                  name="password"
                  type="password"
                  autoComplete="current-password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="block w-full px-3 py-2.5 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all"
                  placeholder="••••••••"
                />
              </div>

              <div className="flex items-center justify-between">
                <label className="flex items-center cursor-pointer">
                  <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    className="h-4 w-4 text-primary border-input rounded focus:ring-ring bg-background"
                  />
                  <span className="ml-2 text-sm">{t('login.rememberMe')}</span>
                </label>
                <a href="#" className="text-sm text-primary hover:text-primary/80 transition-colors">
                  {t('login.forgotPassword')}
                </a>
              </div>

              <button
                type="submit"
                disabled={isLoading}
                className="w-full flex justify-center items-center py-2.5 px-4 bg-primary text-primary-foreground font-semibold rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isLoading ? (
                  <>
                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-primary-foreground" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {t('login.signingIn')}
                  </>
                ) : (
                  t('login.signIn')
                )}
              </button>
            </form>

            {/* Demo credentials */}
            <div className="mt-8 pt-6 border-t border-border">
              <p className="text-xs font-medium text-muted-foreground mb-3">{t('login.demoCredentials')}</p>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => {
                    setEmail('admin@hse-saas.com');
                    setPassword('Admin123!');
                  }}
                  className="text-left text-xs p-3 rounded-lg border border-border hover:bg-muted transition-colors"
                >
                  <span className="font-semibold text-foreground block">Admin</span>
                  <span className="text-muted-foreground">admin@hse-saas.com</span>
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setEmail('superadmin@hse-saas.com');
                    setPassword('SuperAdmin123!');
                  }}
                  className="text-left text-xs p-3 rounded-lg border border-border hover:bg-muted transition-colors"
                >
                  <span className="font-semibold text-foreground block">Super Admin</span>
                  <span className="text-muted-foreground">superadmin@hse-saas.com</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="p-4 text-center">
          <p className="text-xs text-muted-foreground">{t('login.copyright')}</p>
        </div>
      </div>
    </div>
  );
}

// Route guard: redirect to login if not authenticated
function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return <AppLoading />;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return <>{children}</>;
}

// Safety: reset body overflow on route change to prevent stuck overlays
function BodyOverflowReset() {
  const location = useLocation();
  useEffect(() => {
    // Small delay to let modal exit animations complete
    const timer = setTimeout(() => {
      if (!document.querySelector('[data-modal-open]')) {
        document.body.style.overflow = '';
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [location.pathname]);
  return null;
}

// Change Password Page (for forced password changes)
function ChangePasswordPage() {
  const { t } = useTranslation();
  const { user, changePassword } = useAuth();
  const [form, setForm] = useState({ current_password: '', new_password: '', new_password_confirmation: '' });
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (form.new_password !== form.new_password_confirmation) {
      setError(t('messages:errors.passwordMismatch', 'Passwords do not match'));
      return;
    }
    try {
      await changePassword(form.current_password, form.new_password);
    } catch (err: any) {
      setError(err.response?.data?.message || t('messages:errors.generic', 'Failed to change password'));
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center p-4 bg-background">
      <div className="w-full max-w-md rounded-xl border border-border bg-card p-8 shadow-lg">
        <h1 className="text-2xl font-bold mb-2">{t('modules:settings.changePassword', 'Change Password')}</h1>
        <p className="text-muted-foreground mb-6">{t('modules:settings.passwordRequired', 'You must change your password before continuing.')}</p>
        {error && <p className="text-red-500 text-sm mb-4">{error}</p>}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">{t('modules:settings.currentPassword', 'Current Password')}</label>
            <input type="password" value={form.current_password} onChange={e => setForm({ ...form, current_password: e.target.value })} required className="w-full rounded-lg border border-border bg-background px-3 py-2" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('modules:settings.newPassword', 'New Password')}</label>
            <input type="password" value={form.new_password} onChange={e => setForm({ ...form, new_password: e.target.value })} required className="w-full rounded-lg border border-border bg-background px-3 py-2" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t('modules:settings.confirmPassword', 'Confirm Password')}</label>
            <input type="password" value={form.new_password_confirmation} onChange={e => setForm({ ...form, new_password_confirmation: e.target.value })} required className="w-full rounded-lg border border-border bg-background px-3 py-2" />
          </div>
          <button type="submit" className="w-full rounded-lg bg-primary px-4 py-2 font-medium text-primary-foreground hover:opacity-90 transition-opacity">
            {t('modules:settings.updatePassword', 'Update Password')}
          </button>
        </form>
      </div>
    </div>
  );
}

// Root App component
function App() {
  return (
    <ThemeProvider>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter>
          <AuthProvider>
            <BodyOverflowReset />
            <Routes>
              <Route path="/login" element={<LoginPage />} />
              <Route path="/change-password" element={<ChangePasswordPage />} />
              <Route element={<ProtectedRoute><MainLayout /></ProtectedRoute>}>
                <Route path="/dashboard" element={<DashboardPage />} />
                <Route path="/admin" element={<AdminDashboardPage />} />
                <Route path="/enterprise" element={<EnterpriseMonitoringPage />} />
                <Route path="/osha" element={<OshaCompliancePage />} />
                <Route path="/risk" element={<RiskAssessmentPage />} />
                <Route path="/investigation" element={<IncidentInvestigationPage />} />
                <Route path="/branding" element={<CompanyBrandingPage />} />
                <Route path="/super-admin" element={<SuperAdminPage />} />
                <Route path="/analytics" element={<AnalyticsPage />} />
                <Route path="/kpi" element={<KpiPage />} />
                <Route path="/sor" element={<SorPage />} />
                <Route path="/permits" element={<PermitsPage />} />
                <Route path="/inspections" element={<InspectionsPage />} />
                <Route path="/workers" element={<WorkersPage />} />
                <Route path="/training" element={<TrainingPage />} />
                <Route path="/ppe" element={<PpePage />} />
                <Route path="/environment" element={<EnvironmentPage />} />
                <Route path="/library" element={<LibraryPage />} />
                <Route path="/community" element={<CommunityPage />} />
                <Route path="/settings" element={<SettingsPage />} />
                <Route path="/profile" element={<ProfilePage />} />
                <Route path="/users" element={<UsersPage />} />
                <Route path="/projects" element={<ProjectsPage />} />
              </Route>
              <Route path="/" element={<LoginPage />} />
              <Route path="*" element={<NotFoundPage />} />
            </Routes>
          </AuthProvider>
          <Toaster position="top-right" />
        </BrowserRouter>
      </QueryClientProvider>
    </ThemeProvider>
  );
}

// Mount the app
const root = document.getElementById('app');
if (root) {
  ReactDOM.createRoot(root).render(
    <React.StrictMode>
      <Suspense fallback={<AppLoading />}>
        <App />
      </Suspense>
    </React.StrictMode>
  );
}
