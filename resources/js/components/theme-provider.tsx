import React, { createContext, useContext, useEffect, useState } from 'react';

interface ThemeContextType {
  isDark: boolean;
  toggle: () => void;
  setTheme: (theme: 'light' | 'dark') => void;
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
  const [isDark, setIsDark] = useState(() => {
    if (typeof window === 'undefined') return false;
    const stored = localStorage.getItem('theme');
    if (stored) return stored === 'dark';
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  });

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
    
    if (isDark) {
      root.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      root.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }

    // Apply company colors as CSS variables
    root.style.setProperty('--company-primary-light', colors.primaryLight);
    root.style.setProperty('--company-primary-dark', colors.primaryDark);
    root.style.setProperty('--company-accent', colors.accent);
  }, [isDark, colors]);

  const toggle = () => setIsDark(!isDark);
  const setTheme = (theme: 'light' | 'dark') => setIsDark(theme === 'dark');

  return (
    <ThemeContext.Provider value={{ isDark, toggle, setTheme, colors }}>
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
