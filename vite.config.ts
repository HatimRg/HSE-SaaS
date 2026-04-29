import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      refresh: true,
    }),
    react(),
  ],
  resolve: {
    alias: {
      '@': './resources/js',
      '@components': './resources/js/components',
      '@hooks': './resources/js/hooks',
      '@stores': './resources/js/stores',
      '@lib': './resources/js/lib',
      '@types': './resources/js/types',
      '@api': './resources/js/api',
    },
  },
  build: {
    outDir: 'public/build',
    manifest: 'manifest.json',
    // rollupOptions: {
    //   output: {
    //     manualChunks: {
    //       'react-vendor': ['react', 'react-dom', 'react-router-dom'],
    //       'ui-vendor': ['@headlessui/react', '@heroicons/react', 'lucide-react', 'framer-motion'],
    //       'chart-vendor': ['recharts'],
    //       'form-vendor': ['react-hook-form', 'zod', '@hookform/resolvers'],
    //       'query-vendor': ['@tanstack/react-query', 'axios'],
    //       'i18n-vendor': ['i18next', 'react-i18next', 'i18next-http-backend', 'i18next-browser-languagedetector'],
    //     },
    //   },
    // },
    chunkSizeWarningLimit: 1000,
    cssCodeSplit: true,
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
  server: {
    hmr: {
      overlay: false,
    },
  },
  optimizeDeps: {
    include: [
      'react',
      'react-dom',
      'react-router-dom',
      '@tanstack/react-query',
      'axios',
      'zustand',
      'i18next',
      'react-i18next',
      'framer-motion',
    ],
  },
})
