import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  Shield,
  FileText,
  Calendar,
  AlertTriangle,
  TrendingUp,
  TrendingDown,
  Download,
  Eye,
  Clock,
  Users,
  HardHat,
  Activity,
  CheckCircle,
  XCircle,
  AlertCircle,
  Filter,
  Search,
  Printer,
  FileSpreadsheet,
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
  Legend,
  ResponsiveContainer,
  Area,
  AreaChart,
} from 'recharts';
import { api } from '../lib/api';
import { SkeletonCard } from '../components/skeleton';

export default function OshaCompliancePage() {
  const { t } = useTranslation();
  const [selectedTimeframe, setSelectedTimeframe] = useState('30days');
  const [selectedProject, setSelectedProject] = useState('all');
  const [activeTab, setActiveTab] = useState<'dashboard' | 'reports' | 'calendar' | 'inspections'>('dashboard');

  // Fetch OSHA compliance data
  const { data: complianceData, isLoading } = useQuery({
    queryKey: ['osha-compliance', selectedTimeframe, selectedProject],
    queryFn: async () => {
      const response = await api.get(`/osha-compliance/dashboard?timeframe=${selectedTimeframe}&project_id=${selectedProject !== 'all' ? selectedProject : ''}`);
      return response.data.data;
    },
    refetchInterval: 60000, // Refresh every minute
  });

  const getComplianceColor = (score: number) => {
    if (score >= 90) return 'oklch(65% 0.15 145)'; // Success green
    if (score >= 75) return 'oklch(75% 0.12 85)'; // Warning amber
    return 'oklch(55% 0.18 25)'; // Danger red
  };

  const getComplianceLevel = (score: number) => {
    if (score >= 90) return 'Excellent';
    if (score >= 75) return 'Good';
    if (score >= 60) return 'Fair';
    return 'Critical';
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
              <h1 className="text-3xl font-bold text-foreground">OSHA Compliance</h1>
              <p className="text-muted-foreground">
                Regulatory compliance and safety metrics tracking
              </p>
            </div>
            
            <div className="flex items-center gap-4">
              {/* Timeframe Selector */}
              <select
                value={selectedTimeframe}
                onChange={(e) => setSelectedTimeframe(e.target.value)}
                className="px-4 py-2 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="7days">Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
                <option value="90days">Last 90 Days</option>
                <option value="1year">Last Year</option>
              </select>

              {/* Export Options */}
              <div className="flex gap-2">
                <button className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted">
                  <FileSpreadsheet className="h-4 w-4" />
                  Export Data
                </button>
                <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                  <FileText className="h-4 w-4" />
                  OSHA 300
                </button>
              </div>
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
              { key: 'dashboard', label: 'Dashboard', icon: Activity },
              { key: 'reports', label: 'OSHA Reports', icon: FileText },
              { key: 'calendar', label: 'Compliance Calendar', icon: Calendar },
              { key: 'inspections', label: 'Inspections', icon: Eye },
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
        {activeTab === 'dashboard' && <DashboardContent data={complianceData} isLoading={isLoading} />}
        {activeTab === 'reports' && <ReportsContent />}
        {activeTab === 'calendar' && <CalendarContent />}
        {activeTab === 'inspections' && <InspectionsContent />}
      </div>
    </div>
  );
}

