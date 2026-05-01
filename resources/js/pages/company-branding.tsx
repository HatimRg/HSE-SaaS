import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Palette,
  Upload,
  Download,
  Eye,
  RotateCcw,
  Save,
  Image,
  Settings,
  Menu,
  Plus,
  Trash2,
  Edit,
  Check,
  X,
  Moon,
  Sun,
  Zap,
} from 'lucide-react';
import { api } from '../lib/api';
import toast from 'react-hot-toast';

export default function CompanyBrandingPage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<'colors' | 'logo' | 'navigation' | 'preview'>('colors');
  const [previewMode, setPreviewMode] = useState<'light' | 'dark'>('light');

  // Fetch current branding
  const { data: branding, isLoading } = useQuery({
    queryKey: ['company-branding'],
    queryFn: async () => {
      const response = await api.get('/company-branding');
      return response.data.data;
    },
  });

  // Update branding mutation
  const updateBranding = useMutation({
    mutationFn: async (data: FormData) => {
      const response = await api.post('/company-branding', data, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-branding'] });
      toast.success('Branding updated successfully');
    },
    onError: () => {
      toast.error('Failed to update branding');
    },
  });

  const [formData, setFormData] = useState<{
    primary_color: string;
    background_color: string;
    accent_color: string;
    primary_color_dark: string;
    background_color_dark: string;
    accent_color_dark: string;
    custom_css: string;
    navigation_items: any[];
  }>({
    primary_color: '#2563eb',
    background_color: '#ffffff',
    accent_color: '#10b981',
    primary_color_dark: '#3b82f6',
    background_color_dark: '#111827',
    accent_color_dark: '#34d399',
    custom_css: '',
    navigation_items: [],
  });

  // Update form when branding data loads
  useState(() => {
    if (branding) {
      setFormData({
        primary_color: branding.primary_color || '#2563eb',
        background_color: branding.background_color || '#ffffff',
        accent_color: branding.accent_color || '#10b981',
        primary_color_dark: branding.primary_color_dark || '#3b82f6',
        background_color_dark: branding.background_color_dark || '#111827',
        accent_color_dark: branding.accent_color_dark || '#34d399',
        custom_css: branding.custom_css || '',
        navigation_items: branding.navigation_items || [],
      });
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    const data = new FormData();
    Object.entries(formData).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        data.append(key, JSON.stringify(value));
      } else {
        data.append(key, value);
      }
    });

    updateBranding.mutate(data);
  };

  const handleColorChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const addNavigationItem = () => {
    setFormData(prev => ({
      ...prev,
      navigation_items: [
        ...prev.navigation_items,
        {
          label: 'New Item',
          route: '/new',
          icon: 'Circle',
          order: prev.navigation_items.length + 1,
          enabled: true,
        },
      ],
    }));
  };

  const updateNavigationItem = (index: number, field: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      navigation_items: prev.navigation_items.map((item, i) =>
        i === index ? { ...item, [field]: value } : item
      ),
    }));
  };

  const removeNavigationItem = (index: number) => {
    setFormData(prev => ({
      ...prev,
      navigation_items: prev.navigation_items.filter((_, i) => i !== index),
    }));
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="sticky top-0 z-50 bg-background/95 backdrop-blur-sm border-b border-border"
      >
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-foreground">Company Branding</h1>
              <p className="text-muted-foreground">
                Customize your company's appearance and navigation
              </p>
            </div>
            
            <div className="flex items-center gap-4">
              <div className="flex rounded-lg border border-border">
                <button
                  onClick={() => setPreviewMode('light')}
                  className={`px-3 py-2 rounded-l-lg ${previewMode === 'light' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <Sun className="h-4 w-4" />
                </button>
                <button
                  onClick={() => setPreviewMode('dark')}
                  className={`px-3 py-2 rounded-r-lg ${previewMode === 'dark' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <Moon className="h-4 w-4" />
                </button>
              </div>
              
              <button
                onClick={() => window.open('/preview', '_blank')}
                className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted"
              >
                <Eye className="h-4 w-4" />
                Preview
              </button>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Navigation Tabs */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="border-b border-border"
      >
        <div className="px-6">
          <nav className="flex space-x-8">
            {[
              { key: 'colors', label: 'Colors', icon: Palette },
              { key: 'logo', label: 'Logo & Images', icon: Image },
              { key: 'navigation', label: 'Navigation', icon: Menu },
              { key: 'preview', label: 'Preview', icon: Eye },
            ].map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key as any)}
                className={`flex items-center gap-2 py-4 border-b-2 transition-colors ${
                  activeTab === tab.key
                    ? 'border-primary text-primary'
                    : 'border-transparent text-muted-foreground hover:text-foreground'
                }`}
              >
                <tab.icon className="h-4 w-4" />
                {tab.label}
              </button>
            ))}
          </nav>
        </div>
      </motion.div>

      {/* Main Content */}
      <div className="p-6">
        <div className="max-w-4xl mx-auto">
          <form onSubmit={handleSubmit}>
            {activeTab === 'colors' && <ColorsTab formData={formData} onChange={handleColorChange} />}
            {activeTab === 'logo' && <LogoTab />}
            {activeTab === 'navigation' && (
              <NavigationTab
                items={formData.navigation_items}
                onAdd={addNavigationItem}
                onUpdate={updateNavigationItem}
                onRemove={removeNavigationItem}
              />
            )}
            {activeTab === 'preview' && <PreviewTab branding={formData} mode={previewMode} />}

            {/* Action Buttons */}
            <div className="flex items-center justify-between mt-8 pt-6 border-t border-border">
              <div className="flex gap-2">
                <button
                  type="button"
                  className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted"
                >
                  <RotateCcw className="h-4 w-4" />
                  Reset to Default
                </button>
                <button
                  type="button"
                  className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted"
                >
                  <Download className="h-4 w-4" />
                  Export
                </button>
              </div>
              
              <button
                type="submit"
                disabled={updateBranding.isPending}
                className="flex items-center gap-2 px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
              >
                <Save className="h-4 w-4" />
                {updateBranding.isPending ? 'Saving...' : 'Save Changes'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}

