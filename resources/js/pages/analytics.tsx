import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  Shield,
  DollarSign,
  BarChart3,
  Zap,
  Target,
  Award,
  RefreshCw,
  Eye,
  Brain,
  Globe,
  Users,
  Clock,
  CheckCircle,
  XCircle,
  AlertCircle,
  Info,
} from 'lucide-react';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
  Area,
  AreaChart,
  RadarChart,
  PolarGrid,
  PolarAngleAxis,
  PolarRadiusAxis,
  Radar,
  ScatterChart,
  Scatter,
  ComposedChart,
} from 'recharts';
import { api } from '../lib/api';
import { SkeletonCard } from '../components/skeleton';

// Theme configurations
const themes = {
  professional: {
    name: 'Professional',
    primary: '#2563eb',
    background: '#ffffff',
    grid: '#f1f5f9',
    text: '#1e293b',
  },
  executive: {
    name: 'Executive',
    primary: '#7c3aed',
    background: '#fafafa',
    grid: '#e5e7eb',
    text: '#111827',
  },
  operational: {
    name: 'Operational',
    primary: '#059669',
    background: '#f0fdf4',
    grid: '#dcfce7',
    text: '#064e3b',
  },
  risk: {
    name: 'Risk Focus',
    primary: '#dc2626',
    background: '#fef2f2',
    grid: '#fee2e2',
    text: '#7f1d1d',
  },
};

