import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Bell,
  Search,
  Sun,
  Moon,
  User,
  LogOut,
  Settings,
  Bug,
  Languages,
  Check,
  X,
} from 'lucide-react';
import { useTheme } from './theme-provider';
import { useAuth } from './auth-provider';

export function TopBar() {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const { isDark, toggle } = useTheme();
  const { user, logout, notifications } = useAuth();
  const [showNotifications, setShowNotifications] = useState(false);
  const [showProfile, setShowProfile] = useState(false);
  const [showLanguage, setShowLanguage] = useState(false);
  const [showBugReport, setShowBugReport] = useState(false);

  const handleLanguageChange = (lang: string) => {
    i18n.changeLanguage(lang);
    setShowLanguage(false);
  };

  const handleLogout = async () => {
    await logout();
  };

  return (
    <header className="flex h-16 items-center justify-between border-b border-border bg-card px-4 lg:px-6">
      {/* Search */}
      <div className="flex items-center gap-4 flex-1">
        <div className="relative max-w-md flex-1 hidden md:block">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder={t('common.search')}
            className="h-10 w-full rounded-lg border border-input bg-background pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>

      {/* Right side actions */}
      <div className="flex items-center gap-2">
        {/* Language Switcher */}
        <div className="relative">
          <button
            onClick={() => setShowLanguage(!showLanguage)}
            className="flex h-10 w-10 items-center justify-center rounded-lg hover:bg-muted transition-colors"
            title={t('common.language')}
          >
            <Languages className="h-5 w-5" />
            <span className="ml-1 text-xs font-semibold uppercase">{i18n.language}</span>
          </button>

          <AnimatePresence>
            {showLanguage && (
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: 10 }}
                className="absolute right-0 top-full mt-2 w-40 rounded-lg border border-border bg-card shadow-lg"
              >
                <button
                  onClick={() => handleLanguageChange('fr')}
                  className="flex w-full items-center gap-2 px-4 py-2 text-sm hover:bg-muted first:rounded-t-lg"
                >
                  <span className="flex-1">Français</span>
                  {i18n.language === 'fr' && <Check className="h-4 w-4" />}
                </button>
                <button
                  onClick={() => handleLanguageChange('en')}
                  className="flex w-full items-center gap-2 px-4 py-2 text-sm hover:bg-muted last:rounded-b-lg"
                >
                  <span className="flex-1">English</span>
                  {i18n.language === 'en' && <Check className="h-4 w-4" />}
                </button>
              </motion.div>
            )}
          </AnimatePresence>
        </div>

        {/* Theme Toggle */}
        <button
          onClick={toggle}
          className="flex h-10 w-10 items-center justify-center rounded-lg hover:bg-muted transition-colors"
          title={isDark ? 'Light mode' : 'Dark mode'}
        >
          <AnimatePresence mode="wait">
            {isDark ? (
              <motion.div
                key="sun"
                initial={{ rotate: -90, opacity: 0 }}
                animate={{ rotate: 0, opacity: 1 }}
                exit={{ rotate: 90, opacity: 0 }}
                transition={{ duration: 0.2 }}
              >
                <Sun className="h-5 w-5" />
              </motion.div>
            ) : (
              <motion.div
                key="moon"
                initial={{ rotate: 90, opacity: 0 }}
                animate={{ rotate: 0, opacity: 1 }}
                exit={{ rotate: -90, opacity: 0 }}
                transition={{ duration: 0.2 }}
              >
                <Moon className="h-5 w-5" />
              </motion.div>
            )}
          </AnimatePresence>
        </button>

        {/* Bug Report */}
        <button
          onClick={() => setShowBugReport(true)}
          className="flex h-10 w-10 items-center justify-center rounded-lg hover:bg-muted transition-colors"
          title="Report a bug"
        >
          <Bug className="h-5 w-5" />
        </button>

        {/* Notifications */}
        <div className="relative">
          <button
            onClick={() => setShowNotifications(!showNotifications)}
            className="relative flex h-10 w-10 items-center justify-center rounded-lg hover:bg-muted transition-colors"
          >
            <Bell className="h-5 w-5" />
            {notifications && notifications.length > 0 && (
              <span className="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground">
                {notifications.length}
              </span>
            )}
          </button>

          <AnimatePresence>
            {showNotifications && (
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: 10 }}
                className="absolute right-0 top-full mt-2 w-80 rounded-lg border border-border bg-card shadow-lg"
              >
                <div className="flex items-center justify-between border-b border-border p-3">
                  <h3 className="font-semibold">{t('notifications.title')}</h3>
                  <button
                    onClick={() => setShowNotifications(false)}
                    className="text-muted-foreground hover:text-foreground"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
                <div className="max-h-64 overflow-y-auto p-2">
                  {notifications && notifications.length > 0 ? (
                    notifications.map((notification: any) => (
                      <div
                        key={notification.id}
                        className="flex items-start gap-3 rounded-lg p-2 hover:bg-muted cursor-pointer"
                      >
                        <div className="flex-1">
                          <p className="text-sm font-medium">{notification.title}</p>
                          <p className="text-xs text-muted-foreground">{notification.message}</p>
                        </div>
                      </div>
                    ))
                  ) : (
                    <p className="p-4 text-center text-sm text-muted-foreground">
                      {t('notifications.noNotifications')}
                    </p>
                  )}
                </div>
              </motion.div>
            )}
          </AnimatePresence>
        </div>

        {/* Profile */}
        <div className="relative">
          <button
            onClick={() => setShowProfile(!showProfile)}
            className="flex items-center gap-2 rounded-lg p-2 hover:bg-muted transition-colors"
          >
            <img
              src={user?.avatar || `https://ui-avatars.com/api/?name=${user?.name}&background=random`}
              alt={user?.name}
              className="h-8 w-8 rounded-full"
            />
            <span className="hidden text-sm font-medium md:block">{user?.name}</span>
          </button>

          <AnimatePresence>
            {showProfile && (
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: 10 }}
                className="absolute right-0 top-full mt-2 w-56 rounded-lg border border-border bg-card shadow-lg"
              >
                <div className="border-b border-border p-3">
                  <p className="font-medium">{user?.name}</p>
                  <p className="text-xs text-muted-foreground">{user?.email}</p>
                  <p className="text-xs text-muted-foreground">{user?.role?.display_name}</p>
                </div>
                <button
                  onClick={() => {
                    navigate('/profile');
                    setShowProfile(false);
                  }}
                  className="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-muted"
                >
                  <User className="h-4 w-4" />
                  {t('common.profile')}
                </button>
                <button
                  onClick={() => {
                    navigate('/settings');
                    setShowProfile(false);
                  }}
                  className="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-muted"
                >
                  <Settings className="h-4 w-4" />
                  {t('common.settings')}
                </button>
                <div className="border-t border-border" />
                <button
                  onClick={handleLogout}
                  className="flex w-full items-center gap-2 px-3 py-2 text-sm text-destructive hover:bg-destructive/10"
                >
                  <LogOut className="h-4 w-4" />
                  {t('common.logout')}
                </button>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      </div>
    </header>
  );
}
