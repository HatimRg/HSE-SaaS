import React from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  Building2,
  Users,
  AlertTriangle,
  FileCheck,
  ClipboardCheck,
  GraduationCap,
  TrendingUp,
  TrendingDown,
  Activity,
} from 'lucide-react';
import { api } from '../lib/api';
import { SkeletonCard, SkeletonStat } from '../components/skeleton';
import { EmptyState } from '../components/empty-state';

export default function DashboardPage() {
  const { t } = useTranslation();

  // Fetch dashboard data
  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const response = await api.get('/dashboard/stats');
      return response.data.data;
    },
  });

  // Fetch dashboard overview
  const { data: overview, isLoading: overviewLoading } = useQuery({
    queryKey: ['dashboard-overview'],
    queryFn: async () => {
      const response = await api.get('/dashboard');
      return response.data.data;
    },
  });

  const statCards = [
    {
      key: 'projects',
      label: t('dashboard.stats.projects'),
      value: stats?.projects || 0,
      icon: Building2,
      color: 'text-blue-500',
      bgColor: 'bg-blue-500/10',
    },
    {
      key: 'workers',
      label: t('dashboard.stats.workers'),
      value: stats?.workers || 0,
      icon: Users,
      color: 'text-green-500',
      bgColor: 'bg-green-500/10',
    },
    {
      key: 'openSors',
      label: t('dashboard.stats.openSors'),
      value: stats?.open_sors || 0,
      icon: AlertTriangle,
      color: 'text-red-500',
      bgColor: 'bg-red-500/10',
    },
    {
      key: 'activePermits',
      label: t('dashboard.stats.activePermits'),
      value: stats?.active_permits || 0,
      icon: FileCheck,
      color: 'text-amber-500',
      bgColor: 'bg-amber-500/10',
    },
    {
      key: 'inspectionsDue',
      label: t('dashboard.stats.inspectionsDue'),
      value: stats?.upcoming_inspections || 0,
      icon: ClipboardCheck,
      color: 'text-purple-500',
      bgColor: 'bg-purple-500/10',
    },
    {
      key: 'trainings',
      label: t('dashboard.stats.trainings'),
      value: stats?.training_sessions || 0,
      icon: GraduationCap,
      color: 'text-cyan-500',
      bgColor: 'bg-cyan-500/10',
    },
  ];

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
      },
    },
  };

  const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: {
      opacity: 1,
      y: 0,
      transition: { type: 'spring', stiffness: 300, damping: 30 },
    },
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <h1 className="text-2xl font-bold tracking-tight">
            {t('dashboard.title')}
          </h1>
          <p className="text-muted-foreground">
            {t('dashboard.welcome', { name: overview?.user?.name || '' })}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground">
            {new Date().toLocaleDateString(t('common.language') === 'fr' ? 'fr-FR' : 'en-US', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric',
            })}
          </span>
        </div>
      </motion.div>

      {/* Stats Grid */}
      <motion.div
        variants={containerVariants}
        initial="hidden"
        animate="visible"
        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
      >
        {statCards.map((stat, index) => (
          <motion.div
            key={stat.key}
            variants={itemVariants}
            whileHover={{ scale: 1.02 }}
            className="card-lift relative overflow-hidden rounded-xl border border-border bg-card p-4"
          >
            {statsLoading ? (
              <SkeletonStat />
            ) : (
              <>
                <div className="flex items-center justify-between">
                  <div className={`rounded-lg ${stat.bgColor} p-2`}>
                    <stat.icon className={`h-5 w-5 ${stat.color}`} />
                  </div>
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <Activity className="h-3 w-3" />
                  </div>
                </div>
                <div className="mt-3">
                  <p className="text-2xl font-bold">{stat.value}</p>
                  <p className="text-sm text-muted-foreground">{stat.label}</p>
                </div>
              </>
            )}
          </motion.div>
        ))}
      </motion.div>

      {/* Main Content Grid */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Charts Section */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="lg:col-span-2 space-y-6"
        >
          {/* Safety Metrics */}
          <div className="rounded-xl border border-border bg-card p-6">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="font-semibold">{t('dashboard.charts.safety')}</h2>
              <div className="flex gap-2">
                <button className="rounded-lg px-3 py-1 text-xs font-medium bg-primary/10 text-primary">
                  Month
                </button>
                <button className="rounded-lg px-3 py-1 text-xs font-medium text-muted-foreground hover:bg-muted">
                  Quarter
                </button>
                <button className="rounded-lg px-3 py-1 text-xs font-medium text-muted-foreground hover:bg-muted">
                  Year
                </button>
              </div>
            </div>
            {overviewLoading ? (
              <div className="h-64">
                <SkeletonCard />
              </div>
            ) : overview?.safety_metrics ? (
              <div className="grid gap-4 sm:grid-cols-4">
                <SafetyMetricCard
                  label="TRIR"
                  value={overview.safety_metrics.trir}
                  trend="down"
                />
                <SafetyMetricCard
                  label="Frequency Rate"
                  value={overview.safety_metrics.frequency_rate}
                  trend="down"
                />
                <SafetyMetricCard
                  label="Severity Rate"
                  value={overview.safety_metrics.severity_rate}
                  trend="up"
                />
                <SafetyMetricCard
                  label="Near Miss Rate"
                  value={overview.safety_metrics.near_miss_rate}
                  trend="up"
                />
              </div>
            ) : (
              <EmptyState title="No safety data" />
            )}
          </div>

          {/* Recent Activity */}
          <div className="rounded-xl border border-border bg-card p-6">
            <h2 className="mb-4 font-semibold">Recent Activity</h2>
            {overviewLoading ? (
              <div className="space-y-3">
                {[1, 2, 3].map((i) => (
                  <div key={i} className="h-12 animate-pulse rounded bg-muted" />
                ))}
              </div>
            ) : overview?.recent_activity?.length > 0 ? (
              <div className="space-y-3">
                {overview.recent_activity.slice(0, 5).map((activity: any, index: number) => (
                  <motion.div
                    key={index}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.1 }}
                    className="flex items-center gap-4 rounded-lg border border-border/50 p-3 hover:bg-muted/50 transition-colors"
                  >
                    <div className={`h-2 w-2 rounded-full ${
                      activity.severity === 'high' || activity.severity === 'critical'
                        ? 'bg-red-500'
                        : activity.severity === 'medium'
                        ? 'bg-amber-500'
                        : 'bg-green-500'
                    }`} />
                    <div className="flex-1 min-w-0">
                      <p className="font-medium truncate">{activity.title}</p>
                      <p className="text-xs text-muted-foreground">
                        {activity.description}
                      </p>
                    </div>
                    <span className="text-xs text-muted-foreground whitespace-nowrap">
                      {new Date(activity.date).toLocaleDateString()}
                    </span>
                  </motion.div>
                ))}
              </div>
            ) : (
              <EmptyState title="No recent activity" />
            )}
          </div>
        </motion.div>

        {/* Right Sidebar */}
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.5 }}
          className="space-y-6"
        >
          {/* Alerts */}
          <div className="rounded-xl border border-border bg-card p-6">
            <h2 className="mb-4 font-semibold flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-warning" />
              Alerts
            </h2>
            {overview?.alerts?.length > 0 ? (
              <div className="space-y-3">
                {overview.alerts.map((alert: any, index: number) => (
                  <motion.div
                    key={index}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.1 }}
                    className={`rounded-lg p-3 ${
                      alert.type === 'urgent'
                        ? 'bg-red-500/10 text-red-700 dark:text-red-400'
                        : alert.type === 'warning'
                        ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400'
                        : 'bg-blue-500/10 text-blue-700 dark:text-blue-400'
                    }`}
                  >
                    <p className="font-medium text-sm">{alert.title}</p>
                    <p className="text-xs mt-1">{alert.message}</p>
                  </motion.div>
                ))}
              </div>
            ) : (
              <EmptyState title="No alerts" description="Everything looks good!" />
            )}
          </div>

          {/* Compliance Status */}
          <div className="rounded-xl border border-border bg-card p-6">
            <h2 className="mb-4 font-semibold">{t('dashboard.charts.compliance')}</h2>
            {overview?.compliance ? (
              <div className="space-y-4">
                <div>
                  <div className="flex justify-between text-sm mb-1">
                    <span>Valid Permits</span>
                    <span className="font-medium">{overview.compliance.valid_permits}</span>
                  </div>
                  <div className="h-2 rounded-full bg-muted overflow-hidden">
                    <motion.div
                      initial={{ width: 0 }}
                      animate={{ width: `${Math.min((overview.compliance.valid_permits / 10) * 100, 100)}%` }}
                      transition={{ duration: 1, delay: 0.5 }}
                      className="h-full rounded-full bg-green-500"
                    />
                  </div>
                </div>
                <div>
                  <div className="flex justify-between text-sm mb-1">
                    <span>Inspection Pass Rate</span>
                    <span className="font-medium">{Math.round(overview.compliance.inspection_pass_rate)}%</span>
                  </div>
                  <div className="h-2 rounded-full bg-muted overflow-hidden">
                    <motion.div
                      initial={{ width: 0 }}
                      animate={{ width: `${overview.compliance.inspection_pass_rate}%` }}
                      transition={{ duration: 1, delay: 0.6 }}
                      className="h-full rounded-full bg-blue-500"
                    />
                  </div>
                </div>
                {overview.compliance.expired_permits > 0 && (
                  <div className="flex items-center gap-2 text-sm text-destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <span>{overview.compliance.expired_permits} expired permits</span>
                  </div>
                )}
              </div>
            ) : (
              <EmptyState title="No compliance data" />
            )}
          </div>
        </motion.div>
      </div>
    </div>
  );
}

// Safety Metric Card Component
function SafetyMetricCard({ label, value, trend }: { label: string; value: number; trend: 'up' | 'down' }) {
  return (
    <div className="rounded-lg bg-muted/50 p-4 text-center">
      <p className="text-2xl font-bold">{value.toFixed(2)}</p>
      <p className="text-xs text-muted-foreground">{label}</p>
      <div className={`mt-1 flex items-center justify-center gap-1 text-xs ${
        trend === 'down' ? 'text-green-500' : 'text-red-500'
      }`}>
        {trend === 'down' ? (
          <>
            <TrendingDown className="h-3 w-3" />
            <span>Good</span>
          </>
        ) : (
          <>
            <TrendingUp className="h-3 w-3" />
            <span>Watch</span>
          </>
        )}
      </div>
    </div>
  );
}
