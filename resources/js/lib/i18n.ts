import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

// Import French translations
import frCommon from '../locales/fr/common.json';
import frNavigation from '../locales/fr/navigation.json';
import frDashboard from '../locales/fr/dashboard.json';
import frModules from '../locales/fr/modules.json';
import frMessages from '../locales/fr/messages.json';

// Import English translations
import enCommon from '../locales/en/common.json';
import enNavigation from '../locales/en/navigation.json';
import enDashboard from '../locales/en/dashboard.json';
import enModules from '../locales/en/modules.json';
import enMessages from '../locales/en/messages.json';

i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources: {
      fr: {
        common: frCommon,
        navigation: frNavigation,
        dashboard: frDashboard,
        modules: frModules,
        messages: frMessages,
      },
      en: {
        common: enCommon,
        navigation: enNavigation,
        dashboard: enDashboard,
        modules: enModules,
        messages: enMessages,
      },
    },
    fallbackLng: 'fr',
    lng: 'fr',
    debug: false,
    interpolation: {
      escapeValue: false,
    },
    detection: {
      order: ['localStorage', 'navigator', 'htmlTag'],
      caches: ['localStorage'],
    },
    ns: ['common', 'navigation', 'dashboard', 'modules', 'messages'],
    defaultNS: 'common',
  });

export default i18n;
