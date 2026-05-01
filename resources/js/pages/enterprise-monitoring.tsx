import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  Activity,
  AlertTriangle,
  TrendingUp,
  TrendingDown,
  MapPin,
  Users,
  HardHat,
  Shield,
  Clock,
  Zap,
  Wind,
  Thermometer,
  CloudRain,
  Eye,
  Download,
  Filter,
  Calendar,
  BarChart3,
  PieChart,
  LineChart,
  Bell,
  Settings,
  Maximize2,
  Grid3x3,
} from 'lucide-react';
import {
  LineChart as RechartsLineChart,
  Line,
  AreaChart,
  Area,
  BarChart,
  Bar,
  PieChart as RechartsPieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  RadarChart,
  Radar,
  PolarGrid,
  PolarAngleAxis,
  PolarRadiusAxis,
} from 'recharts';
import { api } from '../lib/api';
import { SkeletonCard } from '../components/skeleton';

export default function EnterpriseMonitoringPage() {
  const { t } = useTranslation();
  const [selectedRegion, setSelectedRegion] = useState('all');
  const [selectedTimeRange, setSelectedTimeRange] = useState('24h');
  const [activeAlerts, setActiveAlerts] = useState(true);
  const [viewMode, setViewMode] = useState<'overview' | 'detailed' | 'map'>('overview');

  // Fetch enterprise-wide metrics
  const { data: enterpriseMetrics, isLoading: metricsLoading } = useQuery({
    queryKey: ['enterprise-metrics', selectedRegion, selectedTimeRange],
    queryFn: async () => {
      const response = await api.get(`/enterprise/monitoring?region=${selectedRegion}&timeframe=${selectedTimeRange}`);
      return response.data.data;
    },
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  // Fetch critical alerts
  const { data: criticalAlerts } = useQuery({
    queryKey: ['critical-alerts'],
    queryFn: async () => {
      const response = await api.get('/enterprise/critical-alerts');
      return response.data.data;
    },
    refetchInterval: 10000, // Refresh every 10 seconds
  });

  // Fetch project status overview
  const { data: projectStatus } = useQuery({
    queryKey: ['project-status', selectedRegion],
    queryFn: async () => {
      const response = await api.get(`/enterprise/project-status?region=${selectedRegion}`);
      return response.data.data;
    },
  });

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'critical': return 'oklch(55% 0.18 25)';
      case 'high': return 'oklch(65% 0.12 85)';
      case 'medium': return 'oklch(75% 0.08 250)';
      case 'low': return 'oklch(65% 0.15 145)';
      default: return 'oklch(60% 0.02 60)';
    }
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
              <h1 className="text-3xl font-bold text-foreground">Enterprise Monitoring</h1>
              <p className="text-muted-foreground">
                Real-time oversight of {enterpriseMetrics?.totalProjects || 100}+ projects nationwide
              </p>
            </div>
            
            <div className="flex items-center gap-4">
              {/* Region Filter */}
              <select
                value={selectedRegion}
                onChange={(e) => setSelectedRegion(e.target.value)}
                className="px-4 py-2 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="all">All Regions</option>
                <option value="northeast">Northeast</option>
                <option value="southeast">Southeast</option>
                <option value="midwest">Midwest</option>
                <option value="southwest">Southwest</option>
                <option value="west">West</option>
              </select>

              {/* Time Range */}
              <select
                value={selectedTimeRange}
                onChange={(e) => setSelectedTimeRange(e.target.value)}
                className="px-4 py-2 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="1h">Last Hour</option>
                <option value="24h">Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
              </select>

              {/* View Mode */}
              <div className="flex rounded-lg border border-border">
                <button
                  onClick={() => setViewMode('overview')}
                  className={`px-3 py-2 rounded-l-lg ${viewMode === 'overview' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <Grid3x3 className="h-4 w-4" />
                </button>
                <button
                  onClick={() => setViewMode('detailed')}
                  className={`px-3 py-2 ${viewMode === 'detailed' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <BarChart3 className="h-4 w-4" />
                </button>
                <button
                  onClick={() => setViewMode('map')}
                  className={`px-3 py-2 rounded-r-lg ${viewMode === 'map' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <MapPin className="h-4 w-4" />
                </button>
              </div>

              {/* Alerts Toggle */}
              <button
                onClick={() => setActiveAlerts(!activeAlerts)}
                className={`relative p-2 rounded-lg ${activeAlerts ? 'bg-warning/20 text-warning' : 'text-muted-foreground'}`}
              >
                <Bell className="h-5 w-5" />
                {criticalAlerts?.length > 0 && (
                  <span className="absolute -top-1 -right-1 h-3 w-3 bg-danger rounded-full" />
                )}
              </button>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Critical Alerts Banner */}
      <AnimatePresence>
        {activeAlerts && criticalAlerts?.length > 0 && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            className="bg-danger/10 border-l-4 border-danger"
          >
            <div className="px-6 py-4">
              <div className="flex items-center gap-4">
                <AlertTriangle className="h-5 w-5 text-danger" />
                <div className="flex-1">
                  <h3 className="font-semibold text-danger">Critical Alerts Active</h3>
                  <p className="text-sm text-danger/80">
                    {criticalAlerts.length} critical incidents require immediate attention
                  </p>
                </div>
                <button className="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90">
                  View All Alerts
                </button>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Main Content */}
      <div className="p-6 space-y-6">
        {/* Key Metrics Overview */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            {[
              {
                label: 'Active Projects',
                value: enterpriseMetrics?.activeProjects || 0,
                icon: HardHat,
                change: enterpriseMetrics?.projectChange || 0,
                color: 'oklch(55% 0.10 250)',
              },
              {
                label: 'Total Workforce',
                value: enterpriseMetrics?.totalWorkforce || 0,
                icon: Users,
                change: enterpriseMetrics?.workforceChange || 0,
                color: 'oklch(65% 0.15 145)',
              },
              {
                label: 'Safety Incidents',
                value: enterpriseMetrics?.incidentsToday || 0,
                icon: AlertTriangle,
                change: enterpriseMetrics?.incidentChange || 0,
                color: 'oklch(55% 0.18 25)',
              },
              {
                label: 'TRIR Rate',
                value: enterpriseMetrics?.trir || 0,
                icon: Shield,
                change: enterpriseMetrics?.trirChange || 0,
                color: 'oklch(75% 0.08 250)',
              },
              {
                label: 'Compliance Rate',
                value: `${enterpriseMetrics?.complianceRate || 0}%`,
                icon: Eye,
                change: enterpriseMetrics?.complianceChange || 0,
                color: 'oklch(65% 0.12 85)',
              },
              {
                label: 'High Risk Projects',
                value: enterpriseMetrics?.highRiskProjects || 0,
                icon: Activity,
                change: enterpriseMetrics?.riskChange || 0,
                color: 'oklch(65% 0.08 250)',
              },
            ].map((metric, index) => (
              <motion.div
                key={metric.label}
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.2 + index * 0.05 }}
                className="bg-card border border-border rounded-xl p-6 hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center justify-between mb-4">
                  <div
                    className="p-2 rounded-lg"
                    style={{ backgroundColor: `${metric.color}20` }}
                  >
                    <metric.icon className="h-5 w-5" style={{ color: metric.color }} />
                  </div>
                  <div className="flex items-center gap-1">
                    {metric.change > 0 ? (
                      <TrendingUp className="h-4 w-4 text-danger" />
                    ) : (
                      <TrendingDown className="h-4 w-4 text-success" />
                    )}
                    <span className={`text-xs ${metric.change > 0 ? 'text-danger' : 'text-success'}`}>
                      {Math.abs(metric.change)}%
                    </span>
                  </div>
                </div>
                <div>
                  <p className="text-2xl font-bold text-foreground">{metric.value}</p>
                  <p className="text-sm text-muted-foreground">{metric.label}</p>
                </div>
              </motion.div>
            ))}
          </div>
        </motion.div>

        {/* Main Dashboard Grid */}
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Safety Performance Trends */}
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.3 }}
            className="lg:col-span-2 bg-card border border-border rounded-xl p-6"
          >
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold">Safety Performance Trends</h2>
              <div className="flex gap-2">
                <button className="px-3 py-1 text-xs bg-primary/10 text-primary rounded-lg">
                  TRIR
                </button>
                <button className="px-3 py-1 text-xs text-muted-foreground hover:bg-muted rounded-lg">
                  LTIFR
                </button>
                <button className="px-3 py-1 text-xs text-muted-foreground hover:bg-muted rounded-lg">
                  Near Misses
                </button>
              </div>
            </div>

            {metricsLoading ? (
              <SkeletonCard />
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <AreaChart data={enterpriseMetrics?.safetyTrends || []}>
                  <CartesianGrid strokeDasharray="3 3" stroke="oklch(88% 0.015 60)" />
                  <XAxis dataKey="date" stroke="oklch(60% 0.02 60)" />
                  <YAxis stroke="oklch(60% 0.02 60)" />
                  <Tooltip
                    contentStyle={{
                      backgroundColor: 'oklch(98% 0.005 60)',
                      border: '1px solid oklch(88% 0.015 60)',
                      borderRadius: '8px',
                    }}
                  />
                  <Area
                    type="monotone"
                    dataKey="trir"
                    stroke="oklch(55% 0.18 25)"
                    fill="oklch(55% 0.18 25)"
                    fillOpacity={0.1}
                    strokeWidth={2}
                  />
                  <Area
                    type="monotone"
                    dataKey="ltifr"
                    stroke="oklch(75% 0.08 250)"
                    fill="oklch(75% 0.08 250)"
                    fillOpacity={0.1}
                    strokeWidth={2}
                  />
                </AreaChart>
              </ResponsiveContainer>
            )}
          </motion.div>

          {/* Regional Risk Distribution */}
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.4 }}
            className="bg-card border border-border rounded-xl p-6"
          >
            <h2 className="text-xl font-semibold mb-6">Regional Risk Distribution</h2>

            {metricsLoading ? (
              <SkeletonCard />
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <RechartsPieChart>
                  <Pie
                    data={enterpriseMetrics?.regionalRisk || []}
                    cx="50%"
                    cy="50%"
                    innerRadius={60}
                    outerRadius={100}
                    paddingAngle={2}
                    dataKey="value"
                  >
                    {enterpriseMetrics?.regionalRisk?.map((entry: any, index: number) => (
                      <Cell key={`cell-${index}`} fill={getSeverityColor(entry.severity)} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{
                      backgroundColor: 'oklch(98% 0.005 60)',
                      border: '1px solid oklch(88% 0.015 60)',
                      borderRadius: '8px',
                    }}
                  />
                </RechartsPieChart>
              </ResponsiveContainer>
            )}

            <div className="mt-4 space-y-2">
              {enterpriseMetrics?.regionalRisk?.slice(0, 4).map((region: any, index: number) => (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div
                      className="w-3 h-3 rounded-full"
                      style={{ backgroundColor: getSeverityColor(region.severity) }}
                    />
                    <span className="text-sm">{region.name}</span>
                  </div>
                  <span className="text-sm font-medium">{region.value} projects</span>
                </div>
              ))}
            </div>
          </motion.div>
        </div>

        {/* Project Status Matrix */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.5 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-semibold">Project Status Matrix</h2>
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-success" />
                <span className="text-sm">Excellent</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-warning" />
                <span className="text-sm">Good</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-danger" />
                <span className="text-sm">Critical</span>
              </div>
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {projectStatus?.projects?.slice(0, 9).map((project: any, index: number) => (
              <motion.div
                key={project.id}
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.6 + index * 0.05 }}
                className="border border-border rounded-lg p-4 hover:shadow-md transition-shadow"
              >
                <div className="flex items-center justify-between mb-3">
                  <div>
                    <h3 className="font-medium">{project.name}</h3>
                    <p className="text-sm text-muted-foreground">{project.location}</p>
                  </div>
                  <div
                    className={`w-3 h-3 rounded-full ${
                      project.status === 'excellent' ? 'bg-success' :
                      project.status === 'good' ? 'bg-warning' : 'bg-danger'
                    }`}
                  />
                </div>
                
                <div className="grid grid-cols-3 gap-2 text-xs">
                  <div>
                    <p className="text-muted-foreground">TRIR</p>
                    <p className="font-medium">{project.trir}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Workers</p>
                    <p className="font-medium">{project.workforce}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Days</p>
                    <p className="font-medium">{project.daysWithoutIncident}</p>
                  </div>
                </div>

                {project.alerts > 0 && (
                  <div className="mt-3 flex items-center gap-2 text-xs text-danger">
                    <AlertTriangle className="h-3 w-3" />
                    <span>{project.alerts} active alerts</span>
                  </div>
                )}
              </motion.div>
            ))}
          </div>
        </motion.div>

        {/* Environmental Monitoring */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.7 }}
          className="grid gap-6 md:grid-cols-2"
        >
          <div className="bg-card border border-border rounded-xl p-6">
            <h2 className="text-xl font-semibold mb-6">Weather Conditions</h2>
            <div className="space-y-4">
              {enterpriseMetrics?.weatherConditions?.map((condition: any, index: number) => (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    {condition.type === 'temperature' && <Thermometer className="h-5 w-5 text-warning" />}
                    {condition.type === 'wind' && <Wind className="h-5 w-5 text-info" />}
                    {condition.type === 'rain' && <CloudRain className="h-5 w-5 text-primary" />}
                    <div>
                      <p className="font-medium">{condition.location}</p>
                      <p className="text-sm text-muted-foreground">{condition.condition}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-medium">{condition.value}</p>
                    <p className="text-xs text-muted-foreground">{condition.impact}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="bg-card border border-border rounded-xl p-6">
            <h2 className="text-xl font-semibold mb-6">Compliance Deadlines</h2>
            <div className="space-y-3">
              {enterpriseMetrics?.upcomingDeadlines?.slice(0, 5).map((deadline: any, index: number) => (
                <div key={index} className="flex items-center justify-between p-3 border border-border rounded-lg">
                  <div>
                    <p className="font-medium">{deadline.title}</p>
                    <p className="text-sm text-muted-foreground">{deadline.type}</p>
                  </div>
                  <div className="text-right">
                    <p className={`font-medium ${
                      deadline.daysRemaining <= 7 ? 'text-danger' :
                      deadline.daysRemaining <= 30 ? 'text-warning' : 'text-success'
                    }`}>
                      {deadline.daysRemaining} days
                    </p>
                    <p className="text-xs text-muted-foreground">{deadline.date}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  );
}