// Dashboard Content Component
function DashboardContent({ data, isLoading }: { data: any; isLoading: boolean }) {
  const getComplianceColor = (score: number) => {
    if (score >= 90) return 'oklch(65% 0.15 145)';
    if (score >= 75) return 'oklch(75% 0.12 85)';
    return 'oklch(55% 0.18 25)';
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[1, 2, 3, 4].map((i) => <SkeletonCard key={i} />)}
        </div>
        <SkeletonCard />
        <SkeletonCard />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* OSHA Metrics Overview */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          {[
            {
              label: 'TRIR',
              value: data?.metrics?.trir || 0,
              icon: Shield,
              target: 3.0,
              unit: '',
              description: 'Total Recordable Incident Rate',
            },
            {
              label: 'LTIFR',
              value: data?.metrics?.ltifr || 0,
              icon: AlertTriangle,
              target: 2.0,
              unit: '',
              description: 'Lost Time Injury Frequency Rate',
            },
            {
              label: 'DART',
              value: data?.metrics?.dart || 0,
              icon: Activity,
              target: 2.0,
              unit: '',
              description: 'Days Away, Restricted, or Transferred',
            },
            {
              label: 'Severity Rate',
              value: data?.metrics?.severity_rate || 0,
              icon: TrendingUp,
              target: 1.5,
              unit: ' days',
              description: 'Average days per lost time case',
            },
            {
              label: 'Compliance Score',
              value: data?.metrics?.compliance_score || 0,
              icon: CheckCircle,
              target: 90,
              unit: '%',
              description: 'Overall compliance rating',
            },
          ].map((metric, index) => (
            <motion.div
              key={metric.label}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.3 + index * 0.05 }}
              className="bg-card border border-border rounded-xl p-6"
            >
              <div className="flex items-center justify-between mb-4">
                <div className="p-2 rounded-lg bg-primary/10">
                  <metric.icon className="h-5 w-5 text-primary" />
                </div>
                <div className={`text-xs px-2 py-1 rounded-full ${
                  metric.value <= metric.target
                    ? 'bg-success/20 text-success'
                    : 'bg-danger/20 text-danger'
                }`}>
                  Target: {metric.target}{metric.unit}
                </div>
              </div>
              
              <div className="mb-2">
                <p className="text-2xl font-bold">
                  {typeof metric.value === 'number' ? metric.value.toFixed(2) : metric.value}
                  {metric.unit}
                </p>
                <p className="text-sm font-medium">{metric.label}</p>
              </div>
              
              <p className="text-xs text-muted-foreground">{metric.description}</p>
            </motion.div>
          ))}
        </div>
      </motion.div>

      {/* Compliance Status by Category */}
      <div className="grid gap-6 lg:grid-cols-2">
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.4 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h2 className="text-xl font-semibold mb-6">Compliance Status by Category</h2>
          
          <div className="space-y-4">
            {Object.entries(data?.compliance_status || {}).map(([category, status]: [string, any]) => (
              <div key={category} className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-medium capitalize">{category}</span>
                  <span className={`text-sm font-medium ${
                    status.compliance_rate >= 90 ? 'text-success' :
                    status.compliance_rate >= 75 ? 'text-warning' : 'text-danger'
                  }`}>
                    {status.compliance_rate}%
                  </span>
                </div>
                
                <div className="h-2 rounded-full bg-muted overflow-hidden">
                  <motion.div
                    initial={{ width: 0 }}
                    animate={{ width: `${status.compliance_rate}%` }}
                    transition={{ duration: 1, delay: 0.5 }}
                    className={`h-full rounded-full ${
                      status.compliance_rate >= 90 ? 'bg-success' :
                      status.compliance_rate >= 75 ? 'bg-warning' : 'bg-danger'
                    }`}
                  />
                </div>
                
                <div className="flex justify-between text-xs text-muted-foreground">
                  <span>Completed: {status.completed || status.valid || status.active}</span>
                  <span>Overdue: {status.overdue || status.expired}</span>
                </div>
              </div>
            ))}
          </div>
        </motion.div>

        {/* Incident Trends */}
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.5 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h2 className="text-xl font-semibold mb-6">Incident Trends</h2>
          
          <ResponsiveContainer width="100%" height={250}>
            <AreaChart data={[
              { month: 'Jan', incidents: 12, nearMisses: 45 },
              { month: 'Feb', incidents: 8, nearMisses: 38 },
              { month: 'Mar', incidents: 15, nearMisses: 52 },
              { month: 'Apr', incidents: 10, nearMisses: 41 },
              { month: 'May', incidents: 6, nearMisses: 35 },
              { month: 'Jun', incidents: 9, nearMisses: 43 },
            ]}>
              <CartesianGrid strokeDasharray="3 3" stroke="oklch(88% 0.015 60)" />
              <XAxis dataKey="month" stroke="oklch(60% 0.02 60)" />
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
                dataKey="incidents"
                stroke="oklch(55% 0.18 25)"
                fill="oklch(55% 0.18 25)"
                fillOpacity={0.1}
                strokeWidth={2}
              />
              <Area
                type="monotone"
                dataKey="nearMisses"
                stroke="oklch(75% 0.08 250)"
                fill="oklch(75% 0.08 250)"
                fillOpacity={0.1}
                strokeWidth={2}
              />
            </AreaChart>
          </ResponsiveContainer>
        </motion.div>
      </div>

      {/* High Risk Areas */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.6 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h2 className="text-xl font-semibold mb-6">High Risk Areas</h2>
        
        <div className="space-y-3">
          {data?.high_risk_areas?.slice(0, 5).map((area: any, index: number) => (
            <motion.div
              key={area.project_id}
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: 0.7 + index * 0.1 }}
              className="flex items-center justify-between p-4 border border-border rounded-lg"
            >
              <div className="flex-1">
                <h3 className="font-medium">{area.project_name}</h3>
                <p className="text-sm text-muted-foreground">{area.location}</p>
                <div className="flex gap-2 mt-2">
                  {area.risk_factors?.map((factor: string, i: number) => (
                    <span key={i} className="text-xs px-2 py-1 bg-danger/20 text-danger rounded-full">
                      {factor}
                    </span>
                  ))}
                </div>
              </div>
              
              <div className="text-right">
                <div className="text-2xl font-bold text-danger">{area.risk_score}</div>
                <div className="text-sm text-muted-foreground">Risk Score</div>
              </div>
            </motion.div>
          ))}
        </div>
      </motion.div>
    </div>
  );
}

