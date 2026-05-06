import { motion } from 'framer-motion';
import { useTranslation } from 'react-i18next';
import { Mail, Phone, Building, Shield, FolderKanban, Camera, Pencil, Calendar, Clock, MapPin } from 'lucide-react';
import { useAuth } from '../components/auth-provider';
import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';

export default function ProfilePage() {
  const { t } = useTranslation();
  const { user } = useAuth();

  const { data: projects } = useQuery({
    queryKey: ['user-projects'],
    queryFn: async () => {
      try { const r = await api.get('/projects'); return r.data.data; } catch { return []; }
    },
  });

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      {/* Profile Header Card */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className="rounded-xl border border-border bg-card overflow-hidden"
      >
        {/* Banner */}
        <div className="h-32 bg-gradient-to-r from-primary/20 via-primary/10 to-primary/5 relative">
          <div className="absolute inset-0 opacity-10" style={{
            backgroundImage: 'radial-gradient(circle at 1px 1px, currentColor 1px, transparent 0)',
            backgroundSize: '24px 24px'
          }} />
        </div>

        {/* Avatar + Info */}
        <div className="px-6 pb-6 -mt-12 relative">
          <div className="flex flex-col sm:flex-row sm:items-end gap-4">
            <div className="relative">
              <img
                src={user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'U')}&background=1e5f9e&color=fff&size=128`}
                alt={user?.name}
                className="h-24 w-24 rounded-2xl border-4 border-card shadow-lg object-cover"
              />
              <button className="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-md hover:opacity-90 transition-opacity">
                <Camera className="h-4 w-4" />
              </button>
            </div>
            <div className="flex-1 pt-2">
              <h1 className="text-2xl font-bold">{user?.name}</h1>
              <div className="flex flex-wrap items-center gap-2 mt-1">
                <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                  <Shield className="h-3 w-3" />
                  {user?.role?.display_name}
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">
                  <Building className="h-3 w-3" />
                  {user?.company?.name}
                </span>
              </div>
            </div>
            <button className="flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
              <Pencil className="h-4 w-4" />
              {t('modules:profile.editProfile')}
            </button>
          </div>
        </div>
      </motion.div>

      {/* Info Grid */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {[
          { icon: Mail, label: t('common:email'), value: user?.email },
          { icon: Phone, label: t('common:phone'), value: user?.phone || '—' },
          { icon: Building, label: t('common:company'), value: user?.company?.name },
          { icon: Shield, label: t('modules:profile.role'), value: user?.role?.display_name },
          { icon: Calendar, label: t('modules:profile.memberSince'), value: user?.created_at ? new Date(user.created_at).toLocaleDateString() : '—' },
          { icon: Clock, label: t('modules:profile.lastLogin'), value: user?.last_login_at ? new Date(user.last_login_at).toLocaleString() : '—' },
        ].map((item, i) => (
          <motion.div
            key={item.label}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.05 }}
            className="rounded-xl border border-border bg-card p-4"
          >
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                <item.icon className="h-5 w-5 text-primary" />
              </div>
              <div className="min-w-0 flex-1">
                <p className="text-xs text-muted-foreground">{item.label}</p>
                <p className="text-sm font-medium truncate">{item.value || '—'}</p>
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      {/* Project Access */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.3 }}
        className="rounded-xl border border-border bg-card p-6"
      >
        <h3 className="text-sm font-semibold mb-4">{t('modules:profile.projectAccess')}</h3>
        <div className="flex items-center gap-3 mb-4">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
            <FolderKanban className="h-5 w-5 text-primary" />
          </div>
          <div>
            <p className="font-medium">
              {user?.project_access?.type === 'all' && t('modules:profile.accessType.all', 'All Projects')}
              {user?.project_access?.type === 'pole' && t('modules:profile.accessType.pole', 'Pole Level Access')}
              {user?.project_access?.type === 'projects' && t('modules:profile.accessType.projects', 'Specific Projects Only')}
              {!user?.project_access?.type && '—'}
            </p>
            {user?.project_access?.has_specific_projects && (
              <p className="text-xs text-muted-foreground mt-1">
                {t('modules:profile.accessLimited', 'Access limited to assigned projects')}
              </p>
            )}
          </div>
        </div>

        {projects?.length > 0 && (
          <div className="grid gap-2 sm:grid-cols-2">
            {projects.slice(0, 6).map((project: any) => (
              <div key={project.id} className="flex items-center gap-2 rounded-lg border border-border p-3">
                <MapPin className="h-4 w-4 text-muted-foreground flex-shrink-0" />
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium truncate">{project.name}</p>
                  <p className="text-xs text-muted-foreground">{project.status}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </motion.div>
    </div>
  );
}
