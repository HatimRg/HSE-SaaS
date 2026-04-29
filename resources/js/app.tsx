import React, { Suspense } from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import '../css/app.css';
import { ThemeProvider } from './components/theme-provider';

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


// Simple dashboard component
function Dashboard() {
  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    window.location.href = '/login';
  };

  const getUserInfo = () => {
    const token = localStorage.getItem('auth_token');
    return token ? 'Admin User' : 'Guest';
  };

  return (
    <div className="min-h-screen bg-background font-app">
      {/* Header */}
      <header className="bg-card border-b border-border shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <div className="h-8 w-8 rounded-full bg-primary flex items-center justify-center mr-3">
                <svg className="h-4 w-4 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <h1 className="text-xl font-semibold text-foreground">SafeSite</h1>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-muted-foreground">Welcome, {getUserInfo()}</span>
              <button
                onClick={handleLogout}
                className="px-3 py-1 text-sm bg-destructive text-destructive-foreground rounded hover:bg-destructive/90 transition-colors"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Welcome Section */}
        <div className="mb-8">
          <h2 className="text-2xl font-bold text-foreground mb-2">Dashboard</h2>
          <p className="text-muted-foreground">Health, Safety & Environment Management System</p>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-card p-6 rounded-lg shadow border border-border">
            <div className="flex items-center">
              <div className="p-3 bg-primary/10 rounded-full">
                <svg className="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-muted-foreground">Workers</p>
                <p className="text-2xl font-semibold text-foreground">24</p>
              </div>
            </div>
          </div>

          <div className="bg-card p-6 rounded-lg shadow border border-border">
            <div className="flex items-center">
              <div className="p-3 bg-success/10 rounded-full">
                <svg className="h-6 w-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-muted-foreground">Inspections</p>
                <p className="text-2xl font-semibold text-foreground">142</p>
              </div>
            </div>
          </div>

          <div className="bg-card p-6 rounded-lg shadow border border-border">
            <div className="flex items-center">
              <div className="p-3 bg-warning/10 rounded-full">
                <svg className="h-6 w-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-muted-foreground">Permits</p>
                <p className="text-2xl font-semibold text-foreground">18</p>
              </div>
            </div>
          </div>

          <div className="bg-card p-6 rounded-lg shadow border border-border">
            <div className="flex items-center">
              <div className="p-3 bg-destructive/10 rounded-full">
                <svg className="h-6 w-6 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-muted-foreground">Incidents</p>
                <p className="text-2xl font-semibold text-foreground">3</p>
              </div>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-card p-6 rounded-lg shadow border border-border">
          <h3 className="text-lg font-semibold text-foreground mb-4">Quick Actions</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button className="p-4 border border-border rounded-lg hover:bg-accent transition-colors">
              <svg className="h-8 w-8 text-primary mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              <p className="text-sm font-medium text-foreground">New Inspection</p>
            </button>
            <button className="p-4 border border-border rounded-lg hover:bg-accent transition-colors">
              <svg className="h-8 w-8 text-success mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <p className="text-sm font-medium text-foreground">Create Permit</p>
            </button>
            <button className="p-4 border border-border rounded-lg hover:bg-accent transition-colors">
              <svg className="h-8 w-8 text-warning mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <p className="text-sm font-medium text-foreground">Add Worker</p>
            </button>
          </div>
        </div>
      </main>
    </div>
  );
}

