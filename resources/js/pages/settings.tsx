import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { Palette, Bell, Globe, Shield } from 'lucide-react';

export default function SettingsPage() {
  const { t, i18n } = useTranslation();

  const settingsGroups = [
    {
      icon: Palette,
      title: 'Appearance',
      description: 'Customize the look and feel',
    },
    {
      icon: Bell,
      title: 'Notifications',
      description: 'Manage notification preferences',
    },
    {
      icon: Globe,
      title: 'Language & Region',
      description: `Current: ${i18n.language === 'fr' ? 'Français' : 'English'}`,
    },
    {
      icon: Shield,
      title: 'Security',
      description: 'Password and authentication',
    },
  ];

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <h1 className="text-2xl font-bold">{t('navigation.settings')}</h1>

      <div className="space-y-3">
        {settingsGroups.map((group, index) => (
          <motion.button
            key={group.title}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: index * 0.1 }}
            className="flex w-full items-center gap-4 rounded-xl border border-border bg-card p-4 text-left hover:bg-muted/50 transition-colors"
          >
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
              <group.icon className="h-5 w-5 text-primary" />
            </div>
            <div className="flex-1">
              <p className="font-medium">{group.title}</p>
              <p className="text-sm text-muted-foreground">{group.description}</p>
            </div>
          </motion.button>
        ))}
      </div>
    </div>
  );
}
