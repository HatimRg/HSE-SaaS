import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  Building2,
  Users,
  Activity,
  TrendingUp,
  DollarSign,
  Server,
  AlertTriangle,
  CheckCircle,
  XCircle,
  Search,
  Filter,
  Plus,
  Edit,
  Trash2,
  Eye,
  Download,
  RefreshCw,
  Globe,
  Database,
  HardDrive,
  Cpu,
  Wifi,
  MessageSquare,
  Settings,
  LogOut,
  SwitchCamera,
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
  Area,
  AreaChart,
} from 'recharts';
import { api } from '../lib/api';
import { SkeletonCard } from '../components/skeleton';

export default function SuperAdminPage() {
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState<'dashboard' | 'companies' | 'users' | 'system' | 'broadcasts'>('dashboard');
  const [selectedCompany, setSelectedCompany] = useState<string | null>(null);

  // Fetch super admin dashboard data
  const { data: dashboardData, isLoading } = useQuery({
    queryKey: ['super-admin-dashboard'],
    queryFn: async () => {
      const response = await api.get('/super-admin/dashboard');
      return response.data.data;
    },
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  
  const getHealthColor = (status: string) => {
    switch (status) {
      case 'healthy': return 'oklch(65% 0.15 145)';
      case 'warning': return 'oklch(75% 0.12 85)';
      case 'unhealthy': return 'oklch(55% 0.18 25)';
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
              <h1 className="text-3xl font-bold text-foreground">Super Admin Dashboard</h1>
              <p className="text-muted-foreground">
                Global system administration and monitoring
              </p>
            </div>
            
            <div className="flex items-center gap-4">
              {selectedCompany && (
                <div className="flex items-center gap-2 px-3 py-2 bg-primary/10 text-primary rounded-lg">
                  <Building2 className="h-4 w-4" />
                  <span className="text-sm">Viewing: {selectedCompany}</span>
                  <button
                    onClick={() => setSelectedCompany(null)}
                    className="p-1 hover:bg-primary/20 rounded"
                  >
                    <XCircle className="h-3 w-3" />
                  </button>
                </div>
              )}
              
              <button className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted">
                <RefreshCw className="h-4 w-4" />
                Refresh
              </button>
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
              { key: 'companies', label: 'Companies', icon: Building2 },
              { key: 'users', label: 'Users', icon: Users },
              { key: 'system', label: 'System', icon: Server },
              { key: 'broadcasts', label: 'Broadcasts', icon: MessageSquare },
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
        {activeTab === 'dashboard' && <DashboardContent data={dashboardData} isLoading={isLoading} />}
        {activeTab === 'companies' && <CompaniesContent />}
        {activeTab === 'users' && <UsersContent />}
        {activeTab === 'system' && <SystemContent />}
        {activeTab === 'broadcasts' && <BroadcastsContent />}
      </div>
    </div>
  );
}

// Dashboard Content Component
function DashboardContent({ data, isLoading }: { data: any; isLoading: boolean }) {
  return (
    <div className="space-y-6">
      {/* Global Statistics */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
          {[
            {
              label: 'Total Companies',
              value: data?.stats?.total_companies || 0,
              icon: Building2,
              color: 'oklch(55% 0.10 250)',
              change: '+12%',
            },
            {
              label: 'Total Users',
              value: data?.stats?.total_users || 0,
              icon: Users,
              color: 'oklch(65% 0.15 145)',
              change: '+8%',
            },
            {
              label: 'Total Projects',
              value: data?.stats?.total_projects || 0,
              icon: Activity,
              color: 'oklch(75% 0.08 250)',
              change: '+15%',
            },
            {
              label: 'Active Today',
              value: data?.stats?.active_users_today || 0,
              icon: TrendingUp,
              color: 'oklch(75% 0.12 85)',
              change: '+5%',
            },
            {
              label: 'Revenue',
              value: `$${(data?.stats?.total_revenue || 0).toLocaleString()}`,
              icon: DollarSign,
              color: 'oklch(65% 0.08 250)',
              change: '+18%',
            },
            {
              label: 'Concurrent',
              value: data?.stats?.concurrent_users || 0,
              icon: Server,
              color: 'oklch(55% 0.18 25)',
              change: '-2%',
            },
          ].map((stat, index) => (
            <motion.div
              key={stat.label}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.3 + index * 0.05 }}
              className="bg-card border border-border rounded-xl p-6"
            >
              <div className="flex items-center justify-between mb-4">
                <div className="p-2 rounded-lg" style={{ backgroundColor: `${stat.color}20` }}>
                  <stat.icon className="h-5 w-5" style={{ color: stat.color }} />
                </div>
                <span className={`text-xs px-2 py-1 rounded-full ${
                  stat.change.startsWith('+') ? 'bg-success/20 text-success' : 'bg-danger/20 text-danger'
                }`}>
                  {stat.change}
                </span>
              </div>
              <div>
                <p className="text-2xl font-bold">{stat.value}</p>
                <p className="text-sm text-muted-foreground">{stat.label}</p>
              </div>
            </motion.div>
          ))}
        </div>
      </motion.div>

      {/* System Health */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* System Health Status */}
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.4 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h2 className="text-xl font-semibold mb-6">System Health</h2>
          
          <div className="space-y-4">
            {[
              { name: 'Database', status: data?.system_health?.database_status, icon: Database },
              { name: 'Cache', status: data?.system_health?.cache_status, icon: Server },
              { name: 'Storage', status: data?.system_health?.storage_status, icon: HardDrive },
              { name: 'API Response', status: data?.system_health?.api_response_time < 100 ? 'healthy' : 'warning', icon: Wifi },
            ].map((item, index) => (
              <div key={item.name} className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <item.icon className="h-5 w-5 text-muted-foreground" />
                  <span className="font-medium">{item.name}</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className={`w-2 h-2 rounded-full`} style={{
                    backgroundColor: item.status === 'healthy' ? 'oklch(65% 0.15 145)' :
                                   item.status === 'warning' ? 'oklch(75% 0.12 85)' : 'oklch(55% 0.18 25)'
                  }} />
                  <span className="text-sm capitalize">{item.status}</span>
                </div>
              </div>
            ))}
          </div>
        </motion.div>

        {/* Recent Activity */}
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.5 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h2 className="text-xl font-semibold mb-6">Recent Activity</h2>
          
          <div className="space-y-3">
            {data?.recent_activity?.slice(0, 8).map((activity: any, index: number) => (
              <div key={index} className="flex items-center justify-between p-3 border border-border rounded-lg">
                <div className="flex-1">
                  <p className="text-sm font-medium">{activity.description}</p>
                  <p className="text-xs text-muted-foreground">
                    {activity.user_name} • {activity.company_name}
                  </p>
                </div>
                <span className="text-xs text-muted-foreground">
                  {new Date(activity.created_at).toLocaleTimeString()}
                </span>
              </div>
            ))}
          </div>
        </motion.div>
      </div>

      {/* Top Companies */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.6 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h2 className="text-xl font-semibold mb-6">Top Companies</h2>
        
        <div className="space-y-3">
          {data?.companies?.slice(0, 10).map((company: any, index: number) => (
            <div key={company.id} className="flex items-center justify-between p-4 border border-border rounded-lg">
              <div className="flex items-center gap-4">
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                  <Building2 className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <h3 className="font-medium">{company.name}</h3>
                  <p className="text-sm text-muted-foreground">{company.domain}</p>
                </div>
              </div>
              
              <div className="flex items-center gap-6 text-sm">
                <div className="text-center">
                  <p className="font-medium">{company.users_count}</p>
                  <p className="text-muted-foreground">Users</p>
                </div>
                <div className="text-center">
                  <p className="font-medium">{company.projects_count}</p>
                  <p className="text-muted-foreground">Projects</p>
                </div>
                <span className={`px-2 py-1 text-xs rounded-full text-white`} style={{
                  backgroundColor: company.status === 'active' ? 'oklch(65% 0.15 145)' :
                                   company.status === 'inactive' ? 'oklch(75% 0.12 85)' : 'oklch(55% 0.18 25)'
                }}>
                  {company.status}
                </span>
              </div>
            </div>
          ))}
        </div>
      </motion.div>
    </div>
  );
}

// Companies Content Component
function CompaniesContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">Company Management</h2>
        <div className="text-center py-12 text-muted-foreground">
          Company management interface coming soon
        </div>
      </div>
    </div>
  );
}

// Users Content Component
function UsersContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">User Management</h2>
        <div className="text-center py-12 text-muted-foreground">
          User management interface coming soon
        </div>
      </div>
    </div>
  );
}

// System Content Component
function SystemContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">System Monitoring</h2>
        <div className="text-center py-12 text-muted-foreground">
          System monitoring interface coming soon
        </div>
      </div>
    </div>
  );
}

// Broadcasts Content Component
function BroadcastsContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">System Broadcasts</h2>
        <div className="text-center py-12 text-muted-foreground">
          Broadcast management interface coming soon
        </div>
      </div>
    </div>
  );
}
