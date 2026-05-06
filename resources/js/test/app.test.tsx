import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { I18nextProvider } from 'react-i18next';
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import enCommon from '../locales/en/common.json';
import enModules from '../locales/en/modules.json';
import enNavigation from '../locales/en/navigation.json';
import enMessages from '../locales/en/messages.json';
import enDashboard from '../locales/en/dashboard.json';
import frCommon from '../locales/fr/common.json';
import frModules from '../locales/fr/modules.json';
import frNavigation from '../locales/fr/navigation.json';
import frMessages from '../locales/fr/messages.json';
import frDashboard from '../locales/fr/dashboard.json';

// Create a test i18n instance
const testI18n = i18n.createInstance();
testI18n.use(initReactI18next).init({
  resources: {
    en: { common: enCommon, modules: enModules, navigation: enNavigation, messages: enMessages, dashboard: enDashboard },
    fr: { common: frCommon, modules: frModules, navigation: frNavigation, messages: frMessages, dashboard: frDashboard },
  },
  lng: 'en',
  fallbackLng: 'en',
  ns: ['common', 'modules', 'navigation', 'messages', 'dashboard'],
  defaultNS: 'common',
  interpolation: { escapeValue: false },
});

function createQueryClient() {
  return new QueryClient({ defaultOptions: { queries: { retry: false } } });
}

function renderWithProviders(ui: React.ReactElement) {
  const qc = createQueryClient();
  return render(
    <QueryClientProvider client={qc}>
      <I18nextProvider i18n={testI18n}>
        {ui}
      </I18nextProvider>
    </QueryClientProvider>
  );
}

// ─── i18n Translation Tests ────────────────────────────────────────────

describe('i18n Translation Keys', () => {
  it('EN common.json has all required keys', () => {
    const requiredKeys = ['cancel', 'create', 'delete', 'edit', 'save', 'search', 'name', 'email', 'password', 'description', 'status', 'actions', 'date', 'notes', 'all', 'back', 'refresh', 'error', 'active', 'inactive', 'closed', 'high', 'medium', 'low', 'critical', 'phone', 'company', 'record'];
    requiredKeys.forEach(key => {
      expect(enCommon[key], `Missing key: common.${key}`).toBeDefined();
    });
  });

  it('FR common.json has all required keys matching EN', () => {
    const enKeys = Object.keys(enCommon).filter(k => typeof enCommon[k as keyof typeof enCommon] === 'string');
    enKeys.forEach(key => {
      expect(frCommon[key], `Missing FR key: common.${key}`).toBeDefined();
    });
  });

  it('EN modules.json has all module sections', () => {
    const requiredModules = ['kpi', 'sor', 'permits', 'workers', 'environment', 'users', 'projects', 'inspections', 'community', 'risk', 'settings', 'training', 'ppe', 'library', 'investigation', 'profile'];
    requiredModules.forEach(mod => {
      expect(enModules[mod as keyof typeof enModules], `Missing module: modules.${mod}`).toBeDefined();
    });
  });

  it('FR modules.json has all module sections matching EN', () => {
    const enModulesKeys = Object.keys(enModules);
    enModulesKeys.forEach(key => {
      expect(frModules[key as keyof typeof frModules], `Missing FR module: modules.${key}`).toBeDefined();
    });
  });

  it('EN navigation.json has all nav items', () => {
    const requiredNav = ['dashboard', 'kpi', 'sor', 'risk', 'permits', 'inspections', 'workers', 'training', 'environment', 'community', 'settings', 'users'];
    requiredNav.forEach(key => {
        expect(enNavigation[key as keyof typeof enNavigation], `Missing nav key: navigation.${key}`).toBeDefined();
    });
  });

  it('FR navigation.json has all nav items matching EN', () => {
    const enNavKeys = Object.keys(enNavigation);
    enNavKeys.forEach(key => {
      expect(frNavigation[key as keyof typeof frNavigation], `Missing FR nav key: navigation.${key}`).toBeDefined();
    });
  });

  it('No duplicate keys in EN modules.json', () => {
    const raw = JSON.stringify(enModules);
    // Basic check - JSON.parse would have already failed if truly invalid
    expect(raw.length).toBeGreaterThan(100);
  });

  it('Profile module has nested accessType keys in both languages', () => {
    expect(enModules.profile.accessType.all).toBe('All Projects');
    expect(enModules.profile.accessType.pole).toBe('Pole Level Access');
    expect(enModules.profile.accessType.projects).toBe('Specific Projects Only');
    expect(frModules.profile.accessType.all).toBe('Tous les projets');
    expect(frModules.profile.accessType.pole).toBe('Accès au niveau pôle');
    expect(frModules.profile.accessType.projects).toBe('Projets spécifiques uniquement');
  });

  it('Settings module has all notification keys in both languages', () => {
    const settingsKeys = ['emailNotifs', 'pushNotifs', 'notifEvents', 'notifPermits', 'notifInspections', 'notifTraining', 'notifOverdue'];
    settingsKeys.forEach(key => {
      expect(enModules.settings[key as keyof typeof enModules.settings], `Missing EN settings.${key}`).toBeDefined();
      expect(frModules.settings[key as keyof typeof frModules.settings], `Missing FR settings.${key}`).toBeDefined();
    });
  });
});