// Colors Tab Component
function ColorsTab({ formData, onChange }: { formData: any; onChange: (field: string, value: string) => void }) {
  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-xl font-semibold mb-6">Color Scheme</h2>
        
        <div className="grid gap-6 md:grid-cols-2">
          {/* Light Theme */}
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <Sun className="h-5 w-5 text-primary" />
              <h3 className="font-medium">Light Theme</h3>
            </div>
            
            {[
              { label: 'Primary Color', field: 'primary_color', description: 'Main brand color' },
              { label: 'Background Color', field: 'background_color', description: 'Page background' },
              { label: 'Accent Color', field: 'accent_color', description: 'Highlights and CTAs' },
            ].map((color) => (
              <div key={color.field} className="space-y-2">
                <label className="text-sm font-medium">{color.label}</label>
                <div className="flex items-center gap-3">
                  <input
                    type="color"
                    value={formData[color.field]}
                    onChange={(e) => onChange(color.field, e.target.value)}
                    className="w-12 h-12 rounded border border-border cursor-pointer"
                  />
                  <input
                    type="text"
                    value={formData[color.field]}
                    onChange={(e) => onChange(color.field, e.target.value)}
                    className="flex-1 px-3 py-2 border border-border rounded-lg font-mono text-sm"
                    placeholder="#000000"
                  />
                </div>
                <p className="text-xs text-muted-foreground">{color.description}</p>
              </div>
            ))}
          </div>

          {/* Dark Theme */}
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <Moon className="h-5 w-5 text-primary" />
              <h3 className="font-medium">Dark Theme</h3>
            </div>
            
            {[
              { label: 'Primary Color', field: 'primary_color_dark', description: 'Main brand color' },
              { label: 'Background Color', field: 'background_color_dark', description: 'Page background' },
              { label: 'Accent Color', field: 'accent_color_dark', description: 'Highlights and CTAs' },
            ].map((color) => (
              <div key={color.field} className="space-y-2">
                <label className="text-sm font-medium">{color.label}</label>
                <div className="flex items-center gap-3">
                  <input
                    type="color"
                    value={formData[color.field]}
                    onChange={(e) => onChange(color.field, e.target.value)}
                    className="w-12 h-12 rounded border border-border cursor-pointer"
                  />
                  <input
                    type="text"
                    value={formData[color.field]}
                    onChange={(e) => onChange(color.field, e.target.value)}
                    className="flex-1 px-3 py-2 border border-border rounded-lg font-mono text-sm"
                    placeholder="#000000"
                  />
                </div>
                <p className="text-xs text-muted-foreground">{color.description}</p>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Custom CSS */}
      <div>
        <h3 className="font-medium mb-3">Custom CSS</h3>
        <textarea
          value={formData.custom_css}
          onChange={(e) => onChange('custom_css', e.target.value)}
          className="w-full h-32 px-3 py-2 border border-border rounded-lg font-mono text-sm"
          placeholder="/* Add custom CSS here */"
        />
        <p className="text-xs text-muted-foreground mt-2">
          Add custom CSS to override default styles. Use with caution.
        </p>
      </div>
    </div>
  );
}

// Logo Tab Component
function LogoTab() {
  const [logoLight, setLogoLight] = useState<File | null>(null);
  const [logoDark, setLogoDark] = useState<File | null>(null);
  const [favicon, setFavicon] = useState<File | null>(null);

  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-xl font-semibold mb-6">Logo & Images</h2>
        
        <div className="grid gap-6 md:grid-cols-3">
          {/* Light Logo */}
          <div className="space-y-3">
            <label className="text-sm font-medium">Light Theme Logo</label>
            <div className="border-2 border-dashed border-border rounded-lg p-6 text-center">
              <Upload className="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
              <p className="text-sm text-muted-foreground mb-2">
                Upload logo for light theme
              </p>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => setLogoLight(e.target.files?.[0] || null)}
                className="hidden"
                id="logo-light"
              />
              <label
                htmlFor="logo-light"
                className="inline-flex items-center gap-2 px-3 py-1 text-sm bg-primary text-primary-foreground rounded-lg cursor-pointer hover:bg-primary/90"
              >
                Choose File
              </label>
            </div>
            {logoLight && (
              <div className="text-sm">
                <p className="font-medium">{logoLight.name}</p>
                <p className="text-muted-foreground">{(logoLight.size / 1024).toFixed(1)} KB</p>
              </div>
            )}
          </div>

          {/* Dark Logo */}
          <div className="space-y-3">
            <label className="text-sm font-medium">Dark Theme Logo</label>
            <div className="border-2 border-dashed border-border rounded-lg p-6 text-center">
              <Upload className="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
              <p className="text-sm text-muted-foreground mb-2">
                Upload logo for dark theme
              </p>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => setLogoDark(e.target.files?.[0] || null)}
                className="hidden"
                id="logo-dark"
              />
              <label
                htmlFor="logo-dark"
                className="inline-flex items-center gap-2 px-3 py-1 text-sm bg-primary text-primary-foreground rounded-lg cursor-pointer hover:bg-primary/90"
              >
                Choose File
              </label>
            </div>
            {logoDark && (
              <div className="text-sm">
                <p className="font-medium">{logoDark.name}</p>
                <p className="text-muted-foreground">{(logoDark.size / 1024).toFixed(1)} KB</p>
              </div>
            )}
          </div>

          {/* Favicon */}
          <div className="space-y-3">
            <label className="text-sm font-medium">Favicon</label>
            <div className="border-2 border-dashed border-border rounded-lg p-6 text-center">
              <Upload className="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
              <p className="text-sm text-muted-foreground mb-2">
                Upload favicon (32x32px recommended)
              </p>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => setFavicon(e.target.files?.[0] || null)}
                className="hidden"
                id="favicon"
              />
              <label
                htmlFor="favicon"
                className="inline-flex items-center gap-2 px-3 py-1 text-sm bg-primary text-primary-foreground rounded-lg cursor-pointer hover:bg-primary/90"
              >
                Choose File
              </label>
            </div>
            {favicon && (
              <div className="text-sm">
                <p className="font-medium">{favicon.name}</p>
                <p className="text-muted-foreground">{(favicon.size / 1024).toFixed(1)} KB</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

// Navigation Tab Component
function NavigationTab({
  items,
  onAdd,
  onUpdate,
  onRemove,
}: {
  items: any[];
  onAdd: () => void;
  onUpdate: (index: number, field: string, value: any) => void;
  onRemove: (index: number) => void;
}) {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Navigation Menu</h2>
        <button
          onClick={onAdd}
          className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
        >
          <Plus className="h-4 w-4" />
          Add Item
        </button>
      </div>

      <div className="space-y-3">
        {items.map((item, index) => (
          <div key={index} className="border border-border rounded-lg p-4">
            <div className="grid gap-4 md:grid-cols-5">
              <input
                type="text"
                value={item.label}
                onChange={(e) => onUpdate(index, 'label', e.target.value)}
                placeholder="Label"
                className="px-3 py-2 border border-border rounded-lg"
              />
              <input
                type="text"
                value={item.route}
                onChange={(e) => onUpdate(index, 'route', e.target.value)}
                placeholder="Route"
                className="px-3 py-2 border border-border rounded-lg"
              />
              <input
                type="text"
                value={item.icon}
                onChange={(e) => onUpdate(index, 'icon', e.target.value)}
                placeholder="Icon name"
                className="px-3 py-2 border border-border rounded-lg"
              />
              <input
                type="number"
                value={item.order}
                onChange={(e) => onUpdate(index, 'order', parseInt(e.target.value))}
                placeholder="Order"
                className="px-3 py-2 border border-border rounded-lg"
              />
              <div className="flex items-center gap-2">
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={item.enabled}
                    onChange={(e) => onUpdate(index, 'enabled', e.target.checked)}
                    className="rounded"
                  />
                  <span className="text-sm">Enabled</span>
                </label>
                <button
                  onClick={() => onRemove(index)}
                  className="p-1 text-danger hover:bg-danger/10 rounded"
                >
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

// Preview Tab Component
function PreviewTab({ branding, mode }: { branding: any; mode: 'light' | 'dark' }) {
  const colors = mode === 'light' ? {
    primary: branding.primary_color,
    background: branding.background_color,
    accent: branding.accent_color,
  } : {
    primary: branding.primary_color_dark,
    background: branding.background_color_dark,
    accent: branding.accent_color_dark,
  };

  return (
    <div className="space-y-6">
      <h2 className="text-xl font-semibold">Preview</h2>
      
      <div
        className="border border-border rounded-lg p-6"
        style={{ backgroundColor: colors.background }}
      >
        {/* Header Preview */}
        <div className="flex items-center justify-between mb-6 pb-4 border-b" style={{ borderColor: colors.primary + '20' }}>
          <div className="flex items-center gap-3">
            <div
              className="w-8 h-8 rounded-lg flex items-center justify-center"
              style={{ backgroundColor: colors.primary }}
            >
              <Zap className="h-4 w-4 text-white" />
            </div>
            <span className="font-semibold" style={{ color: colors.primary }}>
              Your Company
            </span>
          </div>
          <nav className="flex gap-4">
            {['Dashboard', 'Projects', 'Reports', 'Settings'].map((item) => (
              <button
                key={item}
                className="px-3 py-1 rounded-lg text-sm font-medium transition-colors hover:bg-opacity-10"
                style={{
                  color: colors.primary,
                  backgroundColor: item === 'Dashboard' ? colors.primary + '20' : 'transparent',
                }}
              >
                {item}
              </button>
            ))}
          </nav>
        </div>

        {/* Content Preview */}
        <div className="space-y-4">
          <div
            className="p-4 rounded-lg"
            style={{ backgroundColor: colors.primary + '10', border: `1px solid ${colors.primary}20` }}
          >
            <h3 className="font-semibold mb-2" style={{ color: colors.primary }}>
              Sample Card
            </h3>
            <p className="text-sm" style={{ color: colors.primary + '80' }}>
              This is how your content will appear with the selected colors.
            </p>
            <button
              className="mt-3 px-4 py-2 rounded-lg text-white text-sm font-medium"
              style={{ backgroundColor: colors.accent }}
            >
              Action Button
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
