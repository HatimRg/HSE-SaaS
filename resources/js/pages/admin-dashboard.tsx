import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  RadarChart,
  Radar,
  AreaChart,
  Area,
  ComposedChart,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  Cell,
  PolarGrid,
  PolarAngleAxis,
  PolarRadiusAxis,
} from 'recharts';
import {
  Shield,
  GraduationCap,
  FileCheck,
  HardHat,
  TreePine,
  AlertTriangle,
  TrendingUp,
  Users,
  Activity,
  Target,
  Zap,
  Award,
  Calendar,
  Filter,
  Download,
} from 'lucide-react';
import { api } from '../lib/api';
import { SkeletonCard, SkeletonStat } from '../components/skeleton';

// Theme configurations
const dashboardThemes = {
  safety: {
    name: 'Safety',
    icon: Shield,
    color: '#ef4444',
    bgColor: '#fef2f2',
    charts: ['trir', 'ltifr', 'incidentTypes', 'nearMisses'],
  },
  training: {
    name: 'Training',
    icon: GraduationCap,
    color: '#3b82f6',
    bgColor: '#eff6ff',
    charts: ['trainingCompletion', 'skillLevels', 'certificationExpiry'],
  },
  compliance: {
    name: 'Compliance',
    icon: FileCheck,
    color: '#10b981',
    bgColor: '#f0fdf4',
    charts: ['permitCompliance', 'inspectionScores', 'auditResults'],
  },
  ppe: {
    name: 'PPE',
    icon: HardHat,
    color: '#f59e0b',
    bgColor: '#fffbeb',
    charts: ['stockLevels', 'issuanceRates', 'complianceRates'],
  },
  environmental: {
    name: 'Environmental',
    icon: TreePine,
    color: '#22c55e',
    bgColor: '#f0fdf4',
    charts: ['wasteManagement', 'energyConsumption', 'emissions'],
  },
  deviation: {
    name: 'Deviation',
    icon: AlertTriangle,
    color: '#f97316',
    bgColor: '#fff7ed',
    charts: ['deviationTrends', 'correctiveActions', 'riskAssessment'],
  },
  monthly: {
    name: 'Monthly Report',
    icon: Calendar,
    color: '#8b5cf6',
    bgColor: '#faf5ff',
    charts: ['monthlySummary', 'trendAnalysis', 'performanceMetrics'],
  },
};

