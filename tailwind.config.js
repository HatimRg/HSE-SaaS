/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: ['class'],
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.{js,ts,jsx,tsx}',
    './resources/js/**/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    container: {
      center: true,
      padding: '2rem',
      screens: {
        '2xl': '1400px',
      },
    },
    extend: {
      colors: {
        // CSS Variable-based colors for dark/light mode support
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
          50: 'hsl(217 91% 95%)',
          100: 'hsl(217 91% 90%)',
          200: 'hsl(217 91% 80%)',
          300: 'hsl(217 91% 70%)',
          400: 'hsl(217 91% 60%)',
          500: 'hsl(217 91% 50%)',
          600: 'hsl(224 76% 48%)',
          700: 'hsl(224 76% 40%)',
          800: 'hsl(224 76% 32%)',
          900: 'hsl(224 76% 25%)',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
        },
        popover: {
          DEFAULT: 'hsl(var(--popover))',
          foreground: 'hsl(var(--popover-foreground))',
        },
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
        // Status colors with high contrast
        success: {
          DEFAULT: 'hsl(142 76% 36%)',
          foreground: 'hsl(210 40% 98%)',
          light: 'hsl(142 76% 95%)',
          dark: 'hsl(142 76% 25%)',
        },
        warning: {
          DEFAULT: 'hsl(38 92% 50%)',
          foreground: 'hsl(222.2 84% 4.9%)',
          light: 'hsl(38 92% 95%)',
          dark: 'hsl(38 92% 35%)',
        },
        info: {
          DEFAULT: 'hsl(217 91% 60%)',
          foreground: 'hsl(210 40% 98%)',
          light: 'hsl(217 91% 95%)',
          dark: 'hsl(217 91% 40%)',
        },
        // SafeSite brand colors
        safesite: {
          primary: 'hsl(var(--safesite-primary))',
          'primary-dark': 'hsl(var(--safesite-primary-dark))',
          success: 'hsl(var(--safesite-success))',
          warning: 'hsl(var(--safesite-warning))',
          danger: 'hsl(var(--safesite-danger))',
        },
      },
      fontFamily: {
        sans: ['Parastoo', 'Inter', 'system-ui', 'sans-serif'],
        login: ['Cinzel', 'Playfair Display', 'serif'],
        app: ['Parastoo', 'Inter', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
      },
      keyframes: {
        'accordion-down': {
          from: { height: '0' },
          to: { height: 'var(--radix-accordion-content-height)' },
        },
        'accordion-up': {
          from: { height: 'var(--radix-accordion-content-height)' },
          to: { height: '0' },
        },
        'fade-in': {
          from: { opacity: '0' },
          to: { opacity: '1' },
        },
        'fade-out': {
          from: { opacity: '1' },
          to: { opacity: '0' },
        },
        'slide-in-from-right': {
          from: { transform: 'translateX(100%)', opacity: '0' },
          to: { transform: 'translateX(0)', opacity: '1' },
        },
        'slide-out-to-right': {
          from: { transform: 'translateX(0)', opacity: '1' },
          to: { transform: 'translateX(100%)', opacity: '0' },
        },
        'slide-in-from-bottom': {
          from: { transform: 'translateY(20px)', opacity: '0' },
          to: { transform: 'translateY(0)', opacity: '1' },
        },
        'slide-in-from-top': {
          from: { transform: 'translateY(-20px)', opacity: '0' },
          to: { transform: 'translateY(0)', opacity: '1' },
        },
        'scale-in': {
          from: { transform: 'scale(0.95)', opacity: '0' },
          to: { transform: 'scale(1)', opacity: '1' },
        },
        'spin-slow': {
          from: { transform: 'rotate(0deg)' },
          to: { transform: 'rotate(360deg)' },
        },
        'pulse-subtle': {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.7' },
        },
        'float': {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        'gradient-shift': {
          '0%, 100%': { backgroundPosition: '0% 50%' },
          '50%': { backgroundPosition: '100% 50%' },
        },
        'reveal-up': {
          from: { transform: 'translateY(30px)', opacity: '0' },
          to: { transform: 'translateY(0)', opacity: '1' },
        },
        'draw-line': {
          from: { strokeDashoffset: '1000' },
          to: { strokeDashoffset: '0' },
        },
      },
      animation: {
        'accordion-down': 'accordion-down 0.2s ease-out',
        'accordion-up': 'accordion-up 0.2s ease-out',
        'fade-in': 'fade-in 0.3s ease-out',
        'fade-out': 'fade-out 0.2s ease-in',
        'slide-in-right': 'slide-in-from-right 0.3s ease-out',
        'slide-out-right': 'slide-out-to-right 0.2s ease-in',
        'slide-in-bottom': 'slide-in-from-bottom 0.3s ease-out',
        'slide-in-top': 'slide-in-from-top 0.3s ease-out',
        'scale-in': 'scale-in 0.2s ease-out',
        'spin-slow': 'spin-slow 3s linear infinite',
        'pulse-subtle': 'pulse-subtle 2s ease-in-out infinite',
        'float': 'float 3s ease-in-out infinite',
        'gradient-shift': 'gradient-shift 8s ease infinite',
        'reveal-up': 'reveal-up 0.6s cubic-bezier(0.165, 0.84, 0.44, 1)',
        'draw-line': 'draw-line 2s ease-out forwards',
      },
      transitionTimingFunction: {
        'spring': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
        'smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',
      },
    },
  },
  plugins: [require('tailwindcss-animate')],
}