export default function AnalyticsPage() {
  const { t } = useTranslation();
  const [selectedTheme, setSelectedTheme] = useState('professional');
  const [selectedProject, setSelectedProject] = useState('all');
  const [dateRange, setDateRange] = useState('90days');
  const [activeDashboard, setActiveDashboard] = useState<'overview' | 'performance' | 'risk' | 'compliance' | 'predictive'>('overview');

  // Fetch analytics data
  const { data: analyticsData, isLoading, refetch } = useQuery({
    queryKey: ['analytics', selectedProject, dateRange, selectedTheme],
    queryFn: async () => {
      const params = new URLSearchParams({
        project_id: selectedProject,
        date_range: dateRange,
        theme: selectedTheme,
      });
      
      const response = await api.get(`/analytics/dashboard?${params}`);
      return response.data.data;
    },
    refetchInterval: 60000, // Refresh every minute
  });

  const currentTheme = themes[selectedTheme as keyof typeof themes];

  
  return (
    <div className="min-h-screen bg-background" style={{ backgroundColor: currentTheme.background }}>
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="sticky top-0 z-50 bg-background/95 backdrop-blur-sm border-b border-border"
      >
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold" style={{ color: currentTheme.text }}>
                Advanced Analytics Dashboard
              </h1>
              <p className="text-muted-foreground">
                Million-dollar insights for safety excellence
              </p>
            </div>
            
            <div className="flex items-center gap-4">
              {/* Theme Selector */}
              <div className="flex rounded-lg border border-border overflow-hidden">
                {Object.entries(themes).map(([key, theme]) => (
                  <button
                    key={key}
                    onClick={() => setSelectedTheme(key)}
                    className={`px-3 py-2 text-sm font-medium transition-colors ${
                      selectedTheme === key
                        ? 'text-white'
                        : 'text-muted-foreground hover:text-foreground'
                    }`}
                    style={{
                      backgroundColor: selectedTheme === key ? theme.primary : 'transparent',
                    }}
                  >
                    {theme.name}
                  </button>
                ))}
              </div>

              {/* Filters */}
              <select
                value={selectedProject}
                onChange={(e) => setSelectedProject(e.target.value)}
                className="px-3 py-2 border border-border rounded-lg text-sm"
              >
                <option value="all">All Projects</option>
              </select>
              
              <select
                value={dateRange}
                onChange={(e) => setDateRange(e.target.value)}
                className="px-3 py-2 border border-border rounded-lg text-sm"
              >
                <option value="30days">30 Days</option>
                <option value="90days">90 Days</option>
                <option value="6months">6 Months</option>
                <option value="1year">1 Year</option>
              </select>
              
              <button
                onClick={() => refetch()}
                className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted"
              >
                <RefreshCw className="h-4 w-4" />
                Refresh
              </button>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Dashboard Navigation */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="border-b border-border"
      >
        <div className="px-6">
          <nav className="flex space-x-8">
            {[
              { key: 'overview', label: 'Executive Overview', icon: BarChart3 },
              { key: 'performance', label: 'Performance Metrics', icon: TrendingUp },
              { key: 'risk', label: 'Risk Analysis', icon: AlertTriangle },
              { key: 'compliance', label: 'Compliance', icon: Shield },
              { key: 'predictive', label: 'Predictive Insights', icon: Brain },
            ].map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveDashboard(tab.key as any)}
                className={`flex items-center gap-2 py-4 border-b-2 transition-colors ${
                  activeDashboard === tab.key
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
        {activeDashboard === 'overview' && <OverviewDashboard data={analyticsData} theme={currentTheme} />}
        {activeDashboard === 'performance' && <PerformanceDashboard data={analyticsData} theme={currentTheme} />}
        {activeDashboard === 'risk' && <RiskDashboard data={analyticsData} theme={currentTheme} />}
        {activeDashboard === 'compliance' && <ComplianceDashboard data={analyticsData} theme={currentTheme} />}
        {activeDashboard === 'predictive' && <PredictiveDashboard data={analyticsData} theme={currentTheme} />}
      </div>
    </div>
  );
}

// Executive Overview Dashboard
function OverviewDashboard({ data, theme }: { data: any; theme: any }) {
  return (
    <div className="space-y-6">
      {/* KPI Scorecards */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[
            {
              label: 'Performance Score',
              value: data?.performance_metrics?.performance_score || 0,
              icon: Award,
              trend: '+5.2%',
              color: theme.primary,
              format: 'score',
            },
            {
              label: 'TRIR Rate',
              value: data?.performance_metrics?.current_rates?.trir || 0,
              icon: TrendingDown,
              trend: '-12%',
              color: '#10b981',
              format: 'rate',
            },
            {
              label: 'Compliance Score',
              value: data?.compliance_metrics?.overall_compliance_score || 0,
              icon: Shield,
              trend: '+3.1%',
              color: '#3b82f6',
              format: 'percentage',
            },
            {
              label: 'ROI',
              value: data?.cost_analysis?.roi_analysis?.roi_percentage || 0,
              icon: DollarSign,
              trend: '+18%',
              color: '#8b5cf6',
              format: 'percentage',
            },
          ].map((kpi, index) => (
            <motion.div
              key={kpi.label}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.3 + index * 0.05 }}
              className="bg-card border border-border rounded-xl p-6"
            >
              <div className="flex items-center justify-between mb-4">
                <div className="p-2 rounded-lg" style={{ backgroundColor: `${kpi.color}20` }}>
                  <kpi.icon className="h-5 w-5" style={{ color: kpi.color }} />
                </div>
                <span className={`text-xs px-2 py-1 rounded-full ${
                  kpi.trend.startsWith('+') ? 'bg-success/20 text-success' : 'bg-danger/20 text-danger'
                }`}>
                  {kpi.trend}
                </span>
              </div>
              <div>
                <p className="text-2xl font-bold">
                  {kpi.format === 'percentage' ? `${kpi.value}%` : 
                   kpi.format === 'score' ? kpi.value :
                   kpi.format === 'rate' ? kpi.value.toFixed(2) : kpi.value}
                </p>
                <p className="text-sm text-muted-foreground">{kpi.label}</p>
              </div>
            </motion.div>
          ))}
        </div>
      </motion.div>

      {/* Combined Performance Chart */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Safety Performance Trends */}
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.4 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h3 className="text-lg font-semibold mb-4">Safety Performance Trends</h3>
          <ResponsiveContainer width="100%" height={300}>
            <ComposedChart data={data?.performance_metrics?.trir_trend || []}>
              <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} />
              <XAxis dataKey="date" stroke={theme.text} />
              <YAxis stroke={theme.text} />
              <Tooltip />
              <Legend />
              <Line
                type="monotone"
                dataKey="value"
                stroke={theme.primary}
                strokeWidth={3}
                name="TRIR"
              />
              <Line
                type="monotone"
                dataKey="benchmark"
                stroke="#ef4444"
                strokeDasharray="5 5"
                name="Industry Benchmark"
              />
              <Line
                type="monotone"
                dataKey="target"
                stroke="#10b981"
                strokeDasharray="5 5"
                name="Target"
              />
            </ComposedChart>
          </ResponsiveContainer>
        </motion.div>

        {/* Risk Heatmap */}
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.5 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h3 className="text-lg font-semibold mb-4">Risk Distribution Heatmap</h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={[
                  { name: 'Critical Risks', value: 15, fill: '#dc2626' },
                  { name: 'High Risks', value: 35, fill: '#f59e0b' },
                  { name: 'Medium Risks', value: 80, fill: '#3b82f6' },
                  { name: 'Low Risks', value: 120, fill: '#10b981' },
                ]}
                cx="50%"
                cy="50%"
                outerRadius={80}
                fill="#8884d8"
                dataKey="value"
              >
                {data?.risk_analysis?.risk_matrix?.map((entry: any, index: number) => (
                  <Cell key={`cell-${index}`} fill={entry.fill} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </motion.div>
      </div>

      {/* Cost Analysis */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.6 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h3 className="text-lg font-semibold mb-4">Cost Analysis & ROI</h3>
        <ResponsiveContainer width="100%" height={400}>
          <AreaChart data={data?.cost_analysis?.cost_trends?.monthly_costs || []}>
            <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} />
            <XAxis dataKey="month" stroke={theme.text} />
            <YAxis stroke={theme.text} />
            <Tooltip />
            <Legend />
            <Area
              type="monotone"
              dataKey="incident_costs"
              stackId="1"
              stroke="#ef4444"
              fill="#ef4444"
              fillOpacity={0.6}
              name="Incident Costs"
            />
            <Area
              type="monotone"
              dataKey="prevention_costs"
              stackId="1"
              stroke="#10b981"
              fill="#10b981"
              fillOpacity={0.6}
              name="Prevention Costs"
            />
          </AreaChart>
        </ResponsiveContainer>
      </motion.div>
    </div>
  );
}

// Performance Dashboard
function PerformanceDashboard({ data, theme }: { data: any; theme: any }) {
  return (
    <div className="space-y-6">
      {/* Multi-Metric Performance */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h3 className="text-lg font-semibold mb-4">Comprehensive Performance Metrics</h3>
        <ResponsiveContainer width="100%" height={400}>
          <RadarChart data={[
            { metric: 'TRIR', value: 85, fullMark: 100 },
            { metric: 'LTIFR', value: 92, fullMark: 100 },
            { metric: 'DART', value: 78, fullMark: 100 },
            { metric: 'Compliance', value: 89, fullMark: 100 },
            { metric: 'Training', value: 94, fullMark: 100 },
            { metric: 'Investigations', value: 82, fullMark: 100 },
          ]}>
            <PolarGrid stroke={theme.grid} />
            <PolarAngleAxis dataKey="metric" stroke={theme.text} />
            <PolarRadiusAxis stroke={theme.text} />
            <Radar
              name="Performance"
              dataKey="value"
              stroke={theme.primary}
              fill={theme.primary}
              fillOpacity={0.6}
            />
            <Tooltip />
          </RadarChart>
        </ResponsiveContainer>
      </motion.div>

      {/* Incident Patterns Analysis */}
      <div className="grid gap-6 lg:grid-cols-2">
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.3 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h3 className="text-lg font-semibold mb-4">Incident Patterns by Time</h3>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={data?.safety_trends?.time_patterns || []}>
              <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} />
              <XAxis dataKey="hour" stroke={theme.text} />
              <YAxis stroke={theme.text} />
              <Tooltip />
              <Bar dataKey="count" fill={theme.primary} />
            </BarChart>
          </ResponsiveContainer>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.4 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h3 className="text-lg font-semibold mb-4">Severity Distribution</h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={[
                  { name: 'Fatal', value: 1, fill: '#dc2626' },
                  { name: 'Critical', value: 5, fill: '#f59e0b' },
                  { name: 'Major', value: 15, fill: '#3b82f6' },
                  { name: 'Minor', value: 79, fill: '#10b981' },
                ]}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                outerRadius={80}
                fill="#8884d8"
                dataKey="value"
              >
                {data?.performance_metrics?.severity_distribution?.map((entry: any, index: number) => (
                  <Cell key={`cell-${index}`} fill={entry.fill} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </motion.div>
      </div>
    </div>
  );
}

// Risk Dashboard
function RiskDashboard({ data, theme }: { data: any; theme: any }) {
  return (
    <div className="space-y-6">
      {/* Risk Matrix */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h3 className="text-lg font-semibold mb-4">Risk Matrix Analysis</h3>
        <ResponsiveContainer width="100%" height={400}>
          <ScatterChart data={data?.risk_analysis?.risk_matrix || []}>
            <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} />
            <XAxis dataKey="likelihood" stroke={theme.text} />
            <YAxis dataKey="severity" stroke={theme.text} />
            <Tooltip />
            <Scatter name="Risk Points" dataKey="count" fill={theme.primary} />
          </ScatterChart>
        </ResponsiveContainer>
      </motion.div>

      {/* Risk Trends */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.3 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h3 className="text-lg font-semibold mb-4">Risk Trends Over Time</h3>
        <ResponsiveContainer width="100%" height={300}>
          <AreaChart data={data?.risk_analysis?.risk_trends || []}>
            <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} />
            <XAxis dataKey="month" stroke={theme.text} />
            <YAxis stroke={theme.text} />
            <Tooltip />
            <Legend />
            <Area
              type="monotone"
              dataKey="high_risks"
              stackId="1"
              stroke="#dc2626"
              fill="#dc2626"
              fillOpacity={0.6}
              name="High Risks"
            />
            <Area
              type="monotone"
              dataKey="medium_risks"
              stackId="1"
              stroke="#f59e0b"
              fill="#f59e0b"
              fillOpacity={0.6}
              name="Medium Risks"
            />
          </AreaChart>
        </ResponsiveContainer>
      </motion.div>
    </div>
  );
}

// Compliance Dashboard
function ComplianceDashboard({ data, theme }: { data: any; theme: any }) {
  return (
    <div className="space-y-6">
      <div className="grid gap-6 lg:grid-cols-3">
        {[
          {
            title: 'Training Compliance',
            data: data?.compliance_metrics?.training,
            icon: Users,
            color: '#3b82f6',
          },
          {
            title: 'Inspection Compliance',
            data: data?.compliance_metrics?.inspections,
            icon: Eye,
            color: '#10b981',
          },
          {
            title: 'PPE Compliance',
            data: data?.compliance_metrics?.ppe,
            icon: Shield,
            color: '#f59e0b',
          },
        ].map((section, index) => (
          <motion.div
            key={section.title}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 + index * 0.1 }}
            className="bg-card border border-border rounded-xl p-6"
          >
            <div className="flex items-center gap-3 mb-4">
              <div className="p-2 rounded-lg" style={{ backgroundColor: `${section.color}20` }}>
                <section.icon className="h-5 w-5" style={{ color: section.color }} />
              </div>
              <h3 className="font-semibold">{section.title}</h3>
            </div>
            
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Completion Rate</span>
                <span className="font-medium">{section.data?.completion_rate || 0}%</span>
              </div>
              <div className="w-full bg-muted rounded-full h-2">
                <div
                  className="h-2 rounded-full transition-all duration-500"
                  style={{
                    width: `${section.data?.completion_rate || 0}%`,
                    backgroundColor: section.color,
                  }}
                />
              </div>
            </div>
          </motion.div>
        ))}
      </div>
    </div>
  );
}

// Predictive Dashboard
function PredictiveDashboard({ data, theme }: { data: any; theme: any }) {
  return (
    <div className="space-y-6">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h3 className="text-lg font-semibold mb-4">Predictive Risk Forecast</h3>
        <div className="grid gap-4 md:grid-cols-2">
          <div className="p-4 border border-border rounded-lg">
            <h4 className="font-medium mb-2">Next Month Risk Level</h4>
            <div className="flex items-center gap-2">
              <div className={`w-3 h-3 rounded-full ${
                data?.predictive_insights?.incident_prediction?.next_month_risk === 'high' ? 'bg-danger' :
                data?.predictive_insights?.incident_prediction?.next_month_risk === 'medium' ? 'bg-warning' : 'bg-success'
              }`} />
              <span className="capitalize">
                {data?.predictive_insights?.incident_prediction?.next_month_risk}
              </span>
            </div>
            <p className="text-sm text-muted-foreground mt-1">
              Confidence: {data?.predictive_insights?.incident_prediction?.confidence}%
            </p>
          </div>
          
          <div className="p-4 border border-border rounded-lg">
            <h4 className="font-medium mb-2">Compliance Gaps</h4>
            <div className="space-y-1">
              <div className="flex justify-between text-sm">
                <span>Training</span>
                <span>{data?.predictive_insights?.compliance_prediction?.training_gaps} gaps</span>
              </div>
              <div className="flex justify-between text-sm">
                <span>Inspections</span>
                <span>{data?.predictive_insights?.compliance_prediction?.inspection_gaps} gaps</span>
              </div>
            </div>
          </div>
        </div>
      </motion.div>
    </div>
  );
}