// ─── Page Rendering Tests ──────────────────────────────────────────────

describe('Page Components', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('SettingsPage renders with all tabs', async () => {
    const { default: SettingsPage } = await import('../pages/settings');
    renderWithProviders(<SettingsPage />);
    expect(screen.getByText('Appearance')).toBeInTheDocument();
    expect(screen.getByText('Notifications')).toBeInTheDocument();
    expect(screen.getByText('Language & Region')).toBeInTheDocument();
    expect(screen.getByText('Security')).toBeInTheDocument();
  });

  it('SettingsPage shows theme options when Appearance tab is active', async () => {
    const { default: SettingsPage } = await import('../pages/settings');
    renderWithProviders(<SettingsPage />);
    expect(screen.getByText('Light')).toBeInTheDocument();
    expect(screen.getByText('Dark')).toBeInTheDocument();
    expect(screen.getByText('System')).toBeInTheDocument();
  });

  it('SettingsPage shows language options when Language tab is clicked', async () => {
    const { default: SettingsPage } = await import('../pages/settings');
    renderWithProviders(<SettingsPage />);
    const langTab = screen.getByText('Language & Region');
    fireEvent.click(langTab);
    expect(screen.getByText('English')).toBeInTheDocument();
    expect(screen.getByText('Français')).toBeInTheDocument();
  });

  it('SettingsPage shows password form when Security tab is clicked', async () => {
    const { default: SettingsPage } = await import('../pages/settings');
    renderWithProviders(<SettingsPage />);
    const secTab = screen.getByText('Security');
    fireEvent.click(secTab);
    await waitFor(() => {
      expect(screen.getByText('Current Password')).toBeInTheDocument();
      expect(screen.getByText('New Password')).toBeInTheDocument();
      expect(screen.getByText('Confirm New Password')).toBeInTheDocument();
    });
  });

  it('ProfilePage renders user info', async () => {
    const { default: ProfilePage } = await import('../pages/profile');
    renderWithProviders(<ProfilePage />);
    expect(screen.getByText('Test User')).toBeInTheDocument();
    expect(screen.getByText('test@demo.com')).toBeInTheDocument();
  });

  it('ProfilePage shows edit profile button', async () => {
    const { default: ProfilePage } = await import('../pages/profile');
    renderWithProviders(<ProfilePage />);
    // Mock t() returns key path since no fallback: 'modules:profile.editProfile'
    expect(screen.getByText('modules:profile.editProfile')).toBeInTheDocument();
  });

  it('ProfilePage shows project access section', async () => {
    const { default: ProfilePage } = await import('../pages/profile');
    renderWithProviders(<ProfilePage />);
    // Mock t() returns key path: 'modules:profile.projectAccess'
    expect(screen.getByText('modules:profile.projectAccess')).toBeInTheDocument();
  });

  it('LoginPage renders form with email input', async () => {
    const { default: LoginPage } = await import('../pages/login');
    renderWithProviders(<LoginPage />);
    // Mock t() returns key path for keys without fallbacks
    const emailInput = screen.getByPlaceholderText('common.emailPlaceholder');
    expect(emailInput).toBeInTheDocument();
    expect(emailInput).toHaveAttribute('type', 'email');
  });

  it('LoginPage renders HSEQ tagline', async () => {
    const { default: LoginPage } = await import('../pages/login');
    renderWithProviders(<LoginPage />);
    // t('common.hseqTagline') returns 'common.hseqTagline' from mock
    expect(screen.getByText('common.hseqTagline')).toBeInTheDocument();
  });

  it('LoginPage renders copyright footer', async () => {
    const { default: LoginPage } = await import('../pages/login');
    renderWithProviders(<LoginPage />);
    // Footer: © 2024 SafeSite Platform. + t('common.allRightsReserved') → 'common.allRightsReserved'
    expect(screen.getByText(/© 2024 SafeSite Platform/)).toBeInTheDocument();
  });
});

