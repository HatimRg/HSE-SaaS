import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useMutation } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { Palette, Bell, Globe, Shield, Check, Moon, Sun, Monitor } from 'lucide-react';
import { api } from '../lib/api';
import { useTheme } from '../components/theme-provider';
import { useAuth } from '../components/auth-provider';
import toast from 'react-hot-toast';

type Tab = 'appearance' | 'notifications' | 'language' | 'security';

export default function SettingsPage() {
  const { t, i18n } = useTranslation();
  const { theme, setTheme } = useTheme();
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState<Tab>('appearance');
  const [passwordForm, setPasswordForm] = useState({ current_password: '', new_password: '', new_password_confirmation: '' });
  const [notifSettings, setNotifSettings] = useState({
    email_events: true,
    email_permits: true,
    email_inspections: false,
    email_training: true,
    push_events: true,
    push_permits: false,
    push_overdue: true,
  });

  const changePassword = useMutation({
    mutationFn: async (data: typeof passwordForm) => {
      const r = await api.post('/user/change-password', data);
      return r.data;
    },
    onSuccess: () => {
      toast.success(t('modules:settings.passwordChanged', 'Password changed successfully'));
      setPasswordForm({ current_password: '', new_password: '', new_password_confirmation: '' });
    },
    onError: () => toast.error(t('modules:settings.passwordError', 'Failed to change password')),
  });

  const tabs: { key: Tab; icon: React.ElementType; label: string }[] = [
    { key: 'appearance', icon: Palette, label: t('modules:settings.appearance', 'Appearance') },
    { key: 'notifications', icon: Bell, label: t('modules:settings.notifications', 'Notifications') },
    { key: 'language', icon: Globe, label: t('modules:settings.languageRegion', 'Language & Region') },
    { key: 'security', icon: Shield, label: t('modules:settings.security', 'Security') },
  ];

  const themeOptions = [
    { value: 'light' as const, label: t('modules:settings.light', 'Light'), icon: Sun },
    { value: 'dark' as const, label: t('modules:settings.dark', 'Dark'), icon: Moon },
    { value: 'system' as const, label: t('modules:settings.system', 'System'), icon: Monitor },
  ];

  const languages = [
    { code: 'en', label: 'English', flag: '🇬🇧' },
    { code: 'fr', label: 'Français', flag: '🇫🇷' },
  ];

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <h1 className="text-2xl font-bold">{t('navigation:settings')}</h1>

      {/* Tab Selector */}
      <div className="flex rounded-lg border border-border overflow-hidden w-fit">
        {tabs.map(tab => (
          <button key={tab.key} onClick={() => setActiveTab(tab.key)}
            className={`flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors ${activeTab === tab.key ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'}`}>
            <tab.icon className="h-4 w-4" /> {tab.label}
          </button>
        ))}
      </div>

      {/* Appearance */}
      {activeTab === 'appearance' && (
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="space-y-6">
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="text-sm font-semibold mb-4">{t('modules:settings.theme', 'Theme')}</h3>
            <div className="grid grid-cols-3 gap-3">
              {themeOptions.map(opt => (
                <button key={opt.value} onClick={() => setTheme(opt.value)}
                  className={`flex flex-col items-center gap-2 rounded-lg border p-4 transition-colors ${theme === opt.value ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted'}`}>
                  <opt.icon className={`h-6 w-6 ${theme === opt.value ? 'text-primary' : 'text-muted-foreground'}`} />
                  <span className="text-sm font-medium">{opt.label}</span>
                  {theme === opt.value && <Check className="h-4 w-4 text-primary" />}
                </button>
              ))}
            </div>
          </div>
        </motion.div>
      )}

      {/* Notifications */}
      {activeTab === 'notifications' && (
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="space-y-4">
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="text-sm font-semibold mb-4">{t('modules:settings.emailNotifs', 'Email Notifications')}</h3>
            <div className="space-y-3">
              {[
                { key: 'email_events', label: t('modules:settings.notifEvents', 'Safety events & observations') },
                { key: 'email_permits', label: t('modules:settings.notifPermits', 'Work permit updates') },
                { key: 'email_inspections', label: t('modules:settings.notifInspections', 'Inspection reminders') },
                { key: 'email_training', label: t('modules:settings.notifTraining', 'Training session updates') },
              ].map(item => (
                <label key={item.key} className="flex items-center justify-between py-2">
                  <span className="text-sm">{item.label}</span>
                  <button onClick={() => setNotifSettings(s => ({ ...s, [item.key]: !s[item.key as keyof typeof s] }))}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${notifSettings[item.key as keyof typeof notifSettings] ? 'bg-primary' : 'bg-muted'}`}>
                    <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${notifSettings[item.key as keyof typeof notifSettings] ? 'translate-x-6' : 'translate-x-1'}`} />
                  </button>
                </label>
              ))}
            </div>
          </div>
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="text-sm font-semibold mb-4">{t('modules:settings.pushNotifs', 'Push Notifications')}</h3>
            <div className="space-y-3">
              {[
                { key: 'push_events', label: t('modules:settings.notifEvents', 'Safety events & observations') },
                { key: 'push_permits', label: t('modules:settings.notifPermits', 'Work permit updates') },
                { key: 'push_overdue', label: t('modules:settings.notifOverdue', 'Overdue actions & events') },
              ].map(item => (
                <label key={item.key} className="flex items-center justify-between py-2">
                  <span className="text-sm">{item.label}</span>
                  <button onClick={() => setNotifSettings(s => ({ ...s, [item.key]: !s[item.key as keyof typeof s] }))}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${notifSettings[item.key as keyof typeof notifSettings] ? 'bg-primary' : 'bg-muted'}`}>
                    <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${notifSettings[item.key as keyof typeof notifSettings] ? 'translate-x-6' : 'translate-x-1'}`} />
                  </button>
                </label>
              ))}
            </div>
          </div>
        </motion.div>
      )}

      {/* Language */}
      {activeTab === 'language' && (
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="space-y-4">
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="text-sm font-semibold mb-4">{t('modules:settings.selectLanguage', 'Select Language')}</h3>
            <div className="grid grid-cols-2 gap-3">
              {languages.map(lang => (
                <button key={lang.code} onClick={() => i18n.changeLanguage(lang.code)}
                  className={`flex items-center gap-3 rounded-lg border p-4 transition-colors ${i18n.language === lang.code ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted'}`}>
                  <span className="text-2xl">{lang.flag}</span>
                  <span className="text-sm font-medium">{lang.label}</span>
                  {i18n.language === lang.code && <Check className="ml-auto h-4 w-4 text-primary" />}
                </button>
              ))}
            </div>
          </div>
        </motion.div>
      )}

      {/* Security */}
      {activeTab === 'security' && (
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="space-y-4">
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="text-sm font-semibold mb-4">{t('modules:settings.changePassword', 'Change Password')}</h3>
            <form onSubmit={e => { e.preventDefault(); changePassword.mutate(passwordForm); }} className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">{t('modules:settings.currentPassword', 'Current Password')}</label>
                <input type="password" value={passwordForm.current_password} onChange={e => setPasswordForm(f => ({ ...f, current_password: e.target.value }))} required
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">{t('modules:settings.newPassword', 'New Password')}</label>
                <input type="password" value={passwordForm.new_password} onChange={e => setPasswordForm(f => ({ ...f, new_password: e.target.value }))} required minLength={8}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">{t('modules:settings.confirmPassword', 'Confirm New Password')}</label>
                <input type="password" value={passwordForm.new_password_confirmation} onChange={e => setPasswordForm(f => ({ ...f, new_password_confirmation: e.target.value }))} required
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" />
              </div>
              <button type="submit" disabled={changePassword.isPending}
                className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-50">
                {changePassword.isPending ? t('modules:settings.saving', 'Saving...') : t('modules:settings.updatePassword', 'Update Password')}
              </button>
            </form>
          </div>
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="text-sm font-semibold mb-3">{t('modules:settings.sessions', 'Active Sessions')}</h3>
            <p className="text-sm text-muted-foreground">{t('modules:settings.sessionsDesc', 'You are currently logged in on this device.')}</p>
            <div className="mt-3 flex items-center gap-3 rounded-lg bg-muted/50 p-3">
              <Shield className="h-5 w-5 text-green-500" />
              <div>
                <p className="text-sm font-medium">{t('modules:settings.currentSession', 'Current Session')}</p>
                <p className="text-xs text-muted-foreground">{user?.email || '—'}</p>
              </div>
            </div>
          </div>
        </motion.div>
      )}
    </div>
  );
}