export default function AdminDashboardPage() {
  const { t } = useTranslation();
  const [selectedTheme, setSelectedTheme] = useState('safety');
  const [dateRange, setDateRange] = useState('month');
  
  const currentTheme = dashboardThemes[selectedTheme as keyof typeof dashboardThemes];

  // Fetch dashboard data based on theme
  const { data: themeData, isLoading: themeLoading } = useQuery({
    queryKey: ['admin-dashboard', selectedTheme, dateRange],
    queryFn: async () => {
      const response = await api.get(`/dashboard/admin/${selectedTheme}?range=${dateRange}`);
      return response.data.data;
    },
  });

  // Fetch overall stats
  const { data: overallStats, isLoading: statsLoading } = useQuery({
    queryKey: ['admin-overall-stats'],
    queryFn: async () => {
      const response = await api.get('/dashboard/admin/overall');
      return response.data.data;
    },
  });

  const renderChart = (chartType: string, data: any) => {
    switch (chartType) {
      case 'trir':
        return (
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={data?.monthlyTrends || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip />
              <Legend />
              <Line type="monotone" dataKey="trir" stroke={currentTheme.color} strokeWidth={2} />
              <Line type="monotone" dataKey="target" stroke="#94a3b8" strokeDasharray="5 5" />
            </LineChart>
          </ResponsiveContainer>
        );

      case 'incidentTypes':
        return (
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={data?.incidentTypes || []}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                outerRadius={80}
                fill="#8884d8"
                dataKey="value"
              >
                {(data?.incidentTypes || []).map((entry: any, index: number) => (
                  <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        );

      case 'trainingCompletion':
        return (
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={data?.trainingData || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="department" />
              <YAxis />
              <Tooltip />
              <Legend />
              <Bar dataKey="completed" fill={currentTheme.color} />
              <Bar dataKey="pending" fill="#94a3b8" />
            </BarChart>
          </ResponsiveContainer>
        );

      case 'permitCompliance':
        return (
          <ResponsiveContainer width="100%" height={300}>
            <AreaChart data={data?.complianceData || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              <YAxis />
              <Tooltip />
              <Area type="monotone" dataKey="compliance" stroke={currentTheme.color} fill={currentTheme.color} fillOpacity={0.6} />
            </AreaChart>
          </ResponsiveContainer>
        );

      case 'stockLevels':
        return (
          <ResponsiveContainer width="100%" height={300}>
            <ComposedChart data={data?.stockData || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="item" />
              <YAxis />
              <Tooltip />
              <Legend />
              <Bar dataKey="current" fill={currentTheme.color} />
              <Line type="monotone" dataKey="minimum" stroke="#ef4444" />
              <Line type="monotone" dataKey="optimal" stroke="#22c55e" />
            </ComposedChart>
          </ResponsiveContainer>
        );

      case 'riskAssessment':
        return (
          <ResponsiveContainer width="100%" height={300}>
            <RadarChart data={data?.riskData || []}>
              <PolarGrid />
              <PolarAngleAxis dataKey="category" />
              <PolarRadiusAxis />
              <Radar name="Current" dataKey="current" stroke={currentTheme.color} fill={currentTheme.color} fillOpacity={0.6} />
              <Radar name="Target" dataKey="target" stroke="#94a3b8" fill="#94a3b8" fillOpacity={0.3} />
            </RadarChart>
          </ResponsiveContainer>
        );

      default:
        return (
          <div className="h-64 flex items-center justify-center text-muted-foreground">
            Chart data not available
          </div>
        );
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
      >
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
          <p className="text-muted-foreground">
            Comprehensive HSE management overview
          </p>
        </div>
        
        <div className="flex items-center gap-4">
          {/* Date Range Selector */}
          <div className="flex rounded-lg border border-border">
            {['week', 'month', 'quarter', 'year'].map((range) => (
              <button
                key={range}
                onClick={() => setDateRange(range)}
                className={`px-3 py-2 text-sm font-medium capitalize rounded-l-lg first:rounded-l-lg last:rounded-r-lg ${
                  dateRange === range
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted'
                }`}
              >
                {range}
              </button>
            ))}
          </div>

          {/* Export Button */}
          <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
            <Download className="h-4 w-4" />
            Export
          </button>
        </div>
      </motion.div>

      {/* Theme Selector */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4"
      >
        {Object.entries(dashboardThemes).map(([key, theme]) => (
          <motion.button
            key={key}
            onClick={() => setSelectedTheme(key)}
            className={`relative overflow-hidden rounded-xl border-2 p-4 text-center transition-all ${
              selectedTheme === key
                ? 'border-primary shadow-lg scale-105'
                : 'border-border hover:border-primary/50'
            }`}
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
          >
            <div
              className="mx-auto mb-2 h-10 w-10 rounded-full flex items-center justify-center"
              style={{ backgroundColor: theme.bgColor }}
            >
              <theme.icon className="h-5 w-5" style={{ color: theme.color }} />
            </div>
            <p className="text-sm font-medium">{theme.name}</p>
            {selectedTheme === key && (
              <motion.div
                layoutId="activeTheme"
                className="absolute inset-0 border-2 border-primary rounded-xl"
                style={{ backgroundColor: `${theme.color}10` }}
              />
            )}
          </motion.button>
        ))}
      </motion.div>

      {/* Key Metrics */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
      >
        {[
          { label: 'Total Projects', value: overallStats?.totalProjects || 0, icon: Target, color: 'text-blue-500' },
          { label: 'Active Workers', value: overallStats?.activeWorkers || 0, icon: Users, color: 'text-green-500' },
          { label: 'Safety Score', value: `${overallStats?.safetyScore || 0}%`, icon: Shield, color: 'text-purple-500' },
          { label: 'Compliance Rate', value: `${overallStats?.complianceRate || 0}%`, icon: FileCheck, color: 'text-amber-500' },
        ].map((metric, index) => (
          <motion.div
            key={metric.label}
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 0.3 + index * 0.1 }}
            className="rounded-xl border border-border bg-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">{metric.label}</p>
                <p className="text-2xl font-bold">{metric.value}</p>
              </div>
              <metric.icon className={`h-8 w-8 ${metric.color}`} />
            </div>
          </motion.div>
        ))}
      </motion.div>

      {/* Main Charts Grid */}
      <AnimatePresence mode="wait">
        <motion.div
          key={selectedTheme}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: -20 }}
          transition={{ duration: 0.3 }}
          className="grid gap-6 lg:grid-cols-2"
        >
          {currentTheme.charts.map((chartType, index) => (
            <motion.div
              key={chartType}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: index * 0.1 }}
              className="rounded-xl border border-border bg-card p-6"
            >
              <h3 className="text-lg font-semibold mb-4 capitalize">
                {chartType.replace(/([A-Z])/g, ' $1').trim()}
              </h3>
              {themeLoading ? (
                <SkeletonCard />
              ) : (
                renderChart(chartType, themeData)
              )}
            </motion.div>
          ))}
        </motion.div>
      </AnimatePresence>

      {/* Additional Insights */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.5 }}
        className="grid gap-6 lg:grid-cols-3"
      >
        {/* Top Performers */}
        <div className="rounded-xl border border-border bg-card p-6">
          <h3 className="text-lg font-semibold mb-4">Top Performers</h3>
          {themeData?.topPerformers?.length > 0 ? (
            <div className="space-y-3">
              {themeData.topPerformers.slice(0, 5).map((performer: any, index: number) => (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                      <span className="text-xs font-bold">{index + 1}</span>
                    </div>
                    <div>
                      <p className="font-medium">{performer.name}</p>
                      <p className="text-xs text-muted-foreground">{performer.project}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-bold">{performer.score}%</p>
                    <p className="text-xs text-muted-foreground">Score</p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center text-muted-foreground py-8">
              No data available
            </div>
          )}
        </div>

        {/* Critical Issues */}
        <div className="rounded-xl border border-border bg-card p-6">
          <h3 className="text-lg font-semibold mb-4">Critical Issues</h3>
          {themeData?.criticalIssues?.length > 0 ? (
            <div className="space-y-3">
              {themeData.criticalIssues.slice(0, 5).map((issue: any, index: number) => (
                <div key={index} className="flex items-start gap-3">
                  <AlertTriangle className="h-5 w-5 text-red-500 mt-0.5" />
                  <div className="flex-1">
                    <p className="font-medium">{issue.title}</p>
                    <p className="text-xs text-muted-foreground">{issue.location}</p>
                    <p className="text-xs text-red-500 mt-1">{issue.severity}</p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center text-muted-foreground py-8">
              No critical issues
            </div>
          )}
        </div>

        {/* Recent Activities */}
        <div className="rounded-xl border border-border bg-card p-6">
          <h3 className="text-lg font-semibold mb-4">Recent Activities</h3>
          {themeData?.recentActivities?.length > 0 ? (
            <div className="space-y-3">
              {themeData.recentActivities.slice(0, 5).map((activity: any, index: number) => (
                <div key={index} className="flex items-start gap-3">
                  <Activity className="h-5 w-5 text-primary mt-0.5" />
                  <div className="flex-1">
                    <p className="font-medium">{activity.action}</p>
                    <p className="text-xs text-muted-foreground">{activity.user}</p>
                    <p className="text-xs text-muted-foreground">
                      {new Date(activity.timestamp).toLocaleString()}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center text-muted-foreground py-8">
              No recent activities
            </div>
          )}
        </div>
      </motion.div>
    </div>
  );
}

// Chart colors
const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];