// SafeSite login component with animated background and fancy fonts
function LoginPage() {
  const [isLoading, setIsLoading] = React.useState(false);
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    const formData = new FormData(e.target as HTMLFormElement);
    const email = formData.get('email') as string;
    const password = formData.get('password') as string;
    
    setIsLoading(true);
    
    // Fast, efficient loading - no unnecessary delays
    await new Promise(resolve => setTimeout(resolve, 400));
    
    // Demo validation
    if ((email === 'admin@demo.com' || email === 'engineer@demo.com') && password === 'password') {
      localStorage.setItem('auth_token', 'demo-token-' + Date.now());
      console.log('Login successful, token stored:', localStorage.getItem('auth_token'));
      window.location.href = '/dashboard';
    } else {
      setIsLoading(false);
      alert('Invalid credentials. Use admin@demo.com / password');
      console.log('Login failed for:', { email, password });
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 flex items-center justify-center p-4 relative overflow-hidden">
      
      {/* Animated Background Elements */}
      
      {/* Floating Orbs with Animation */}
      <div className="absolute top-20 left-10 w-72 h-72 bg-primary-500/20 rounded-full blur-3xl animate-float-slow"></div>
      <div className="absolute bottom-20 right-10 w-96 h-96 bg-success/20 rounded-full blur-3xl animate-float-medium"></div>
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-warning/10 rounded-full blur-3xl animate-pulse-glow"></div>
      
      {/* Animated Geometric Shapes */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        {/* Floating Hexagons */}
        <div className="absolute top-1/4 left-[10%] opacity-10">
          <svg className="w-16 h-16 text-primary animate-float-slow" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1">
            <path d="M12 2l8.66 5v10L12 22l-8.66-5V7L12 2z" />
          </svg>
        </div>
        <div className="absolute top-1/3 right-[15%] opacity-10">
          <svg className="w-20 h-20 text-success animate-float-medium" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1">
            <path d="M12 2l8.66 5v10L12 22l-8.66-5V7L12 2z" />
          </svg>
        </div>
        <div className="absolute bottom-1/4 left-[20%] opacity-10">
          <svg className="w-12 h-12 text-warning animate-float-slow" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1">
            <circle cx="12" cy="12" r="10" />
          </svg>
        </div>
        
        {/* Floating Shield Icons */}
        <div className="absolute top-[15%] right-[25%] opacity-10">
          <svg className="w-24 h-24 text-primary animate-float-medium" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
          </svg>
        </div>
        <div className="absolute bottom-[20%] right-[10%] opacity-10">
          <svg className="w-16 h-16 text-success animate-float-slow" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1">
            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        
        {/* Floating Lines */}
        <div className="absolute top-[40%] left-[5%] w-32 h-0.5 bg-gradient-to-r from-transparent via-primary/30 to-transparent animate-pulse"></div>
        <div className="absolute top-[60%] right-[8%] w-24 h-0.5 bg-gradient-to-r from-transparent via-success/30 to-transparent animate-pulse"></div>
        <div className="absolute bottom-[35%] left-[15%] w-40 h-0.5 bg-gradient-to-r from-transparent via-warning/30 to-transparent animate-pulse"></div>
        
        {/* Small Floating Dots */}
        <div className="absolute top-[25%] left-[35%] w-2 h-2 bg-primary/40 rounded-full animate-pulse"></div>
        <div className="absolute top-[45%] right-[30%] w-3 h-3 bg-success/40 rounded-full animate-pulse"></div>
        <div className="absolute bottom-[40%] left-[45%] w-2 h-2 bg-warning/40 rounded-full animate-pulse"></div>
        <div className="absolute top-[70%] right-[20%] w-2 h-2 bg-primary/40 rounded-full animate-pulse"></div>
      </div>

      {/* Grid Pattern */}
      <div className="absolute inset-0 opacity-[0.03]" style={{
        backgroundImage: 'linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px)',
        backgroundSize: '60px 60px'
      }}></div>

      <div className="relative z-10 w-full max-w-md">
        
        {/* SafeSite Branding with Fancy Font */}
        <div className="mb-8 text-center">
          <div className="mx-auto mb-6 relative">
            {/* Animated glow effect */}
            <div className="absolute inset-0 bg-gradient-to-br from-primary via-success to-warning rounded-2xl blur-lg opacity-40 animate-pulse"></div>
            <div className="relative bg-card/90 backdrop-blur-xl rounded-2xl p-6 border border-border shadow-2xl">
              <div className="flex items-center justify-center space-x-3">
                <svg className="h-10 w-10 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <svg className="h-12 w-12 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <svg className="h-10 w-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </div>
            </div>
          </div>
          {/* Fancy Font for Login Page Title */}
          <h1 className="text-4xl font-bold text-white mb-2 tracking-wide font-login">SafeSite</h1>
          <p className="text-slate-400 font-login text-sm tracking-wider uppercase">Health • Safety • Environment • Quality</p>
        </div>

        {/* Modern glassmorphism login card */}
        <div className="bg-card/90 backdrop-blur-xl rounded-2xl shadow-2xl border border-border p-8">
          <div className="mb-6">
            <h2 className="text-2xl font-semibold text-foreground mb-1 font-login">Sign In</h2>
            <p className="text-sm text-muted-foreground">Secure access to your SafeSite dashboard</p>
          </div>
          
          <form onSubmit={handleSubmit} className="space-y-5">
            {/* Email input */}
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-foreground mb-2">
                Email Address
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg className="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                  </svg>
                </div>
                <input
                  id="email"
                  name="email"
                  type="email"
                  autoComplete="email"
                  required
                  className="block w-full pl-10 pr-3 py-2.5 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                  placeholder="admin@demo.com"
                />
              </div>
            </div>

            {/* Password input */}
            <div>
              <label htmlFor="password" className="block text-sm font-medium text-foreground mb-2">
                Password
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg className="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </div>
                <input
                  id="password"
                  name="password"
                  type="password"
                  autoComplete="current-password"
                  required
                  className="block w-full pl-10 pr-3 py-2.5 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                  placeholder="••••••••"
                />
              </div>
            </div>

            {/* Remember me & forgot password */}
            <div className="flex items-center justify-between">
              <label className="flex items-center cursor-pointer">
                <input
                  id="remember"
                  name="remember"
                  type="checkbox"
                  className="h-4 w-4 text-primary border-input rounded focus:ring-primary bg-background"
                />
                <span className="ml-2 text-sm text-foreground">Remember me</span>
              </label>
              <a href="#" className="text-sm text-primary hover:text-primary/80 transition-colors">
                Forgot password?
              </a>
            </div>

            {/* Submit button with SafeSite colors */}
            <button
              type="submit"
              disabled={isLoading}
              className="w-full flex justify-center items-center py-2.5 px-4 bg-primary text-primary-foreground font-semibold rounded-lg hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-primary/25"
            >
              {isLoading ? (
                <>
                  <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-primary-foreground" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Signing in...
                </>
              ) : (
                'Sign In'
              )}
            </button>
          </form>

          {/* Demo credentials */}
          <div className="mt-6 pt-6 border-t border-border">
            <p className="text-xs font-medium text-muted-foreground mb-2">Demo Credentials:</p>
            <div className="space-y-2">
              <button
                type="button"
                onClick={() => {
                  (document.getElementById('email') as HTMLInputElement).value = 'admin@demo.com';
                  (document.getElementById('password') as HTMLInputElement).value = 'password';
                }}
                className="w-full text-left text-xs text-muted-foreground hover:text-foreground transition-colors p-2 rounded hover:bg-accent"
              >
                <strong className="text-warning">Admin:</strong> admin@demo.com / password
              </button>
              <button
                type="button"
                onClick={() => {
                  (document.getElementById('email') as HTMLInputElement).value = 'engineer@demo.com';
                  (document.getElementById('password') as HTMLInputElement).value = 'password';
                }}
                className="w-full text-left text-xs text-muted-foreground hover:text-foreground transition-colors p-2 rounded hover:bg-accent"
              >
                <strong className="text-primary">Engineer:</strong> engineer@demo.com / password
              </button>
            </div>
          </div>
        </div>

        {/* Professional footer */}
        <div className="mt-8 text-center">
          <p className="text-xs text-muted-foreground">© 2024 SafeSite Platform. All rights reserved.</p>
          <p className="text-xs text-muted-foreground/70 mt-1">Safety First. Always.</p>
        </div>
      </div>
    </div>
  );
}

// Authentication check component
function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const token = localStorage.getItem('auth_token');
  
  if (!token) {
    // Redirect to login if no token
    window.location.href = '/login';
    return null;
  }
  
  return <>{children}</>;
}

// Root App component
function App() {
  const getCurrentPath = window.location.pathname;
  const token = localStorage.getItem('auth_token');
  
  // Redirect to login if accessing protected route without token
  if (getCurrentPath !== '/login' && !token) {
    window.location.href = '/login';
  }
  
  return (
    <ThemeProvider>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route 
              path="/dashboard" 
              element={
                <ProtectedRoute>
                  <Dashboard />
                </ProtectedRoute>
              } 
            />
            <Route path="/" element={<LoginPage />} />
            <Route 
              path="*" 
              element={
                token ? (
                  <ProtectedRoute>
                    <Dashboard />
                  </ProtectedRoute>
                ) : (
                  <LoginPage />
                )
              } 
            />
          </Routes>
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
