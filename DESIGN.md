# HSE SaaS Platform - Design System

## Color Palette (OKLCH)

### Primary: Maritime Blue
```
--color-primary-50: oklch(97% 0.01 250)   /* Lightest tint */
--color-primary-100: oklch(92% 0.02 250)
--color-primary-200: oklch(85% 0.04 250)
--color-primary-300: oklch(75% 0.06 250)
--color-primary-400: oklch(65% 0.08 250)
--color-primary-500: oklch(55% 0.10 250)   /* Main primary */
--color-primary-600: oklch(48% 0.09 250)
--color-primary-700: oklch(40% 0.08 250)
--color-primary-800: oklch(32% 0.07 250)
--color-primary-900: oklch(25% 0.06 250)   /* Darkest */
```

### Neutrals: Warm Tinted
```
--color-neutral-50: oklch(98% 0.005 60)   /* Warm white */
--color-neutral-100: oklch(95% 0.01 60)
--color-neutral-200: oklch(88% 0.015 60)
--color-neutral-300: oklch(75% 0.02 60)
--color-neutral-400: oklch(60% 0.02 60)
--color-neutral-500: oklch(50% 0.02 60)
--color-neutral-600: oklch(40% 0.02 60)
--color-neutral-700: oklch(30% 0.02 60)
--color-neutral-800: oklch(20% 0.015 60)
--color-neutral-900: oklch(12% 0.01 60)    /* Warm black */
```

### Semantic Colors
```
--color-success: oklch(65% 0.15 145)        /* Confident green */
--color-warning: oklch(75% 0.12 85)        /* Amber, not yellow */
--color-danger: oklch(55% 0.18 25)         /* Deep red */
--color-info: oklch(70% 0.08 250)          /* Tinted toward primary */
```

## Typography

### Font Stack
```
--font-sans: "Inter", system-ui, -apple-system, sans-serif;
--font-mono: "JetBrains Mono", "Fira Code", monospace;
```

### Scale
```
--text-xs: 0.75rem;    /* 12px - labels, timestamps */
--text-sm: 0.875rem;   /* 14px - secondary text */
--text-base: 1rem;     /* 16px - body */
--text-lg: 1.125rem;   /* 18px - lead paragraphs */
--text-xl: 1.25rem;    /* 20px - small headings */
--text-2xl: 1.5rem;    /* 24px - section headings */
--text-3xl: 1.875rem;  /* 30px - page titles */
--text-4xl: 2.25rem;   /* 36px - hero headings */
```

### Line Length
- Max body width: 65ch
- Dashboard cards: 45ch max for readability

## Spacing Rhythm

### Base Unit: 4px
```
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-3: 0.75rem;   /* 12px */
--space-4: 1rem;      /* 16px */
--space-5: 1.25rem;   /* 20px */
--space-6: 1.5rem;    /* 24px */
--space-8: 2rem;      /* 32px */
--space-10: 2.5rem;   /* 40px */
--space-12: 3rem;     /* 48px */
--space-16: 4rem;     /* 64px */
--space-20: 5rem;     /* 80px */
```

### Section Spacing
- Dashboard sections: 24px (space-6)
- Card padding: 20px (space-5)
- Form fields: 16px (space-4)
- Compact lists: 12px (space-3)

## Components

### Cards
- Border radius: 12px (rounded-xl)
- Shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03)
- Hover shadow: 0 4px 6px -1px rgba(0,0,0,0.08)
- Border: 1px solid oklch(88% 0.015 60)

### Buttons

#### Primary
- Background: oklch(55% 0.10 250)
- Text: oklch(98% 0.005 60)
- Padding: 10px 20px
- Border radius: 8px
- Hover: oklch(48% 0.09 250), translateY(-1px)
- Active: oklch(40% 0.08 250)

#### Secondary
- Background: transparent
- Border: 1px solid oklch(75% 0.02 60)
- Text: oklch(40% 0.02 60)
- Hover: Background oklch(95% 0.01 60)

### Tables
- Header background: oklch(95% 0.01 60)
- Row hover: oklch(97% 0.005 60)
- Border color: oklch(88% 0.015 60)
- Row height: 52px for comfortable touch targets

### Status Badges

```css
.badge-success {
  background: oklch(95% 0.03 145);
  color: oklch(40% 0.10 145);
  border: 1px solid oklch(85% 0.05 145);
}

.badge-warning {
  background: oklch(95% 0.04 85);
  color: oklch(45% 0.12 85);
  border: 1px solid oklch(85% 0.06 85);
}

.badge-danger {
  background: oklch(92% 0.05 25);
  color: oklch(45% 0.15 25);
  border: 1px solid oklch(80% 0.08 25);
}
```

## Layout Principles

### Dashboard Grid
```
Main content area: fluid, max-width 1600px
Sidebar: 280px fixed (desktop), drawer on mobile
Top bar: 64px fixed height
Content padding: 24px
```

### Breakpoints
```
sm: 640px   /* Mobile landscape */
md: 768px   /* Tablet */
lg: 1024px  /* Desktop */
xl: 1280px  /* Large desktop */
2xl: 1536px /* Extra large */
```

## Animation

### Timing
```
--duration-fast: 150ms;    /* Micro-interactions */
--duration-normal: 250ms; /* UI transitions */
--duration-slow: 350ms;   /* Page transitions */
```

### Easing
```
--ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
--ease-out-quart: cubic-bezier(0.165, 0.84, 0.44, 1);
```

### Rules
- No layout property animations (width, height, top, left)
- Use transform and opacity only
- Hover effects: 150ms
- Page transitions: 350ms
- Stagger list items by 25ms

## Landing Page Specific

### Hero Section
- Full viewport height (min 600px, max 900px)
- Background: Subtle gradient from oklch(98% 0.005 60) to oklch(95% 0.01 250)
- No stock photos of construction workers
- Abstract geometric shapes suggesting structure/safety
- Single, confident CTA button

### Feature Sections
- Alternating layout (image left/text right, then reverse)
- Use actual UI screenshots, not illustrations
- Real data in screenshots (anonymized)

### Trust Indicators
- Client logos in grayscale, color on hover
- Testimonials with real names and titles
- Certification badges (ISO, security standards)

---

*Last updated: April 28, 2026*
