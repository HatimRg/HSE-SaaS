import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  AreaChart, Area, BarChart, Bar, ComposedChart,
  PieChart, Pie, Cell, RadarChart, Radar, PolarGrid, PolarAngleAxis, PolarRadiusAxis,
  XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
  ScatterChart, Scatter, ZAxis, Line,
} from 'recharts';
import {
  Users, AlertTriangle, FileCheck,
  ShieldCheck, Clock, Zap, Activity, HardHat, GraduationCap,
  Leaf, ChevronRight, BarChart3, Target, ArrowUpRight,
  ArrowDownRight, Eye, RefreshCw, Flame, Gauge, PieChart as PieChartIcon,
} from 'lucide-react';
import { api } from '../lib/api';
import { useTheme } from '../components/theme-provider';

const CHART_COLORS = {
  primary: '#1e5f9e', success: '#2d7a4f', warning: '#b87333', danger: '#c0392b',
  info: '#2980b9', purple: '#7d3c98', cyan: '#1a8a8a', pink: '#c0507a',
  orange: '#d4722a', lime: '#5a8a2d',
};

function ChartTooltip({ active, payload, label }: any) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg border border-border bg-card p-3 shadow-xl">
      {label && <p className="mb-1 text-xs font-medium text-muted-foreground">{label}</p>}
      {payload.map((entry: any, idx: number) => (
        <div key={idx} className="flex items-center gap-2 text-sm">
          <span className="h-2 w-2 rounded-full" style={{ backgroundColor: entry.color }} />
          <span className="text-muted-foreground">{entry.name}:</span>
          <span className="font-semibold">{typeof entry.value === 'number' ? entry.value.toLocaleString() : entry.value}</span>
        </div>
      ))}
    </div>
  );
}

function KPIGauge({ value, max, label, color, unit }: { value: number; max: number; label: string; color: string; unit?: string }) {
  const percentage = Math.min((value / max) * 100, 100);
  const statusColor = percentage < 40 ? '#22c55e' : percentage < 70 ? '#f59e0b' : '#ef4444';
  return (
    <div className="flex flex-col items-center">
      <div className="relative w-24 h-24">
        <svg viewBox="0 0 100 100" className="w-full h-full -rotate-90">
          <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" strokeWidth="8" className="text-muted/30" />
          <circle cx="50" cy="50" r="40" fill="none" stroke={statusColor} strokeWidth="8"
            strokeDasharray={`${percentage * 2.51} 251`} strokeLinecap="round" className="transition-all duration-1000" />
        </svg>
        <div className="absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-lg font-bold">{value}</span>
          <span className="text-[10px] text-muted-foreground">{unit}</span>
        </div>
      </div>
      <span className="mt-1 text-xs text-muted-foreground text-center">{label}</span>
    </div>
  );
}

function StatCard({ title, value, change, changeLabel, icon: Icon, color, bgColor }: any) {
  const isPositive = change >= 0;
  const isGood = title === 'TRIR' || title === 'LTIFR' || title === 'Incidents' ? !isPositive : isPositive;
  return (
    <motion.div whileHover={{ scale: 1.02, y: -2 }}
      className="relative overflow-hidden rounded-xl border border-border bg-card p-4 shadow-sm hover:shadow-md transition-shadow">
      <div>
        <div className={`inline-flex rounded-lg ${bgColor} p-2 mb-2`}><Icon className={`h-4 w-4 ${color}`} /></div>
        <p className="text-2xl font-bold tracking-tight">{value}</p>
        <p className="text-xs text-muted-foreground mt-0.5">{title}</p>
      </div>
      {change !== 0 && (
        <div className={`mt-2 flex items-center gap-1 text-xs font-medium ${isGood ? 'text-green-500' : 'text-red-500'}`}>
          {isGood ? <ArrowDownRight className="h-3 w-3" /> : <ArrowUpRight className="h-3 w-3" />}
          <span>{Math.abs(change)}%</span>
          <span className="text-muted-foreground font-normal">{changeLabel}</span>
        </div>
      )}
    </motion.div>
  );
}

