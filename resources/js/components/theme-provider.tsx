import React, { createContext, useContext, useEffect, useState } from 'react';

type ThemeMode = 'light' | 'dark' | 'system';

interface ThemeContextType {
  isDark: boolean;
  theme: ThemeMode;
  toggle: () => void;
  setTheme: (theme: ThemeMode) => void;
  colors: {
    primaryLight: string;
    primaryDark: string;
    backgroundLight: string;
    backgroundDark: string;
    accent: string;
  };
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [theme, setThemeState] = useState<ThemeMode>(() => {
    if (typeof window === 'undefined') return 'system';
    const stored = localStorage.getItem('theme') as ThemeMode | null;
    if (stored && ['light', 'dark', 'system'].includes(stored)) return stored;
    return 'system';
  });

  const [isDark, setIsDark] = useState(false);

  // Company colors (can be fetched from API)
  const [colors] = useState({
    primaryLight: '#3b82f6',
    primaryDark: '#1d4ed8',
    backgroundLight: '#ffffff',
    backgroundDark: '#0f172a',
    accent: '#f59e0b',
  });

  useEffect(() => {
    const root = document.documentElement;

    let shouldBeDark: boolean;
    if (theme === 'system') {
      shouldBeDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    } else {
      shouldBeDark = theme === 'dark';
    }

    setIsDark(shouldBeDark);

    if (shouldBeDark) {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }

    localStorage.setItem('theme', theme);

    // Apply company colors as CSS variables
    root.style.setProperty('--company-primary-light', colors.primaryLight);
    root.style.setProperty('--company-primary-dark', colors.primaryDark);
    root.style.setProperty('--company-accent', colors.accent);
  }, [theme, colors]);

  // Listen for system theme changes when in system mode
  useEffect(() => {
    if (theme !== 'system') return;

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = (e: MediaQueryListEvent) => {
      const root = document.documentElement;
      setIsDark(e.matches);
      if (e.matches) {
        root.classList.add('dark');
      } else {
        root.classList.remove('dark');
      }
    };

    mediaQuery.addEventListener('change', handler);
    return () => mediaQuery.removeEventListener('change', handler);
  }, [theme]);

  const toggle = () => setThemeState(isDark ? 'light' : 'dark');
  const setTheme = (newTheme: ThemeMode) => setThemeState(newTheme);

  return (
    <ThemeContext.Provider value={{ isDark, theme, toggle, setTheme, colors }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within ThemeProvider');
  }
  return context;
}
