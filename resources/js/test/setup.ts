import '@testing-library/jest-dom';
import { vi } from 'vitest';
import React from 'react';

// Mock react-i18next
vi.mock('react-i18next', () => ({
  useTranslation: () => ({
    t: (key: string, fallback?: string) => fallback || key,
    i18n: { language: 'en', changeLanguage: vi.fn() },
  }),
  initReactI18next: { type: '3rdParty', init: vi.fn() },
  I18nextProvider: ({ children }: any) => children,
  Trans: ({ children }: any) => children,
  withTranslation: () => (Component: any) => Component,
}));

// Mock @tanstack/react-query
vi.mock('@tanstack/react-query', async () => {
  const actual = await vi.importActual('@tanstack/react-query');
  return {
    ...actual,
    useQuery: () => ({ data: null, isLoading: false, error: null, refetch: vi.fn() }),
    useMutation: () => ({ mutate: vi.fn(), isPending: false, isSuccess: false }),
    useQueryClient: () => ({ invalidateQueries: vi.fn() }),
  };
});

// Mock framer-motion
vi.mock('framer-motion', () => ({
  motion: new Proxy({}, {
    get: (_target, prop) => {
      // Return a component that renders the appropriate HTML element
      return (props: any) => {
        const { children, initial, animate, exit, transition, whileHover, whileTap, variants, layoutId, ...rest } = props || {};
        return React.createElement(String(prop), rest, children);
      };
    }
  }),
  AnimatePresence: ({ children }: any) => children,
  AnimateSharedLayout: ({ children }: any) => children,
  LayoutGroup: ({ children }: any) => children,
}));

// Mock react-hot-toast
vi.mock('react-hot-toast', () => ({
  default: { success: vi.fn(), error: vi.fn(), loading: vi.fn() },
  Toaster: () => null,
}));

// Mock auth provider
vi.mock('../components/auth-provider', () => ({
  useAuth: () => ({
    user: {
      id: 1,
      name: 'Test User',
      email: 'test@demo.com',
      role: { display_name: 'Admin', name: 'admin' },
      company: { name: 'Test Corp' },
      project_access: { type: 'all' },
    },
    login: vi.fn(() => Promise.resolve(true)),
    logout: vi.fn(),
  }),
}));

// Mock theme provider
vi.mock('../components/theme-provider', () => ({
  useTheme: () => ({ theme: 'light', isDark: false, toggle: vi.fn(), setTheme: vi.fn() }),
}));

// Mock API
vi.mock('../lib/api', () => ({
  api: {
    get: vi.fn(() => Promise.resolve({ data: { data: [] } })),
    post: vi.fn(() => Promise.resolve({ data: { data: {} } })),
    put: vi.fn(() => Promise.resolve({ data: { data: {} } })),
    delete: vi.fn(() => Promise.resolve({ data: {} })),
    patch: vi.fn(() => Promise.resolve({ data: { data: {} } })),
  },
}));

// Mock react-router-dom
const mockNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return {
    ...actual,
    useNavigate: () => mockNavigate,
    Link: (props: any) => React.createElement('a', props),
  };
});

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock IntersectionObserver
class MockIntersectionObserver {
  observe = vi.fn();
  unobserve = vi.fn();
  disconnect = vi.fn();
}
Object.defineProperty(window, 'IntersectionObserver', {
  writable: true,
  value: MockIntersectionObserver,
});
