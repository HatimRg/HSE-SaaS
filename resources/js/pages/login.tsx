import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import { Eye, EyeOff, Shield, Lock, Sun, Moon, Languages } from 'lucide-react';
import { useAuth } from '../components/auth-provider';
import { useTheme } from '../components/theme-provider';

export default function LoginPage() {
  const { t, i18n } = useTranslation();
  const { login } = useAuth();
  const { isDark, toggle } = useTheme();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [remember, setRemember] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [showLangMenu, setShowLangMenu] = useState(false);

  const handleLanguageChange = (lang: string) => {
    i18n.changeLanguage(lang);
    setShowLangMenu(false);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      await login(email, password, remember);
    } catch (err: any) {
      setError(err.message || t('errors.unauthorized'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className={`flex min-h-screen items-center justify-center p-4 relative overflow-hidden transition-colors duration-300 ${isDark ? 'bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950' : 'bg-gradient-to-br from-slate-100 via-slate-200 to-slate-100'}`}>
      
      {/* Top Right Controls */}
      <div className="absolute top-4 right-4 flex items-center gap-2 z-50">
        {/* Theme Toggle */}
        <button
          onClick={toggle}
          className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 text-foreground hover:bg-white/20 transition-all"
          title={isDark ? t('common.lightMode') : t('common.darkMode')}
        >
          <AnimatePresence mode="wait" initial={false}>
            {isDark ? (
              <motion.div
                key="sun"
                initial={{ rotate: -90, opacity: 0 }}
                animate={{ rotate: 0, opacity: 1 }}
                exit={{ rotate: 90, opacity: 0 }}
                transition={{ duration: 0.2 }}
              >
                <Sun className="h-5 w-5 text-yellow-300" />
              </motion.div>
            ) : (
              <motion.div
                key="moon"
                initial={{ rotate: 90, opacity: 0 }}
                animate={{ rotate: 0, opacity: 1 }}
                exit={{ rotate: -90, opacity: 0 }}
                transition={{ duration: 0.2 }}
              >
                <Moon className="h-5 w-5 text-slate-600" />
              </motion.div>
            )}
          </AnimatePresence>
        </button>

        {/* Language Toggle */}
        <div className="relative">
          <button
            onClick={() => setShowLangMenu(!showLangMenu)}
            className="flex h-10 items-center gap-1.5 rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 px-3 text-foreground hover:bg-white/20 transition-all"
            title="Switch language"
          >
            <Languages className="h-5 w-5" />
            <span className="text-sm font-medium">{i18n.language.toUpperCase()}</span>
          </button>
          
          <AnimatePresence>
            {showLangMenu && (
              <motion.div
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -10 }}
                className="absolute right-0 top-full mt-2 rounded-lg border border-border bg-card shadow-lg overflow-hidden min-w-[120px]"
              >
                <button
                  onClick={() => handleLanguageChange('fr')}
                  className={`w-full px-4 py-2 text-left text-sm hover:bg-muted transition-colors ${i18n.language === 'fr' ? 'bg-primary/10 text-primary font-medium' : ''}`}
                >
                  Français (FR)
                </button>
                <button
                  onClick={() => handleLanguageChange('en')}
                  className={`w-full px-4 py-2 text-left text-sm hover:bg-muted transition-colors ${i18n.language === 'en' ? 'bg-primary/10 text-primary font-medium' : ''}`}
                >
                  English (EN)
                </button>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      </div>
      
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
        
        {/* Small Floating Dots */}
        <div className="absolute top-[25%] left-[35%] w-2 h-2 bg-primary/40 rounded-full animate-pulse"></div>
        <div className="absolute top-[45%] right-[30%] w-3 h-3 bg-success/40 rounded-full animate-pulse"></div>
        <div className="absolute bottom-[40%] left-[45%] w-2 h-2 bg-warning/40 rounded-full animate-pulse"></div>
      </div>

      {/* Grid Pattern */}
      <div className="absolute inset-0 opacity-[0.03]" style={{
        backgroundImage: 'linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px)',
        backgroundSize: '60px 60px'
      }}></div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md relative z-10"
      >
        {/* Logo & Title with Fancy Font */}
        <div className="mb-8 text-center">
          <motion.div
            initial={{ scale: 0.8 }}
            animate={{ scale: 1 }}
            transition={{ type: 'spring', stiffness: 200, delay: 0.2 }}
            className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg"
          >
            <Shield className="h-8 w-8" />
          </motion.div>
          <h1 className="text-4xl font-bold font-login tracking-wide text-white">{t('common.appName')}</h1>
          <p className="mt-2 text-sm text-muted-foreground font-login tracking-wider uppercase">
            Health • Safety • Environment • Quality
          </p>
        </div>

        {/* Login Form */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="rounded-2xl border border-border bg-card/90 backdrop-blur-xl p-6 shadow-lg"
        >
          <h2 className="mb-6 text-center text-lg font-semibold font-login">
            {t('common.login')}
          </h2>

          {error && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
              className="mb-4 rounded-lg bg-destructive/10 p-3 text-sm text-destructive"
            >
              {error}
            </motion.div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="mb-1.5 block text-sm font-medium text-foreground">
                {t('common.email')}
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                className="h-11 w-full rounded-lg border border-input bg-background px-3 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-foreground placeholder:text-muted-foreground"
                placeholder="email@company.com"
              />
            </div>

            <div>
              <label className="mb-1.5 block text-sm font-medium text-foreground">
                {t('common.password')}
              </label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  className="h-11 w-full rounded-lg border border-input bg-background px-3 pr-10 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-foreground placeholder:text-muted-foreground"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  {showPassword ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between">
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={remember}
                  onChange={(e) => setRemember(e.target.checked)}
                  className="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                />
                <span className="text-muted-foreground">{t('common.remember')}</span>
              </label>
              <a
                href="#"
                className="text-sm text-primary hover:text-primary/80 transition-colors"
              >
                {t('common.forgotPassword')}
              </a>
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="btn-shine relative h-11 w-full rounded-lg bg-primary font-medium text-primary-foreground transition-all hover:bg-primary/90 disabled:opacity-50"
            >
              {isLoading ? (
                <span className="flex items-center justify-center gap-2">
                  <motion.div
                    animate={{ rotate: 360 }}
                    transition={{ duration: 1, repeat: Infinity, ease: 'linear' }}
                    className="h-4 w-4 rounded-full border-2 border-white/30 border-t-white"
                  />
                  {t('common.loading')}
                </span>
              ) : (
                <span className="flex items-center justify-center gap-2">
                  <Lock className="h-4 w-4" />
                  {t('common.login')}
                </span>
              )}
            </button>
          </form>

          <div className="mt-4 text-center text-xs text-muted-foreground">
            <p>Demo credentials:</p>
            <p>admin@demo.com / password</p>
            <p>engineer@demo.com / password</p>
          </div>
        </motion.div>

        {/* Footer */}
        <motion.p
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.5 }}
          className="mt-8 text-center text-xs text-muted-foreground"
        >
          © 2024 SafeSite Platform. All rights reserved.
        </motion.p>
      </motion.div>
    </div>
  );
}
