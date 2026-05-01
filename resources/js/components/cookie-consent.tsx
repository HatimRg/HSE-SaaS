import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import { Cookie, X, ChevronDown, ChevronUp, Shield, Eye, Settings } from 'lucide-react';

interface CookieSettings {
  essential: boolean;
  tracking: boolean;
  preferences: boolean;
}

const COOKIE_CONSENT_KEY = 'hse_cookie_consent';
const COOKIE_SETTINGS_KEY = 'hse_cookie_settings';

export function CookieConsent() {
  const { t } = useTranslation('messages');
  const [isVisible, setIsVisible] = useState(false);
  const [showDetails, setShowDetails] = useState(false);
  const [settings, setSettings] = useState<CookieSettings>({
    essential: true,
    tracking: false,
    preferences: false,
  });

  useEffect(() => {
    // Check if user has already consented
    const hasConsented = localStorage.getItem(COOKIE_CONSENT_KEY);
    if (!hasConsented) {
      // Small delay for better UX
      const timer = setTimeout(() => setIsVisible(true), 1500);
      return () => clearTimeout(timer);
    }

    // Load saved settings
    const savedSettings = localStorage.getItem(COOKIE_SETTINGS_KEY);
    if (savedSettings) {
      setSettings(JSON.parse(savedSettings));
    }
  }, []);

  const acceptAll = () => {
    const allSettings = {
      essential: true,
      tracking: true,
      preferences: true,
    };
    setSettings(allSettings);
    localStorage.setItem(COOKIE_CONSENT_KEY, 'true');
    localStorage.setItem(COOKIE_SETTINGS_KEY, JSON.stringify(allSettings));
    setIsVisible(false);
  };

  const acceptSelected = () => {
    localStorage.setItem(COOKIE_CONSENT_KEY, 'true');
    localStorage.setItem(COOKIE_SETTINGS_KEY, JSON.stringify(settings));
    setIsVisible(false);
  };

  const rejectNonEssential = () => {
    const essentialOnly = {
      essential: true,
      tracking: false,
      preferences: false,
    };
    setSettings(essentialOnly);
    localStorage.setItem(COOKIE_CONSENT_KEY, 'true');
    localStorage.setItem(COOKIE_SETTINGS_KEY, JSON.stringify(essentialOnly));
    setIsVisible(false);
  };

  const toggleSetting = (key: keyof CookieSettings) => {
    if (key === 'essential') return; // Cannot disable essential
    setSettings(prev => ({ ...prev, [key]: !prev[key] }));
  };

  return (
    <AnimatePresence>
      {isVisible && (
        <motion.div
          initial={{ y: 100, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          exit={{ y: 100, opacity: 0 }}
          transition={{ type: 'spring', damping: 25, stiffness: 200 }}
          className="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6"
        >
          <div className="mx-auto max-w-4xl">
            <div className="rounded-2xl border border-border bg-card/95 backdrop-blur-md p-4 md:p-6 shadow-2xl">
              {/* Header */}
              <div className="flex items-start justify-between gap-4">
                <div className="flex items-center gap-3">
                  <motion.div
                    animate={{ rotate: [0, 10, -10, 0] }}
                    transition={{ duration: 0.5, delay: 0.3 }}
                    className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10"
                  >
                    <Cookie className="h-5 w-5 text-primary" />
                  </motion.div>
                  <div>
                    <h3 className="font-semibold">{t('cookies.title')}</h3>
                    <p className="text-sm text-muted-foreground">{t('cookies.description')}</p>
                  </div>
                </div>
                <button
                  onClick={() => setIsVisible(false)}
                  className="rounded-full p-1 hover:bg-muted transition-colors"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>

              {/* Expandable Details */}
              <AnimatePresence>
                {showDetails && (
                  <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.3 }}
                    className="overflow-hidden"
                  >
                    <div className="mt-4 space-y-3 border-t border-border pt-4">
                      {/* Essential Cookies */}
                      <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                        <div className="flex items-center gap-3">
                          <Shield className="h-4 w-4 text-green-500" />
                          <div>
                            <p className="font-medium text-sm">Essential</p>
                            <p className="text-xs text-muted-foreground">{t('cookies.types.essential')}</p>
                          </div>
                        </div>
                        <input
                          type="checkbox"
                          checked={settings.essential}
                          disabled
                          className="h-4 w-4 accent-primary cursor-not-allowed"
                        />
                      </div>

                      {/* Tracking Cookies */}
                      <div
                        className="flex items-center justify-between rounded-lg bg-muted/50 p-3 cursor-pointer hover:bg-muted transition-colors"
                        onClick={() => toggleSetting('tracking')}
                      >
                        <div className="flex items-center gap-3">
                          <Eye className="h-4 w-4 text-blue-500" />
                          <div>
                            <p className="font-medium text-sm">Functional</p>
                            <p className="text-xs text-muted-foreground">{t('cookies.types.tracking')}</p>
                          </div>
                        </div>
                        <input
                          type="checkbox"
                          checked={settings.tracking}
                          onChange={() => {}}
                          className="h-4 w-4 accent-primary cursor-pointer"
                        />
                      </div>

                      {/* Preferences Cookies */}
                      <div
                        className="flex items-center justify-between rounded-lg bg-muted/50 p-3 cursor-pointer hover:bg-muted transition-colors"
                        onClick={() => toggleSetting('preferences')}
                      >
                        <div className="flex items-center gap-3">
                          <Settings className="h-4 w-4 text-orange-500" />
                          <div>
                            <p className="font-medium text-sm">Preferences</p>
                            <p className="text-xs text-muted-foreground">{t('cookies.types.preferences')}</p>
                          </div>
                        </div>
                        <input
                          type="checkbox"
                          checked={settings.preferences}
                          onChange={() => {}}
                          className="h-4 w-4 accent-primary cursor-pointer"
                        />
                      </div>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>

              {/* Actions */}
              <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <button
                  onClick={() => setShowDetails(!showDetails)}
                  className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
                >
                  {showDetails ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                  {t('cookies.learnMore')}
                </button>

                <div className="flex flex-wrap items-center gap-2">
                  <button
                    onClick={rejectNonEssential}
                    className="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-all active:scale-95"
                  >
                    {t('cookies.reject')}
                  </button>
                  {showDetails ? (
                    <button
                      onClick={acceptSelected}
                      className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-all active:scale-95"
                    >
                      Save Preferences
                    </button>
                  ) : (
                    <button
                      onClick={acceptAll}
                      className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-all active:scale-95"
                    >
                      {t('cookies.accept')}
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}

// Hook to check if tracking is allowed
export function useCookieConsent() {
  const [settings, setSettings] = useState<CookieSettings | null>(null);

  useEffect(() => {
    const saved = localStorage.getItem(COOKIE_SETTINGS_KEY);
    if (saved) {
      setSettings(JSON.parse(saved));
    }
  }, []);

  const canTrack = settings?.tracking ?? false;
  const canStorePreferences = settings?.preferences ?? false;
  const hasConsent = !!settings;

  return { canTrack, canStorePreferences, hasConsent, settings };
}

// Track user actions (only if consent given)
export function trackAction(action: string, metadata?: Record<string, any>) {
  const settings = localStorage.getItem(COOKIE_SETTINGS_KEY);
  if (!settings) return;

  const parsed = JSON.parse(settings);
  if (!parsed.tracking) return;

  // Store action in localStorage for now (can be sent to backend later)
  const actions = JSON.parse(localStorage.getItem('hse_user_actions') || '[]');
  actions.push({
    action,
    metadata,
    timestamp: new Date().toISOString(),
    url: window.location.pathname,
  });

  // Keep only last 100 actions
  if (actions.length > 100) actions.shift();

  localStorage.setItem('hse_user_actions', JSON.stringify(actions));
}
