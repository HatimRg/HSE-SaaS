import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  Search,
  AlertTriangle,
  Clock,
  CheckCircle,
  FileText,
  Users,
  Calendar,
  TrendingUp,
  Download,
  Plus,
  Eye,
  Edit,
  Trash2,
  Filter,
  BarChart3,
  Target,
  Shield,
  Activity,
} from 'lucide-react';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
} from 'recharts';
import { api } from '../lib/api';
import { SkeletonCard } from '../components/skeleton';

export default function IncidentInvestigationPage() {
  const { t } = useTranslation();
  const [selectedProject, setSelectedProject] = useState('all');
  const [selectedStatus, setSelectedStatus] = useState('all');
  const [selectedSeverity, setSelectedSeverity] = useState('all');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [activeTab, setActiveTab] = useState<'investigations' | 'analytics' | 'templates'>('investigations');

  // Fetch investigation data
  const { data: investigationData, isLoading, refetch } = useQuery({
    queryKey: ['incident-investigation', selectedProject, selectedStatus, selectedSeverity],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (selectedProject !== 'all') params.append('project_id', selectedProject);
      if (selectedStatus !== 'all') params.append('status', selectedStatus);
      if (selectedSeverity !== 'all') params.append('severity', selectedSeverity);
      
      const response = await api.get(`/incident-investigation/dashboard?${params}`);
      return response.data.data;
    },
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'closed': return 'oklch(65% 0.15 145)';
      case 'approved': return 'oklch(65% 0.08 250)';
      case 'under_review': return 'oklch(75% 0.12 85)';
      case 'in_progress': return 'oklch(75% 0.08 250)';
      default: return 'oklch(60% 0.02 60)';
    }
  };

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'fatal': return 'oklch(55% 0.18 25)';
      case 'critical': return 'oklch(65% 0.12 85)';
      case 'major': return 'oklch(75% 0.08 250)';
      case 'minor': return 'oklch(65% 0.15 145)';
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
              <h1 className="text-3xl font-bold text-foreground">Incident Investigation</h1>
              <p className="text-muted-foreground">
                Systematic investigation and root cause analysis
              </p>
            </div>
            
            <div className="flex items-center gap-4">
              {/* Filters */}
              <div className="flex gap-2">
                <select
                  value={selectedProject}
                  onChange={(e) => setSelectedProject(e.target.value)}
                  className="px-3 py-2 bg-background border border-border rounded-lg text-sm"
                >
                  <option value="all">All Projects</option>
                </select>
                
                <select
                  value={selectedStatus}
                  onChange={(e) => setSelectedStatus(e.target.value)}
                  className="px-3 py-2 bg-background border border-border rounded-lg text-sm"
                >
                  <option value="all">All Status</option>
                  <option value="in_progress">In Progress</option>
                  <option value="under_review">Under Review</option>
                  <option value="approved">Approved</option>
                  <option value="closed">Closed</option>
                </select>
                
                <select
                  value={selectedSeverity}
                  onChange={(e) => setSelectedSeverity(e.target.value)}
                  className="px-3 py-2 bg-background border border-border rounded-lg text-sm"
                >
                  <option value="all">All Severities</option>
                  <option value="fatal">Fatal</option>
                  <option value="critical">Critical</option>
                  <option value="major">Major</option>
                  <option value="minor">Minor</option>
                </select>
              </div>

              {/* Actions */}
              <button
                onClick={() => setShowCreateModal(true)}
                className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
              >
                <Plus className="h-4 w-4" />
                New Investigation
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
              { key: 'investigations', label: 'Investigations', icon: Search },
              { key: 'analytics', label: 'Analytics', icon: BarChart3 },
              { key: 'templates', label: 'Templates', icon: FileText },
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
        {activeTab === 'investigations' && <InvestigationsContent data={investigationData} isLoading={isLoading} />}
        {activeTab === 'analytics' && <AnalyticsContent />}
        {activeTab === 'templates' && <TemplatesContent />}
      </div>

      {/* Create Investigation Modal */}
      {showCreateModal && (
        <CreateInvestigationModal
          onClose={() => setShowCreateModal(false)}
          onSuccess={() => {
            setShowCreateModal(false);
            refetch();
          }}
        />
      )}
    </div>
  );
}