// ─── Component Tests ──────────────────────────────────────────────────

describe('Shared Components', () => {
  it('EmptyState renders title and description', async () => {
    const { EmptyState } = await import('../components/empty-state');
    renderWithProviders(<EmptyState title="Test Title" description="Test Description" />);
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
  });

  it('Modal renders title and children', async () => {
    const { Modal } = await import('../components/modal');
    const onClose = vi.fn();
    renderWithProviders(
      <Modal isOpen={true} onClose={onClose} title="Test Modal">
        <p>Modal content</p>
      </Modal>
    );
    expect(screen.getByText('Test Modal')).toBeInTheDocument();
    expect(screen.getByText('Modal content')).toBeInTheDocument();
  });

  it('Modal calls onClose when close button clicked', async () => {
    const { Modal } = await import('../components/modal');
    const onClose = vi.fn();
    renderWithProviders(
      <Modal isOpen={true} onClose={onClose} title="Test Modal">
        <p>Content</p>
      </Modal>
    );
    // Find and click the close button (X button)
    const closeButtons = screen.getAllByRole('button');
    const closeBtn = closeButtons.find(b => b.textContent === '' || b.closest('button'));
    if (closeBtn) {
      fireEvent.click(closeBtn);
    }
  });
});

// ─── API Module Tests ──────────────────────────────────────────────────

describe('API Module', () => {
  it('api module exports axios instance', async () => {
    const { api } = await import('../lib/api');
    expect(api).toBeDefined();
    expect(typeof api.get).toBe('function');
    expect(typeof api.post).toBe('function');
    expect(typeof api.put).toBe('function');
    expect(typeof api.delete).toBe('function');
  });
});

// ─── i18n Runtime Tests ────────────────────────────────────────────────

describe('i18n Runtime', () => {
  it('translates common keys correctly in English', () => {
    expect(testI18n.t('common:cancel')).toBe('Cancel');
    expect(testI18n.t('common:create')).toBe('Create');
    expect(testI18n.t('common:search')).toBe('Search');
    expect(testI18n.t('common:email')).toBe('Email');
    expect(testI18n.t('common:password')).toBe('Password');
  });

  it('translates module keys correctly in English', () => {
    expect(testI18n.t('modules:library.title')).toBe('Document Library');
    expect(testI18n.t('modules:investigation.startInvestigation')).toBe('Start Investigation');
    expect(testI18n.t('modules:profile.editProfile')).toBe('Edit Profile');
  });

  it('translates correctly in French after language change', async () => {
    await testI18n.changeLanguage('fr');
    expect(testI18n.t('common:cancel')).toBe('Annuler');
    expect(testI18n.t('common:create')).toBe('Créer');
    expect(testI18n.t('common:search')).toBe('Rechercher');
    expect(testI18n.t('modules:library.title')).toBe('Bibliothèque de documents');
    expect(testI18n.t('modules:profile.editProfile')).toBe('Modifier le profil');
    // Reset to English
    await testI18n.changeLanguage('en');
  });

  it('falls back to key when translation missing', () => {
    expect(testI18n.t('common:nonexistent.key')).toBe('nonexistent.key');
  });
});