export default function DashboardPage() {
  const { t } = useTranslation();
  const { isDark } = useTheme();
  const [timeRange, setTimeRange] = useState<'7d' | '30d' | '90d' | '1y'>('30d');

  const { data: stats, refetch } = useQuery({
    queryKey: ['dashboard-stats', timeRange],
    queryFn: async () => {
      try { const r = await api.get('/dashboard/stats', { params: { range: timeRange } }); return r.data.data; }
      catch { return null; }
    },
  });

  const { data: overview } = useQuery({
    queryKey: ['dashboard-overview'],
    queryFn: async () => {
      try { const r = await api.get('/dashboard'); return r.data.data; }
      catch { return null; }
    },
  });

  const { data: charts } = useQuery({
    queryKey: ['dashboard-charts', timeRange],
    queryFn: async () => {
      try { const r = await api.get('/dashboard/charts', { params: { range: timeRange } }); return r.data.data; }
      catch { return null; }
    },
  });

  const { data: activities } = useQuery({
    queryKey: ['dashboard-activities'],
    queryFn: async () => {
      try { const r = await api.get('/dashboard/activities'); return r.data.data; }
      catch { return []; }
    },
  });

  const { data: alerts } = useQuery({
    queryKey: ['dashboard-alerts'],
    queryFn: async () => {
      try { const r = await api.get('/dashboard/alerts'); return r.data.data; }
      catch { return []; }
    },
  });

  const statCards = [
    { title: 'TRIR', value: stats?.trir !== undefined ? stats.trir.toFixed(2) : '0', change: stats?.trir_change ?? 0, changeLabel: t('dashboard:vsLastMonth', 'vs last month'), icon: ShieldCheck, color: 'text-blue-500', bgColor: 'bg-blue-500/10' },
    { title: 'LTIFR', value: stats?.ltifr !== undefined ? stats.ltifr.toFixed(2) : '0', change: stats?.ltifr_change ?? 0, changeLabel: t('dashboard:vsLastMonth', 'vs last month'), icon: Zap, color: 'text-purple-500', bgColor: 'bg-purple-500/10' },
    { title: t('dashboard:stats.workers'), value: stats?.daily_headcount ?? 0, change: stats?.headcount_change ?? 0, changeLabel: t('dashboard:vsYesterday', 'vs yesterday'), icon: Users, color: 'text-green-500', bgColor: 'bg-green-500/10' },
    { title: t('dashboard:incidents', 'Incidents'), value: stats?.incidents ?? 0, change: stats?.incidents_change ?? 0, changeLabel: t('dashboard:vsLastMonth', 'vs last month'), icon: AlertTriangle, color: 'text-red-500', bgColor: 'bg-red-500/10' },
    { title: t('dashboard:nearMisses', 'Near Misses'), value: stats?.near_miss ?? 0, change: stats?.near_miss_change ?? 0, changeLabel: t('dashboard:reportingRate', 'reporting rate'), icon: Eye, color: 'text-amber-500', bgColor: 'bg-amber-500/10' },
    { title: t('dashboard:permitCompliance', 'Permit Compliance'), value: `${stats?.permit_compliance ?? 0}%`, change: stats?.compliance_change ?? 0, changeLabel: t('dashboard:vsTarget', 'vs target'), icon: FileCheck, color: 'text-cyan-500', bgColor: 'bg-cyan-500/10' },
  ];

  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
  const axisColor = isDark ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.3)';

  const incidentTrend = charts?.incident_trend || [];
  const complianceRadar = charts?.compliance_radar || [];
  const incidentByType = charts?.incident_by_type || [];
  const performanceScore = charts?.performance_score || [];
  const trainingCompletion = charts?.training_completion || [];
  const ppeStatus = charts?.ppe_status || [];
  const riskMatrix = charts?.risk_matrix || [];
  const hasChartData = (d: any[]) => d.length > 0;

  return (
    <div className="space-y-6 pb-8">
      <motion.div initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }}
        className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{t('dashboard:title')}</h1>
          <p className="text-muted-foreground">{t('dashboard:welcome')}, {overview?.user?.name || ''}</p>
        </div>
        <div className="flex items-center gap-3">
          <div className="flex rounded-lg border border-border overflow-hidden">
            {(['7d', '30d', '90d', '1y'] as const).map(k => (
              <button key={k} onClick={() => setTimeRange(k)}
                className={`px-3 py-1.5 text-xs font-medium transition-colors ${timeRange === k ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'}`}>
                {k.toUpperCase()}
              </button>
            ))}
          </div>
          <button onClick={() => refetch()} className="rounded-lg border border-border p-2 text-muted-foreground hover:bg-muted transition-colors">
            <RefreshCw className="h-4 w-4" />
          </button>
        </div>
      </motion.div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {statCards.map((s, i) => (
          <motion.div key={s.title} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
            <StatCard {...s} />
          </motion.div>
        ))}
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Gauge className="h-4 w-4 text-blue-500" /> {t('dashboard:keySafety', 'Key Safety Indicators')}</h3>
          <div className="grid grid-cols-2 gap-4">
            <KPIGauge value={stats?.trir ?? 0} max={5} label="TRIR" color={CHART_COLORS.info} unit="rate" />
            <KPIGauge value={stats?.ltifr ?? 0} max={3} label="LTIFR" color={CHART_COLORS.purple} unit="rate" />
            <KPIGauge value={stats?.permit_compliance ?? 0} max={100} label={t('dashboard:compliance', 'Compliance')} color={CHART_COLORS.success} unit="%" />
            <KPIGauge value={stats?.training_rate ?? 0} max={100} label={t('dashboard:training', 'Training')} color={CHART_COLORS.warning} unit="%" />
          </div>
        </div>

        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-2 flex items-center gap-2"><PieChartIcon className="h-4 w-4 text-pink-500" /> {t('dashboard:incidentDistribution', 'Incident Distribution')}</h3>
          {hasChartData(incidentByType) ? (
            <><ResponsiveContainer width="100%" height={200}>
              <PieChart><Pie data={incidentByType} cx="50%" cy="50%" innerRadius={45} outerRadius={75} paddingAngle={2} dataKey="value">
                {incidentByType.map((_: any, i: number) => (<Cell key={i} fill={_.color || CHART_COLORS.danger} />))}
              </Pie><Tooltip content={<ChartTooltip />} /></PieChart>
            </ResponsiveContainer>
            <div className="grid grid-cols-2 gap-x-3 gap-y-1 mt-2">
              {incidentByType.slice(0, 4).map((item: any) => (
                <div key={item.name} className="flex items-center gap-1.5 text-xs">
                  <span className="h-2 w-2 rounded-full flex-shrink-0" style={{ backgroundColor: item.color || CHART_COLORS.danger }} />
                  <span className="text-muted-foreground truncate">{item.name}</span>
                </div>
              ))}
            </div></>
          ) : <div className="flex items-center justify-center h-[200px] text-sm text-muted-foreground">{t('dashboard:noData', 'No data')}</div>}
        </div>

        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-2 flex items-center gap-2"><Target className="h-4 w-4 text-green-500" /> {t('dashboard:complianceRadar', 'Compliance Radar')}</h3>
          {hasChartData(complianceRadar) ? (
            <ResponsiveContainer width="100%" height={220}>
              <RadarChart data={complianceRadar}>
                <PolarGrid stroke={gridColor} /><PolarAngleAxis dataKey="subject" tick={{ fill: axisColor, fontSize: 10 }} />
                <PolarRadiusAxis angle={30} domain={[0, 100]} tick={false} />
                <Radar name={t('dashboard:current', 'Current')} dataKey="A" stroke={CHART_COLORS.primary} fill={CHART_COLORS.primary} fillOpacity={0.2} strokeWidth={2} />
                <Radar name={t('dashboard:target', 'Target')} dataKey="B" stroke={CHART_COLORS.success} fill={CHART_COLORS.success} fillOpacity={0.1} strokeWidth={1.5} strokeDasharray="5 5" />
                <Tooltip content={<ChartTooltip />} /><Legend wrapperStyle={{ fontSize: 10 }} />
              </RadarChart>
            </ResponsiveContainer>
          ) : <div className="flex items-center justify-center h-[220px] text-sm text-muted-foreground">{t('dashboard:noData', 'No data')}</div>}
        </div>

        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-2 flex items-center gap-2"><Flame className="h-4 w-4 text-orange-500" /> {t('dashboard:riskHeatMap', 'Risk Heat Map')}</h3>
          {hasChartData(riskMatrix) ? (
            <ResponsiveContainer width="100%" height={220}>
              <ScatterChart>
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
                <XAxis dataKey="x" type="number" domain={[0, 6]} tick={{ fill: axisColor, fontSize: 10 }} />
                <YAxis dataKey="y" type="number" domain={[0, 6]} tick={{ fill: axisColor, fontSize: 10 }} />
                <ZAxis dataKey="z" range={[50, 400]} /><Tooltip content={<ChartTooltip />} />
                <Scatter data={riskMatrix}>
                  {riskMatrix.map((entry: any, i: number) => (
                    <Cell key={i} fill={entry.label === 'Critical' ? CHART_COLORS.danger : entry.label === 'High' ? CHART_COLORS.warning : entry.label === 'Medium' ? CHART_COLORS.info : CHART_COLORS.success} fillOpacity={0.7} />
                  ))}
                </Scatter>
              </ScatterChart>
            </ResponsiveContainer>
          ) : <div className="flex items-center justify-center h-[220px] text-sm text-muted-foreground">{t('dashboard:noData', 'No data')}</div>}
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="lg:col-span-2 rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold flex items-center gap-2 mb-4"><Activity className="h-4 w-4 text-red-500" /> {t('dashboard:incidentTrend', 'Incident & Near Miss Trend')}</h3>
          {hasChartData(incidentTrend) ? (
            <ResponsiveContainer width="100%" height={300}>
              <ComposedChart data={incidentTrend}>
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
                <XAxis dataKey="month" tick={{ fill: axisColor, fontSize: 11 }} /><YAxis tick={{ fill: axisColor, fontSize: 11 }} />
                <Tooltip content={<ChartTooltip />} /><Legend wrapperStyle={{ fontSize: 11 }} />
                <Area type="monotone" dataKey="nearMiss" name={t('dashboard:nearMiss', 'Near Miss')} fill={CHART_COLORS.warning} fillOpacity={0.15} stroke={CHART_COLORS.warning} strokeWidth={2} />
                <Bar dataKey="incidents" name={t('dashboard:incidents', 'Incidents')} fill={CHART_COLORS.danger} radius={[4, 4, 0, 0]} barSize={20} />
                <Line type="monotone" dataKey="lostTime" name={t('dashboard:lostTime', 'Lost Time')} stroke={CHART_COLORS.purple} strokeWidth={2} dot={{ r: 4 }} />
                <Line type="monotone" dataKey="target" name={t('dashboard:target', 'Target')} stroke={CHART_COLORS.success} strokeWidth={1.5} strokeDasharray="5 5" dot={false} />
              </ComposedChart>
            </ResponsiveContainer>
          ) : <div className="flex items-center justify-center h-[300px] text-sm text-muted-foreground">{t('dashboard:noData', 'No data')}</div>}
        </motion.div>

        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.35 }} className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><HardHat className="h-4 w-4 text-cyan-500" /> {t('dashboard:ppeStatus', 'PPE Inventory Status')}</h3>
          {hasChartData(ppeStatus) ? (
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={ppeStatus} layout="vertical">
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
                <XAxis type="number" tick={{ fill: axisColor, fontSize: 10 }} />
                <YAxis dataKey="name" type="category" tick={{ fill: axisColor, fontSize: 10 }} width={80} />
                <Tooltip content={<ChartTooltip />} /><Legend wrapperStyle={{ fontSize: 10 }} />
                <Bar dataKey="inStock" name={t('dashboard:inStock', 'In Stock')} fill={CHART_COLORS.success} stackId="a" />
                <Bar dataKey="issued" name={t('dashboard:issued', 'Issued')} fill={CHART_COLORS.info} stackId="a" />
                <Bar dataKey="expired" name={t('dashboard:expired', 'Expired')} fill={CHART_COLORS.danger} stackId="a" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          ) : <div className="flex items-center justify-center h-[300px] text-sm text-muted-foreground">{t('dashboard:noData', 'No data')}</div>}
        </motion.div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }} className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><BarChart3 className="h-4 w-4 text-indigo-500" /> {t('dashboard:performanceScore', 'HSE Performance Score')}</h3>
          {hasChartData(performanceScore) ? (
            <ResponsiveContainer width="100%" height={280}>
              <AreaChart data={performanceScore}>
                <defs><linearGradient id="colorOverall" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor={CHART_COLORS.primary} stopOpacity={0.3} />
                  <stop offset="95%" stopColor={CHART_COLORS.primary} stopOpacity={0} />
                </linearGradient></defs>
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
                <XAxis dataKey="month" tick={{ fill: axisColor, fontSize: 11 }} />
                <YAxis domain={[0, 100]} tick={{ fill: axisColor, fontSize: 11 }} />
                <Tooltip content={<ChartTooltip />} />
                <Area type="monotone" dataKey="overall" name={t('dashboard:overall', 'Overall')} stroke={CHART_COLORS.primary} fill="url(#colorOverall)" strokeWidth={2.5} />
                <Line type="monotone" dataKey="safety" name={t('dashboard:charts.safety')} stroke={CHART_COLORS.info} strokeWidth={1.5} dot={false} />
                <Line type="monotone" dataKey="env" name={t('dashboard:charts.environmental')} stroke={CHART_COLORS.success} strokeWidth={1.5} dot={false} />
                <Line type="monotone" dataKey="health" name={t('dashboard:health', 'Health')} stroke={CHART_COLORS.purple} strokeWidth={1.5} dot={false} />
              </AreaChart>
            </ResponsiveContainer>
          ) : <div className="flex items-center justify-center h-[280px] text-sm text-muted-foreground">{t('dashboard:noData', 'No data')}</div>}
        </motion.div>

        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.45 }} className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><GraduationCap className="h-4 w-4 text-amber-500" /> {t('dashboard:trainingCompletion', 'Training Completion Rates')}</h3>
          {hasChartData(trainingCompletion) ? (
            <div className="space-y-4">
              {trainingCompletion.map((item: any, idx: number) => {
                const completed = item.completed ?? 0;
                const total = item.total ?? 0;
                const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
                const color = pct >= 90 ? CHART_COLORS.success : pct >= 70 ? CHART_COLORS.warning : CHART_COLORS.danger;
                return (
                  <motion.div key={item.name} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.5 + idx * 0.05 }}>
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-sm font-medium">{item.name}</span>
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-muted-foreground">{completed}/{total}</span>
                        <span className="text-sm font-bold" style={{ color }}>{pct}%</span>
                      </div>
                    </div>
                    <div className="h-2.5 rounded-full bg-muted overflow-hidden">
                      <motion.div initial={{ width: 0 }} animate={{ width: `${pct}%` }} transition={{ duration: 0.8, delay: 0.6 + idx * 0.05 }}
                        className="h-full rounded-full" style={{ backgroundColor: color }} />
                    </div>
                  </motion.div>
                );
              })}
            </div>
          ) : <div className="flex items-center justify-center h-[280px] text-sm text-muted-foreground">{t('dashboard:noData', 'No data')}</div>}
        </motion.div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.5 }} className="lg:col-span-2 rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Clock className="h-4 w-4 text-blue-500" /> {t('dashboard:recentActivity', 'Recent Activity')}</h3>
          {activities && activities.length > 0 ? (
            <div className="space-y-3">
              {activities.map((item: any, idx: number) => (
                <motion.div key={item.id || idx} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.55 + idx * 0.05 }}
                  className="flex items-start gap-3 rounded-lg border border-border/50 p-3 hover:bg-muted/30 transition-colors cursor-pointer group">
                  <div className={`mt-1 h-2 w-2 rounded-full flex-shrink-0 ${item.type === 'danger' ? 'bg-red-500' : item.type === 'warning' ? 'bg-amber-500' : item.type === 'success' ? 'bg-green-500' : 'bg-blue-500'}`} />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium group-hover:text-primary transition-colors">{item.title || item.description}</p>
                    {item.description && item.title && <p className="text-xs text-muted-foreground">{item.description}</p>}
                  </div>
                  <span className="text-xs text-muted-foreground whitespace-nowrap">{item.time || item.created_at || ''}</span>
                  <ChevronRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                </motion.div>
              ))}
            </div>
          ) : <div className="flex items-center justify-center py-12 text-sm text-muted-foreground">{t('dashboard:noActivity', 'No recent activity')}</div>}
        </motion.div>

        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.55 }} className="space-y-6">
          <div className="rounded-xl border border-border bg-card p-5">
            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><AlertTriangle className="h-4 w-4 text-amber-500" /> {t('dashboard:activeAlerts', 'Active Alerts')}</h3>
            {alerts && alerts.length > 0 ? (
              <div className="space-y-2">
                {alerts.map((alert: any, idx: number) => (
                  <div key={idx} className={`rounded-lg p-2.5 text-xs font-medium ${
                    alert.level === 'danger' ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' :
                    alert.level === 'warning' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' :
                    'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20'
                  }`}>{alert.message || alert.msg}</div>
                ))}
              </div>
            ) : <p className="text-sm text-muted-foreground py-4 text-center">{t('dashboard:noAlerts', 'No active alerts')}</p>}
          </div>

          <div className="rounded-xl border border-border bg-card p-5">
            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><Leaf className="h-4 w-4 text-green-500" /> {t('dashboard:charts.environmental')}</h3>
            <div className="grid grid-cols-2 gap-3">
              {[
                { label: t('modules:environment.airQuality', 'Air Quality'), value: stats?.air_quality ?? 0, color: 'text-green-500' },
                { label: t('modules:environment.noiseLevel', 'Noise Level'), value: stats?.noise_level ?? 0, color: 'text-amber-500' },
                { label: t('modules:environment.wasteDiversion', 'Waste Div.'), value: stats?.waste_diversion ?? 0, color: 'text-blue-500' },
                { label: t('modules:environment.waterUsage', 'Water Usage'), value: stats?.water_usage ?? 0, color: 'text-cyan-500' },
              ].map((item, idx) => (
                <div key={idx} className="rounded-lg bg-muted/50 p-2.5 text-center">
                  <p className={`text-sm font-bold ${item.color}`}>{item.value || '—'}</p>
                  <p className="text-[10px] text-muted-foreground">{item.label}</p>
                </div>
              ))}
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  );
}