// Reports Content Component
function ReportsContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">OSHA Regulatory Reports</h2>
        
        <div className="grid gap-4 md:grid-cols-2">
          {[
            {
              title: 'OSHA 300 Log',
              description: 'Detailed log of work-related injuries and illnesses',
              icon: FileText,
              action: 'Generate 300',
              year: new Date().getFullYear(),
            },
            {
              title: 'OSHA 300A Summary',
              description: 'Annual summary of work-related injuries and illnesses',
              icon: FileSpreadsheet,
              action: 'Generate 300A',
              year: new Date().getFullYear() - 1,
            },
          ].map((report, index) => (
            <motion.div
              key={report.title}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: index * 0.1 }}
              className="border border-border rounded-lg p-6"
            >
              <div className="flex items-start justify-between mb-4">
                <div className="p-2 rounded-lg bg-primary/10">
                  <report.icon className="h-6 w-6 text-primary" />
                </div>
                <span className="text-sm text-muted-foreground">{report.year}</span>
              </div>
              
              <h3 className="font-semibold mb-2">{report.title}</h3>
              <p className="text-sm text-muted-foreground mb-4">{report.description}</p>
              
              <div className="flex gap-2">
                <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                  <Download className="h-4 w-4" />
                  {report.action}
                </button>
                <button className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted">
                  <Printer className="h-4 w-4" />
                  Print
                </button>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </div>
  );
}

// Calendar Content Component
function CalendarContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">Compliance Calendar</h2>
        <div className="text-center py-12 text-muted-foreground">
          Calendar view coming soon
        </div>
      </div>
    </div>
  );
}

// Inspections Content Component
function InspectionsContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">Scheduled Inspections</h2>
        <div className="text-center py-12 text-muted-foreground">
          Inspection management coming soon
        </div>
      </div>
    </div>
  );
}