// Investigations Content Component
function InvestigationsContent({ data, isLoading }: { data: any; isLoading: boolean }) {
  return (
    <div className="space-y-6">
      {/* Statistics Overview */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[
            {
              label: 'Total Investigations',
              value: data?.statistics?.total_investigations || 0,
              icon: FileText,
              color: 'oklch(55% 0.10 250)',
            },
            {
              label: 'Average Duration',
              value: `${data?.statistics?.average_duration_days || 0} days`,
              icon: Clock,
              color: 'oklch(75% 0.12 85)',
            },
            {
              label: 'Overdue',
              value: data?.statistics?.overdue_investigations || 0,
              icon: AlertTriangle,
              color: 'oklch(55% 0.18 25)',
            },
            {
              label: 'Completion Rate',
              value: `${data?.statistics?.completion_rate || 0}%`,
              icon: Target,
              color: 'oklch(65% 0.15 145)',
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
              </div>
              <div>
                <p className="text-2xl font-bold">{stat.value}</p>
                <p className="text-sm text-muted-foreground">{stat.label}</p>
              </div>
            </motion.div>
          ))}
        </div>
      </motion.div>

      {/* Pending and Overdue Investigations */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Pending Investigations */}
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.4 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h2 className="text-xl font-semibold mb-6">Pending Investigations</h2>
          
          {isLoading ? (
            <SkeletonCard />
          ) : (
            <div className="space-y-3">
              {data?.pending_investigations?.slice(0, 5).map((investigation: any, index: number) => (
                <motion.div
                  key={investigation.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.5 + index * 0.1 }}
                  className="flex items-center justify-between p-3 border border-border rounded-lg"
                >
                  <div className="flex-1">
                    <h3 className="font-medium">{investigation.incident?.title}</h3>
                    <p className="text-sm text-muted-foreground">{investigation.incident?.project?.name}</p>
                    <div className="flex items-center gap-2 mt-1">
                      <span className="text-xs text-muted-foreground">
                        Investigator: {investigation.investigator?.name}
                      </span>
                      <span className="text-xs text-muted-foreground">
                        Due: {new Date(investigation.due_date).toLocaleDateString()}
                      </span>
                    </div>
                  </div>
                  
                  <div className="text-right">
                    <span className="text-xs px-2 py-1 bg-warning/20 text-warning rounded-full">
                      {investigation.days_until_due} days left
                    </span>
                  </div>
                </motion.div>
              ))}
            </div>
          )}
        </motion.div>

        {/* Overdue Investigations */}
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: 0.5 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h2 className="text-xl font-semibold mb-6">Overdue Investigations</h2>
          
          {isLoading ? (
            <SkeletonCard />
          ) : (
            <div className="space-y-3">
              {data?.overdue_investigations?.slice(0, 5).map((investigation: any, index: number) => (
                <motion.div
                  key={investigation.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.6 + index * 0.1 }}
                  className="flex items-center justify-between p-3 border border-danger/20 rounded-lg bg-danger/5"
                >
                  <div className="flex-1">
                    <h3 className="font-medium">{investigation.incident?.title}</h3>
                    <p className="text-sm text-muted-foreground">{investigation.incident?.project?.name}</p>
                    <div className="flex items-center gap-2 mt-1">
                      <span className="text-xs text-muted-foreground">
                        Investigator: {investigation.investigator?.name}
                      </span>
                      <span className="text-xs text-danger">
                        {investigation.days_overdue} days overdue
                      </span>
                    </div>
                  </div>
                  
                  <button className="px-3 py-1 text-xs bg-danger text-white rounded-lg hover:bg-danger/90">
                    Escalate
                  </button>
                </motion.div>
              ))}
            </div>
          )}
        </motion.div>
      </div>

      {/* Investigations List */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.7 }}
        className="bg-card border border-border rounded-xl p-6"
      >
        <h2 className="text-xl font-semibold mb-6">All Investigations</h2>
        
        {isLoading ? (
          <div className="space-y-4">
            {[1, 2, 3, 4, 5].map((i) => <SkeletonCard key={i} />)}
          </div>
        ) : (
          <div className="space-y-4">
            {data?.investigations?.data?.map((investigation: any, index: number) => (
              <InvestigationCard
                key={investigation.id}
                investigation={investigation}
                index={index}
              />
            ))}
          </div>
        )}
      </motion.div>
    </div>
  );
}

