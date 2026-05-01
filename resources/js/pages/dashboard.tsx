import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  AreaChart, Area, BarChart, Bar, LineChart, Line, ComposedChart,
  PieChart, Pie, Cell, RadarChart, Radar, PolarGrid, PolarAngleAxis, PolarRadiusAxis,
  XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
  ScatterChart, Scatter, ZAxis,
} from 'recharts';
import {
  Users, AlertTriangle, FileCheck, TrendingUp, TrendingDown,
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

const RADIAN = Math.PI / 180;
const renderCustomizedLabel = ({ cx, cy, midAngle, innerRadius, outerRadius, percent }: any) => {
  const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
  const x = cx + radius * Math.cos(-midAngle * RADIAN);
  const y = cy + radius * Math.sin(-midAngle * RADIAN);
  return (
    <text x={x} y={y} fill="white" textAnchor="middle" dominantBaseline="central" fontSize={12} fontWeight={600}>
      {`${(percent * 100).toFixed(0)}%`}
    </text>
  );
};

function ChartTooltip({ active, payload, label }: any) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg border border-border bg-card p-3 shadow-xl backdrop-blur-sm">
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

function StatCard({ title, value, change, changeLabel, icon: Icon, color, bgColor, sparkData }: any) {
  const isPositive = change >= 0;
  const isGood = title === 'TRIR' || title === 'LTIFR' || title === 'Incidents' ? !isPositive : isPositive;
  return (
    <motion.div whileHover={{ scale: 1.02, y: -2 }}
      className="relative overflow-hidden rounded-xl border border-border bg-card p-4 shadow-sm hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between">
        <div>
          <div className={`inline-flex rounded-lg ${bgColor} p-2 mb-2`}><Icon className={`h-4 w-4 ${color}`} /></div>
          <p className="text-2xl font-bold tracking-tight">{value}</p>
          <p className="text-xs text-muted-foreground mt-0.5">{title}</p>
        </div>
        {sparkData && (
          <div className="w-20 h-10">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={sparkData}>
                <defs>
                  <linearGradient id={`spark-${title}`} x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor={isGood ? CHART_COLORS.success : CHART_COLORS.danger} stopOpacity={0.3} />
                    <stop offset="95%" stopColor={isGood ? CHART_COLORS.success : CHART_COLORS.danger} stopOpacity={0} />
                  </linearGradient>
                </defs>
                <Area type="monotone" dataKey="v" stroke={isGood ? CHART_COLORS.success : CHART_COLORS.danger}
                  fill={`url(#spark-${title})`} strokeWidth={1.5} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        )}
      </div>
      <div className={`mt-2 flex items-center gap-1 text-xs font-medium ${isGood ? 'text-green-500' : 'text-red-500'}`}>
        {isGood ? <ArrowDownRight className="h-3 w-3" /> : <ArrowUpRight className="h-3 w-3" />}
        <span>{Math.abs(change)}%</span>
        <span className="text-muted-foreground font-normal">{changeLabel}</span>
      </div>
    </motion.div>
  );
}

export default function DashboardPage() {
  const { t } = useTranslation();
  const { isDark } = useTheme();
  const [timeRange, setTimeRange] = useState<'7d' | '30d' | '90d' | '1y'>('30d');

  const { data: stats, refetch } = useQuery({
    queryKey: ['dashboard-stats', timeRange],
    queryFn: async () => { const r = await api.get('/dashboard/stats', { params: { range: timeRange } }); return r.data.data; },
  });

  const { data: overview } = useQuery({
    queryKey: ['dashboard-overview'],
    queryFn: async () => { const r = await api.get('/dashboard'); return r.data.data; },
  });

  const { data: charts } = useQuery({
    queryKey: ['dashboard-charts', timeRange],
    queryFn: async () => { const r = await api.get('/dashboard/charts', { params: { range: timeRange } }); return r.data.data; },
  });

  const demoIncidentTrend = [
    { month: 'Jan', incidents: 4, nearMiss: 12, lostTime: 1, target: 6 },
    { month: 'Feb', incidents: 3, nearMiss: 15, lostTime: 0, target: 6 },
    { month: 'Mar', incidents: 5, nearMiss: 10, lostTime: 2, target: 6 },
    { month: 'Apr', incidents: 2, nearMiss: 18, lostTime: 0, target: 6 },
    { month: 'May', incidents: 1, nearMiss: 22, lostTime: 0, target: 6 },
    { month: 'Jun', incidents: 3, nearMiss: 14, lostTime: 1, target: 6 },
    { month: 'Jul', incidents: 2, nearMiss: 20, lostTime: 0, target: 6 },
    { month: 'Aug', incidents: 1, nearMiss: 25, lostTime: 0, target: 6 },
    { month: 'Sep', incidents: 0, nearMiss: 30, lostTime: 0, target: 6 },
    { month: 'Oct', incidents: 1, nearMiss: 28, lostTime: 0, target: 6 },
    { month: 'Nov', incidents: 2, nearMiss: 19, lostTime: 1, target: 6 },
    { month: 'Dec', incidents: 1, nearMiss: 23, lostTime: 0, target: 6 },
  ];

  const demoComplianceRadar = [
    { subject: 'Safety', A: 92, B: 85, fullMark: 100 },
    { subject: 'Environment', A: 78, B: 80, fullMark: 100 },
    { subject: 'Health', A: 88, B: 75, fullMark: 100 },
    { subject: 'Training', A: 95, B: 90, fullMark: 100 },
    { subject: 'Permits', A: 85, B: 82, fullMark: 100 },
    { subject: 'PPE', A: 90, B: 88, fullMark: 100 },
  ];

  const demoIncidentByType = [
    { name: 'Slip/Trip', value: 28, color: CHART_COLORS.danger },
    { name: 'Fall', value: 15, color: CHART_COLORS.warning },
    { name: 'Chemical', value: 8, color: CHART_COLORS.purple },
    { name: 'Electrical', value: 5, color: CHART_COLORS.info },
    { name: 'Mechanical', value: 18, color: CHART_COLORS.orange },
    { name: 'Ergonomic', value: 12, color: CHART_COLORS.cyan },
    { name: 'Other', value: 14, color: CHART_COLORS.pink },
  ];

  const demoPerformanceScore = [
    { month: 'Jan', safety: 72, env: 68, health: 80, overall: 73 },
    { month: 'Feb', safety: 75, env: 70, health: 82, overall: 76 },
    { month: 'Mar', safety: 78, env: 72, health: 85, overall: 78 },
    { month: 'Apr', safety: 82, env: 75, health: 87, overall: 81 },
    { month: 'May', safety: 85, env: 78, health: 89, overall: 84 },
    { month: 'Jun', safety: 88, env: 80, health: 91, overall: 86 },
    { month: 'Jul', safety: 90, env: 82, health: 92, overall: 88 },
    { month: 'Aug', safety: 91, env: 85, health: 93, overall: 90 },
    { month: 'Sep', safety: 93, env: 87, health: 95, overall: 92 },
    { month: 'Oct', safety: 92, env: 88, health: 94, overall: 91 },
    { month: 'Nov', safety: 94, env: 90, health: 95, overall: 93 },
    { month: 'Dec', safety: 95, env: 92, health: 96, overall: 94 },
  ];

  const demoRiskMatrix = [
    { x: 1, y: 1, z: 2, label: 'Low' }, { x: 2, y: 1, z: 3, label: 'Low' },
    { x: 3, y: 1, z: 5, label: 'Medium' }, { x: 4, y: 1, z: 8, label: 'Medium' },
    { x: 5, y: 1, z: 12, label: 'High' }, { x: 1, y: 2, z: 3, label: 'Low' },
    { x: 2, y: 2, z: 5, label: 'Medium' }, { x: 3, y: 2, z: 8, label: 'Medium' },
    { x: 4, y: 2, z: 12, label: 'High' }, { x: 5, y: 2, z: 18, label: 'Critical' },
    { x: 1, y: 3, z: 5, label: 'Medium' }, { x: 2, y: 3, z: 8, label: 'Medium' },
    { x: 3, y: 3, z: 12, label: 'High' }, { x: 4, y: 3, z: 18, label: 'Critical' },
    { x: 5, y: 3, z: 25, label: 'Critical' }, { x: 1, y: 4, z: 8, label: 'Medium' },
    { x: 2, y: 4, z: 12, label: 'High' }, { x: 3, y: 4, z: 18, label: 'Critical' },
    { x: 4, y: 4, z: 25, label: 'Critical' }, { x: 5, y: 4, z: 35, label: 'Critical' },
    { x: 1, y: 5, z: 12, label: 'High' }, { x: 2, y: 5, z: 18, label: 'Critical' },
    { x: 3, y: 5, z: 25, label: 'Critical' }, { x: 4, y: 5, z: 35, label: 'Critical' },
    { x: 5, y: 5, z: 50, label: 'Critical' },
  ];

  const demoTrainingCompletion = [
    { name: 'Safety Induction', completed: 95, total: 100 },
    { name: 'Working at Heights', completed: 78, total: 90 },
    { name: 'Confined Space', completed: 65, total: 80 },
    { name: 'First Aid', completed: 88, total: 95 },
    { name: 'Fire Safety', completed: 92, total: 98 },
    { name: 'Chemical Handling', completed: 70, total: 85 },
  ];

  const demoPPEStatus = [
    { name: 'Helmets', inStock: 120, issued: 85, expired: 15 },
    { name: 'Safety Glasses', inStock: 200, issued: 150, expired: 25 },
    { name: 'Gloves', inStock: 500, issued: 380, expired: 45 },
    { name: 'Harnesses', inStock: 50, issued: 35, expired: 8 },
    { name: 'Respirators', inStock: 80, issued: 60, expired: 12 },
  ];

  const sparkData = (trend: 'up' | 'down') => Array.from({ length: 7 }, (_, i) => ({
    v: trend === 'up' ? 10 + i * 3 + Math.random() * 5 : 30 - i * 2 + Math.random() * 3,
  }));

  const statCards = [
    { title: 'TRIR', value: stats?.trir?.toFixed(2) || '0.42', change: -12, changeLabel: 'vs last month', icon: ShieldCheck, color: 'text-blue-500', bgColor: 'bg-blue-500/10', sparkData: sparkData('down') },
    { title: 'LTIFR', value: stats?.ltifr?.toFixed(2) || '0.18', change: -8, changeLabel: 'vs last month', icon: Zap, color: 'text-purple-500', bgColor: 'bg-purple-500/10', sparkData: sparkData('down') },
    { title: 'Headcount', value: stats?.daily_headcount || '247', change: 5, changeLabel: 'vs yesterday', icon: Users, color: 'text-green-500', bgColor: 'bg-green-500/10', sparkData: sparkData('up') },
    { title: 'Incidents', value: stats?.incidents || '3', change: -25, changeLabel: 'vs last month', icon: AlertTriangle, color: 'text-red-500', bgColor: 'bg-red-500/10', sparkData: sparkData('down') },
    { title: 'Near Misses', value: stats?.near_miss || '28', change: 15, changeLabel: 'reporting rate', icon: Eye, color: 'text-amber-500', bgColor: 'bg-amber-500/10', sparkData: sparkData('up') },
    { title: 'Permit Compliance', value: `${stats?.permit_compliance || '94'}%`, change: 3, changeLabel: 'vs target', icon: FileCheck, color: 'text-cyan-500', bgColor: 'bg-cyan-500/10', sparkData: sparkData('up') },
  ];

  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
  const axisColor = isDark ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.3)';

  return (
    <div className="space-y-6 pb-8">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }}
        className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{t('dashboard:title', 'Dashboard')}</h1>
          <p className="text-muted-foreground">{t('dashboard:welcome', 'Welcome back')}, {overview?.user?.name || 'Admin'}</p>
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

      {/* KPI Stat Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {statCards.map((s, i) => (
          <motion.div key={s.title} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
            <StatCard {...s} />
          </motion.div>
        ))}
      </div>

      {/* KPI Gauges + Donut + Radar + Risk Map */}
      <div className="grid gap-4 md:grid-cols-4">
        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Gauge className="h-4 w-4 text-blue-500" /> Key Safety Indicators</h3>
          <div className="grid grid-cols-2 gap-4">
            <KPIGauge value={0.42} max={5} label="TRIR" color={CHART_COLORS.info} unit="rate" />
            <KPIGauge value={0.18} max={3} label="LTIFR" color={CHART_COLORS.purple} unit="rate" />
            <KPIGauge value={94} max={100} label="Compliance" color={CHART_COLORS.success} unit="%" />
            <KPIGauge value={88} max={100} label="Training" color={CHART_COLORS.warning} unit="%" />
          </div>
        </div>

        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-2 flex items-center gap-2"><PieChartIcon className="h-4 w-4 text-pink-500" /> Incident Distribution</h3>
          <ResponsiveContainer width="100%" height={200}>
            <PieChart>
              <Pie data={demoIncidentByType} cx="50%" cy="50%" innerRadius={45} outerRadius={75} paddingAngle={2} dataKey="value" labelLine={false} label={renderCustomizedLabel}>
                {demoIncidentByType.map((entry, index) => (<Cell key={`cell-${index}`} fill={entry.color} />))}
              </Pie>
              <Tooltip content={<ChartTooltip />} />
            </PieChart>
          </ResponsiveContainer>
          <div className="grid grid-cols-2 gap-x-3 gap-y-1 mt-2">
            {demoIncidentByType.slice(0, 4).map(item => (
              <div key={item.name} className="flex items-center gap-1.5 text-xs">
                <span className="h-2 w-2 rounded-full flex-shrink-0" style={{ backgroundColor: item.color }} />
                <span className="text-muted-foreground truncate">{item.name}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-2 flex items-center gap-2"><Target className="h-4 w-4 text-green-500" /> Compliance Radar</h3>
          <ResponsiveContainer width="100%" height={220}>
            <RadarChart data={demoComplianceRadar}>
              <PolarGrid stroke={gridColor} />
              <PolarAngleAxis dataKey="subject" tick={{ fill: axisColor, fontSize: 10 }} />
              <PolarRadiusAxis angle={30} domain={[0, 100]} tick={false} />
              <Radar name="Current" dataKey="A" stroke={CHART_COLORS.primary} fill={CHART_COLORS.primary} fillOpacity={0.2} strokeWidth={2} />
              <Radar name="Target" dataKey="B" stroke={CHART_COLORS.success} fill={CHART_COLORS.success} fillOpacity={0.1} strokeWidth={1.5} strokeDasharray="5 5" />
              <Tooltip content={<ChartTooltip />} />
              <Legend wrapperStyle={{ fontSize: 10 }} />
            </RadarChart>
          </ResponsiveContainer>
        </div>

        <div className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-2 flex items-center gap-2"><Flame className="h-4 w-4 text-orange-500" /> Risk Heat Map</h3>
          <ResponsiveContainer width="100%" height={220}>
            <ScatterChart>
              <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
              <XAxis dataKey="x" name="Likelihood" type="number" domain={[0, 6]} tick={{ fill: axisColor, fontSize: 10 }} />
              <YAxis dataKey="y" name="Severity" type="number" domain={[0, 6]} tick={{ fill: axisColor, fontSize: 10 }} />
              <ZAxis dataKey="z" range={[50, 400]} />
              <Tooltip content={<ChartTooltip />} />
              <Scatter data={demoRiskMatrix}>
                {demoRiskMatrix.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={entry.label === 'Critical' ? CHART_COLORS.danger : entry.label === 'High' ? CHART_COLORS.warning : entry.label === 'Medium' ? CHART_COLORS.info : CHART_COLORS.success} fillOpacity={0.7} />
                ))}
              </Scatter>
            </ScatterChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Incident Trend + PPE */}
      <div className="grid gap-6 lg:grid-cols-3">
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="lg:col-span-2 rounded-xl border border-border bg-card p-5">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold flex items-center gap-2"><Activity className="h-4 w-4 text-red-500" /> Incident & Near Miss Trend</h3>
            <div className="flex items-center gap-4 text-xs text-muted-foreground">
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-red-500" /> Incidents</span>
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-amber-500" /> Near Miss</span>
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-purple-500" /> Lost Time</span>
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-green-500" /> Target</span>
            </div>
          </div>
          <ResponsiveContainer width="100%" height={300}>
            <ComposedChart data={charts?.incident_trend || demoIncidentTrend}>
              <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
              <XAxis dataKey="month" tick={{ fill: axisColor, fontSize: 11 }} />
              <YAxis tick={{ fill: axisColor, fontSize: 11 }} />
              <Tooltip content={<ChartTooltip />} />
              <Legend wrapperStyle={{ fontSize: 11 }} />
              <Area type="monotone" dataKey="nearMiss" name="Near Miss" fill={CHART_COLORS.warning} fillOpacity={0.15} stroke={CHART_COLORS.warning} strokeWidth={2} />
              <Bar dataKey="incidents" name="Incidents" fill={CHART_COLORS.danger} radius={[4, 4, 0, 0]} barSize={20} />
              <Line type="monotone" dataKey="lostTime" name="Lost Time" stroke={CHART_COLORS.purple} strokeWidth={2} dot={{ r: 4 }} />
              <Line type="monotone" dataKey="target" name="Target" stroke={CHART_COLORS.success} strokeWidth={1.5} strokeDasharray="5 5" dot={false} />
            </ComposedChart>
          </ResponsiveContainer>
        </motion.div>

        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.35 }} className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><HardHat className="h-4 w-4 text-cyan-500" /> PPE Inventory Status</h3>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={demoPPEStatus} layout="vertical">
              <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
              <XAxis type="number" tick={{ fill: axisColor, fontSize: 10 }} />
              <YAxis dataKey="name" type="category" tick={{ fill: axisColor, fontSize: 10 }} width={80} />
              <Tooltip content={<ChartTooltip />} />
              <Legend wrapperStyle={{ fontSize: 10 }} />
              <Bar dataKey="inStock" name="In Stock" fill={CHART_COLORS.success} stackId="a" />
              <Bar dataKey="issued" name="Issued" fill={CHART_COLORS.info} stackId="a" />
              <Bar dataKey="expired" name="Expired" fill={CHART_COLORS.danger} stackId="a" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </motion.div>
      </div>

      {/* Performance + Training */}
      <div className="grid gap-6 lg:grid-cols-2">
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }} className="rounded-xl border border-border bg-card p-5">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold flex items-center gap-2"><BarChart3 className="h-4 w-4 text-indigo-500" /> HSE Performance Score</h3>
            <div className="flex items-center gap-3 text-xs text-muted-foreground">
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-indigo-500" /> Overall</span>
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-blue-500" /> Safety</span>
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-green-500" /> Env</span>
              <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-purple-500" /> Health</span>
            </div>
          </div>
          <ResponsiveContainer width="100%" height={280}>
            <AreaChart data={demoPerformanceScore}>
              <defs>
                <linearGradient id="colorOverall" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor={CHART_COLORS.primary} stopOpacity={0.3} />
                  <stop offset="95%" stopColor={CHART_COLORS.primary} stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
              <XAxis dataKey="month" tick={{ fill: axisColor, fontSize: 11 }} />
              <YAxis domain={[50, 100]} tick={{ fill: axisColor, fontSize: 11 }} />
              <Tooltip content={<ChartTooltip />} />
              <Area type="monotone" dataKey="overall" name="Overall" stroke={CHART_COLORS.primary} fill="url(#colorOverall)" strokeWidth={2.5} />
              <Line type="monotone" dataKey="safety" name="Safety" stroke={CHART_COLORS.info} strokeWidth={1.5} dot={false} />
              <Line type="monotone" dataKey="env" name="Environment" stroke={CHART_COLORS.success} strokeWidth={1.5} dot={false} />
              <Line type="monotone" dataKey="health" name="Health" stroke={CHART_COLORS.purple} strokeWidth={1.5} dot={false} />
            </AreaChart>
          </ResponsiveContainer>
        </motion.div>

        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.45 }} className="rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><GraduationCap className="h-4 w-4 text-amber-500" /> Training Completion Rates</h3>
          <div className="space-y-4">
            {demoTrainingCompletion.map((item, idx) => {
              const pct = Math.round((item.completed / item.total) * 100);
              const color = pct >= 90 ? CHART_COLORS.success : pct >= 70 ? CHART_COLORS.warning : CHART_COLORS.danger;
              return (
                <motion.div key={item.name} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.5 + idx * 0.05 }}>
                  <div className="flex items-center justify-between mb-1.5">
                    <span className="text-sm font-medium">{item.name}</span>
                    <div className="flex items-center gap-2">
                      <span className="text-xs text-muted-foreground">{item.completed}/{item.total}</span>
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
        </motion.div>
      </div>

      {/* Activity + Alerts + Environmental */}
      <div className="grid gap-6 lg:grid-cols-3">
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.5 }} className="lg:col-span-2 rounded-xl border border-border bg-card p-5">
          <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Clock className="h-4 w-4 text-blue-500" /> Recent Activity</h3>
          <div className="space-y-3">
            {[
              { title: 'Permit #WP-2024-089 approved', desc: 'Hot work permit for Building A', time: '5 min ago', type: 'success' },
              { title: 'Inspection failed - Scaffold Sector B', desc: 'Safety railing missing on 3rd floor', time: '22 min ago', type: 'danger' },
              { title: 'Near miss reported', desc: 'Falling object near Gate 4', time: '1 hr ago', type: 'warning' },
              { title: 'Training completed: 12 workers', desc: 'Working at Heights certification', time: '2 hrs ago', type: 'info' },
              { title: 'KPI Report submitted', desc: 'Monthly safety metrics for Project Alpha', time: '3 hrs ago', type: 'success' },
              { title: 'PPE stock alert', desc: 'Safety gloves below minimum threshold', time: '4 hrs ago', type: 'warning' },
            ].map((item, idx) => (
              <motion.div key={idx} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.55 + idx * 0.05 }}
                className="flex items-start gap-3 rounded-lg border border-border/50 p-3 hover:bg-muted/30 transition-colors cursor-pointer group">
                <div className={`mt-1 h-2 w-2 rounded-full flex-shrink-0 ${item.type === 'danger' ? 'bg-red-500' : item.type === 'warning' ? 'bg-amber-500' : item.type === 'success' ? 'bg-green-500' : 'bg-blue-500'}`} />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium group-hover:text-primary transition-colors">{item.title}</p>
                  <p className="text-xs text-muted-foreground">{item.desc}</p>
                </div>
                <span className="text-xs text-muted-foreground whitespace-nowrap">{item.time}</span>
                <ChevronRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
              </motion.div>
            ))}
          </div>
        </motion.div>

        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.55 }} className="space-y-6">
          <div className="rounded-xl border border-border bg-card p-5">
            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><AlertTriangle className="h-4 w-4 text-amber-500" /> Active Alerts</h3>
            <div className="space-y-2">
              {[
                { msg: '3 permits expiring today', level: 'warning' },
                { msg: 'Scaffold inspection overdue', level: 'danger' },
                { msg: 'PPE stock below threshold', level: 'warning' },
                { msg: '2 workers need recertification', level: 'info' },
              ].map((alert, idx) => (
                <div key={idx} className={`rounded-lg p-2.5 text-xs font-medium ${
                  alert.level === 'danger' ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' :
                  alert.level === 'warning' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' :
                  'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20'
                }`}>{alert.msg}</div>
              ))}
            </div>
          </div>

          <div className="rounded-xl border border-border bg-card p-5">
            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><Leaf className="h-4 w-4 text-green-500" /> Environmental</h3>
            <div className="grid grid-cols-2 gap-3">
              {[
                { label: 'Air Quality', value: 'Good', color: 'text-green-500' },
                { label: 'Noise Level', value: '72 dB', color: 'text-amber-500' },
                { label: 'Waste Div.', value: '85%', color: 'text-blue-500' },
                { label: 'Water Usage', value: 'Normal', color: 'text-cyan-500' },
              ].map((item, idx) => (
                <div key={idx} className="rounded-lg bg-muted/50 p-2.5 text-center">
                  <p className={`text-sm font-bold ${item.color}`}>{item.value}</p>
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