// Investigation Card Component
function InvestigationCard({ investigation, index }: {
  investigation: any;
  index: number;
}) {
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'closed': return 'oklch(65% 0.15 145)';
      case 'approved': return 'oklch(65% 0.08 250)';
      case 'under_review': return 'oklch(75% 0.12 85)';
      case 'in_progress': return 'oklch(75% 0.08 250)';
      default: return 'oklch(60% 0.02 60)';
    }
  };

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'fatal': return 'oklch(55% 0.18 25)';
      case 'critical': return 'oklch(65% 0.12 85)';
      case 'major': return 'oklch(75% 0.08 250)';
      case 'minor': return 'oklch(65% 0.15 145)';
      default: return 'oklch(60% 0.02 60)';
    }
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.1 }}
      className="border border-border rounded-lg p-6"
    >
      <div className="flex items-start justify-between mb-4">
        <div className="flex-1">
          <div className="flex items-center gap-3 mb-2">
            <span
              className="px-2 py-1 text-xs rounded-full text-white"
              style={{ backgroundColor: getSeverityColor(investigation.incident?.severity) }}
            >
              {investigation.incident?.severity?.toUpperCase()}
            </span>
            <span
              className="px-2 py-1 text-xs rounded-full text-white"
              style={{ backgroundColor: getStatusColor(investigation.status) }}
            >
              {investigation.status.replace('_', ' ').toUpperCase()}
            </span>
            <h3 className="font-medium">{investigation.incident?.title}</h3>
          </div>
          
          <p className="text-sm text-muted-foreground mb-3">
            {investigation.location_details}
          </p>
          
          <div className="flex items-center gap-4 text-xs text-muted-foreground">
            <span>Project: {investigation.incident?.project?.name}</span>
            <span>Investigator: {investigation.investigator?.name}</span>
            <span>Created: {new Date(investigation.created_at).toLocaleDateString()}</span>
          </div>
        </div>
        
        <div className="flex items-center gap-2">
          <button className="p-2 rounded hover:bg-muted">
            <Eye className="h-4 w-4" />
          </button>
          <button className="p-2 rounded hover:bg-muted">
            <Download className="h-4 w-4" />
          </button>
        </div>
      </div>
      
      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <div className="flex items-center gap-4">
          <span>Witnesses: {investigation.witnesses?.length || 0}</span>
          <span>Root Causes: {investigation.root_causes?.length || 0}</span>
          <span>Corrective Actions: {investigation.corrective_actions?.length || 0}</span>
        </div>
        <span>Due: {new Date(investigation.due_date).toLocaleDateString()}</span>
      </div>
    </motion.div>
  );
}

// Analytics Content Component
function AnalyticsContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">Investigation Analytics</h2>
        <div className="text-center py-12 text-muted-foreground">
          Analytics dashboard coming soon
        </div>
      </div>
    </div>
  );
}

// Templates Content Component
function TemplatesContent() {
  return (
    <div className="space-y-6">
      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="text-xl font-semibold mb-6">Investigation Templates</h2>
        <div className="text-center py-12 text-muted-foreground">
          Template management coming soon
        </div>
      </div>
    </div>
  );
}

// Create Investigation Modal Component
function CreateInvestigationModal({ onClose, onSuccess }: {
  onClose: () => void;
  onSuccess: () => void;
}) {
  const [formData, setFormData] = useState({
    incident_id: '',
    investigator_id: '',
    investigation_date: new Date().toISOString().split('T')[0],
    location_details: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission
    onSuccess();
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-card rounded-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
      >
        <h2 className="text-xl font-semibold mb-6">Start Investigation</h2>
        
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">Incident</label>
            <select
              value={formData.incident_id}
              onChange={(e) => setFormData({ ...formData, incident_id: e.target.value })}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              required
            >
              <option value="">Select incident...</option>
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-2">Lead Investigator</label>
            <select
              value={formData.investigator_id}
              onChange={(e) => setFormData({ ...formData, investigator_id: e.target.value })}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              required
            >
              <option value="">Select investigator...</option>
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-2">Investigation Date</label>
            <input
              type="date"
              value={formData.investigation_date}
              onChange={(e) => setFormData({ ...formData, investigation_date: e.target.value })}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              required
            />
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-2">Location Details</label>
            <textarea
              value={formData.location_details}
              onChange={(e) => setFormData({ ...formData, location_details: e.target.value })}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              rows={3}
              required
            />
          </div>
          
          <div className="flex gap-2 mt-6">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2 border border-border rounded-lg hover:bg-muted"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
              Start Investigation
            </button>
          </div>
        </form>
      </motion.div>
    </div>
  );
}
