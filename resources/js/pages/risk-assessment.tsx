import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  AlertTriangle,
  Shield,
  TrendingUp,
  Calendar,
  User,
  FileText,
  Download,
  Plus,
  Eye,
  Edit,
  Trash2,
  Clock,
  CheckCircle,
  XCircle,
  AlertCircle,
  Filter,
  Search,
  BarChart3,
  Grid3x3,
  Activity,
} from 'lucide-react';
import {
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
  LineChart,
  Line,
} from 'recharts';
import { api } from '../lib/api';
import { SkeletonCard } from '../components/skeleton';

export default function RiskAssessmentPage() {
  const { t } = useTranslation();
  const [selectedProject, setSelectedProject] = useState('all');
  const [selectedSeverity, setSelectedSeverity] = useState('all');
  const [selectedStatus, setSelectedStatus] = useState('all');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [viewMode, setViewMode] = useState<'list' | 'matrix' | 'heatmap'>('list');

  // Fetch risk assessment data
  const { data: riskData, isLoading, refetch } = useQuery({
    queryKey: ['risk-assessment', selectedProject, selectedSeverity, selectedStatus],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (selectedProject !== 'all') params.append('project_id', selectedProject);
      if (selectedSeverity !== 'all') params.append('severity', selectedSeverity);
      if (selectedStatus !== 'all') params.append('status', selectedStatus);
      
      const response = await api.get(`/risk-assessment/dashboard?${params}`);
      return response.data.data;
    },
  });

  const getRiskColor = (level: string) => {
    switch (level) {
      case 'critical': return 'oklch(55% 0.18 25)';
      case 'high': return 'oklch(65% 0.12 85)';
      case 'medium': return 'oklch(75% 0.08 250)';
      case 'low': return 'oklch(65% 0.15 145)';
      default: return 'oklch(60% 0.02 60)';
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'mitigated': return 'oklch(65% 0.15 145)';
      case 'pending_review': return 'oklch(75% 0.12 85)';
      case 'approved': return 'oklch(65% 0.08 250)';
      case 'rejected': return 'oklch(55% 0.18 25)';
      case 'monitored': return 'oklch(75% 0.08 250)';
      case 'closed': return 'oklch(60% 0.02 60)';
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
              <h1 className="text-3xl font-bold text-foreground">Risk Assessment</h1>
              <p className="text-muted-foreground">
                Comprehensive risk management and mitigation tracking
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
                  value={selectedSeverity}
                  onChange={(e) => setSelectedSeverity(e.target.value)}
                  className="px-3 py-2 bg-background border border-border rounded-lg text-sm"
                >
                  <option value="all">All Levels</option>
                  <option value="critical">Critical</option>
                  <option value="high">High</option>
                  <option value="medium">Medium</option>
                  <option value="low">Low</option>
                </select>
                
                <select
                  value={selectedStatus}
                  onChange={(e) => setSelectedStatus(e.target.value)}
                  className="px-3 py-2 bg-background border border-border rounded-lg text-sm"
                >
                  <option value="all">All Status</option>
                  <option value="pending_review">Pending Review</option>
                  <option value="approved">Approved</option>
                  <option value="mitigated">Mitigated</option>
                  <option value="monitored">Monitored</option>
                  <option value="closed">Closed</option>
                </select>
              </div>

              {/* View Mode */}
              <div className="flex rounded-lg border border-border">
                <button
                  onClick={() => setViewMode('list')}
                  className={`px-3 py-2 rounded-l-lg ${viewMode === 'list' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <Grid3x3 className="h-4 w-4" />
                </button>
                <button
                  onClick={() => setViewMode('matrix')}
                  className={`px-3 py-2 ${viewMode === 'matrix' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <BarChart3 className="h-4 w-4" />
                </button>
                <button
                  onClick={() => setViewMode('heatmap')}
                  className={`px-3 py-2 rounded-r-lg ${viewMode === 'heatmap' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                >
                  <Activity className="h-4 w-4" />
                </button>
              </div>

              {/* Actions */}
              <button
                onClick={() => setShowCreateModal(true)}
                className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
              >
                <Plus className="h-4 w-4" />
                New Assessment
              </button>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Main Content */}
      <div className="p-6 space-y-6">
        {/* Statistics Overview */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {[
              {
                label: 'Total Assessments',
                value: riskData?.assessments?.total || 0,
                icon: FileText,
                color: 'oklch(55% 0.10 250)',
              },
              {
                label: 'Critical Risks',
                value: riskData?.high_priority_risks?.filter((r: any) => r.risk_level === 'critical').length || 0,
                icon: AlertTriangle,
                color: 'oklch(55% 0.18 25)',
              },
              {
                label: 'Mitigated',
                value: riskData?.assessments?.data?.filter((r: any) => r.status === 'mitigated').length || 0,
                icon: CheckCircle,
                color: 'oklch(65% 0.15 145)',
              },
              {
                label: 'Overdue Reviews',
                value: riskData?.overdue_assessments?.length || 0,
                icon: Clock,
                color: 'oklch(75% 0.12 85)',
              },
            ].map((stat, index) => (
              <motion.div
                key={stat.label}
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.2 + index * 0.05 }}
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

        {/* Risk Matrix View */}
        {viewMode === 'matrix' && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="bg-card border border-border rounded-xl p-6"
          >
            <h2 className="text-xl font-semibold mb-6">Risk Matrix</h2>
            <RiskMatrix data={riskData?.risk_matrix} />
          </motion.div>
        )}

        {/* Risk List View */}
        {viewMode === 'list' && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="bg-card border border-border rounded-xl p-6"
          >
            <h2 className="text-xl font-semibold mb-6">Risk Assessments</h2>
            
            {isLoading ? (
              <div className="space-y-4">
                {[1, 2, 3, 4, 5].map((i) => <SkeletonCard key={i} />)}
              </div>
            ) : (
              <div className="space-y-4">
                {riskData?.assessments?.data?.map((assessment: any, index: number) => (
                  <RiskAssessmentCard
                    key={assessment.id}
                    assessment={assessment}
                    onEdit={() => console.log('Edit assessment:', assessment.id)}
                    onDelete={() => console.log('Delete assessment:', assessment.id)}
                    index={index}
                  />
                ))}
              </div>
            )}
          </motion.div>
        )}

        {/* Risk Heat Map View */}
        {viewMode === 'heatmap' && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="bg-card border border-border rounded-xl p-6"
          >
            <h2 className="text-xl font-semibold mb-6">Risk Heat Map</h2>
            <RiskHeatMap />
          </motion.div>
        )}

        {/* High Priority Risks */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="bg-card border border-border rounded-xl p-6"
        >
          <h2 className="text-xl font-semibold mb-6">High Priority Risks</h2>
          
          <div className="space-y-3">
            {riskData?.high_priority_risks?.slice(0, 5).map((risk: any, index: number) => (
              <motion.div
                key={risk.id}
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: 0.5 + index * 0.1 }}
                className="flex items-center justify-between p-4 border border-border rounded-lg"
              >
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <span
                      className="px-2 py-1 text-xs rounded-full text-white"
                      style={{ backgroundColor: getRiskColor(risk.risk_level) }}
                    >
                      {risk.risk_level.toUpperCase()}
                    </span>
                    <h3 className="font-medium">{risk.title}</h3>
                  </div>
                  <p className="text-sm text-muted-foreground mb-2">{risk.description}</p>
                  <div className="flex items-center gap-4 text-xs text-muted-foreground">
                    <span>Project: {risk.project?.name}</span>
                    <span>Score: {risk.risk_score}</span>
                    <span>Assessor: {risk.assessor?.name}</span>
                  </div>
                </div>
                
                <div className="flex items-center gap-2">
                  <button className="p-2 rounded hover:bg-muted">
                    <Eye className="h-4 w-4" />
                  </button>
                  <button className="p-2 rounded hover:bg-muted">
                    <Edit className="h-4 w-4" />
                  </button>
                </div>
              </motion.div>
            ))}
          </div>
        </motion.div>
      </div>

      {/* Create Assessment Modal */}
      {showCreateModal && (
        <CreateAssessmentModal
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

// Risk Matrix Component
function RiskMatrix({ data }: { data: any }) {
  const getCellColor = (count: number) => {
    if (count === 0) return 'oklch(95% 0.01 60)';
    if (count <= 2) return 'oklch(65% 0.15 145)';
    if (count <= 5) return 'oklch(75% 0.12 85)';
    return 'oklch(55% 0.18 25)';
  };

  const likelihoodLabels = ['Very Rare', 'Rare', 'Possible', 'Likely', 'Very Likely'];
  const severityLabels = ['Minor', 'Moderate', 'Major', 'Severe', 'Catastrophic'];

  return (
    <div className="overflow-x-auto">
      <div className="min-w-[600px]">
        <table className="w-full">
          <thead>
            <tr>
              <th className="p-2 text-sm font-medium">Severity/Likelihood</th>
              {likelihoodLabels.map((label, index) => (
                <th key={index} className="p-2 text-sm font-medium text-center">{label}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {severityLabels.map((severityLabel, severityIndex) => (
              <tr key={severityIndex}>
                <td className="p-2 text-sm font-medium">{severityLabel}</td>
                {Array.from({ length: 5 }, (_, likelihoodIndex) => (
                  <td
                    key={likelihoodIndex}
                    className="p-2 text-center"
                    style={{
                      backgroundColor: getCellColor(data?.[severityIndex + 1]?.[likelihoodIndex + 1] || 0),
                    }}
                  >
                    <span className="text-sm font-medium">
                      {data?.[severityIndex + 1]?.[likelihoodIndex + 1] || 0}
                    </span>
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// Risk Assessment Card Component
function RiskAssessmentCard({ assessment, onEdit, onDelete, index }: {
  assessment: any;
  onEdit: () => void;
  onDelete: () => void;
  index: number;
}) {
  const getRiskColor = (level: string) => {
    switch (level) {
      case 'critical': return 'oklch(55% 0.18 25)';
      case 'high': return 'oklch(65% 0.12 85)';
      case 'medium': return 'oklch(75% 0.08 250)';
      case 'low': return 'oklch(65% 0.15 145)';
      default: return 'oklch(60% 0.02 60)';
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'mitigated': return 'oklch(65% 0.15 145)';
      case 'pending_review': return 'oklch(75% 0.12 85)';
      case 'approved': return 'oklch(65% 0.08 250)';
      case 'rejected': return 'oklch(55% 0.18 25)';
      case 'monitored': return 'oklch(75% 0.08 250)';
      case 'closed': return 'oklch(60% 0.02 60)';
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
              style={{ backgroundColor: getRiskColor(assessment.risk_level) }}
            >
              {assessment.risk_level.toUpperCase()}
            </span>
            <span
              className="px-2 py-1 text-xs rounded-full text-white"
              style={{ backgroundColor: getStatusColor(assessment.status) }}
            >
              {assessment.status.replace('_', ' ').toUpperCase()}
            </span>
            <h3 className="font-medium">{assessment.title}</h3>
          </div>
          
          <p className="text-sm text-muted-foreground mb-3">{assessment.description}</p>
          
          <div className="flex items-center gap-4 text-xs text-muted-foreground">
            <span>Score: {assessment.risk_score}</span>
            <span>Likelihood: {assessment.likelihood}</span>
            <span>Severity: {assessment.severity}</span>
            <span>Category: {assessment.risk_category}</span>
          </div>
        </div>
        
        <div className="flex items-center gap-2">
          <button
            onClick={onEdit}
            className="p-2 rounded hover:bg-muted"
          >
            <Edit className="h-4 w-4" />
          </button>
          <button
            onClick={onDelete}
            className="p-2 rounded hover:bg-muted text-destructive"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      </div>
      
      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <div className="flex items-center gap-4">
          <span>Project: {assessment.project?.name}</span>
          <span>Assessor: {assessment.assessor?.name}</span>
        </div>
        <span>Review: {new Date(assessment.review_date).toLocaleDateString()}</span>
      </div>
    </motion.div>
  );
}

// Risk Heat Map Component
function RiskHeatMap() {
  // Mock data for demonstration
  const projects = [
    { name: 'Project A', risk: 85, location: 'New York' },
    { name: 'Project B', risk: 45, location: 'Los Angeles' },
    { name: 'Project C', risk: 92, location: 'Chicago' },
    { name: 'Project D', risk: 23, location: 'Houston' },
    { name: 'Project E', risk: 67, location: 'Phoenix' },
  ];

  const getHeatColor = (risk: number) => {
    if (risk >= 80) return 'oklch(55% 0.18 25)';
    if (risk >= 60) return 'oklch(65% 0.12 85)';
    if (risk >= 40) return 'oklch(75% 0.12 85)';
    if (risk >= 20) return 'oklch(75% 0.08 250)';
    return 'oklch(65% 0.15 145)';
  };

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      {projects.map((project, index) => (
        <motion.div
          key={project.name}
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ delay: index * 0.1 }}
          className="border border-border rounded-lg p-4"
        >
          <div className="flex items-center justify-between mb-2">
            <h3 className="font-medium">{project.name}</h3>
            <span className="text-2xl font-bold" style={{ color: getHeatColor(project.risk) }}>
              {project.risk}
            </span>
          </div>
          <p className="text-sm text-muted-foreground mb-3">{project.location}</p>
          <div className="h-2 rounded-full overflow-hidden">
            <div
              className="h-full rounded-full transition-all duration-500"
              style={{
                width: `${project.risk}%`,
                backgroundColor: getHeatColor(project.risk),
              }}
            />
          </div>
        </motion.div>
      ))}
    </div>
  );
}

// Create Assessment Modal Component
function CreateAssessmentModal({ onClose, onSuccess }: {
  onClose: () => void;
  onSuccess: () => void;
}) {
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    risk_category: 'safety',
    likelihood: 3,
    severity: 3,
    affected_areas: [],
    affected_personnel: [],
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
        <h2 className="text-xl font-semibold mb-6">Create Risk Assessment</h2>
        
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">Title</label>
            <input
              type="text"
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              required
            />
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-2">Description</label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              rows={4}
              required
            />
          </div>
          
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-2">Risk Category</label>
              <select
                value={formData.risk_category}
                onChange={(e) => setFormData({ ...formData, risk_category: e.target.value })}
                className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="safety">Safety</option>
                <option value="health">Health</option>
                <option value="environment">Environment</option>
                <option value="security">Security</option>
                <option value="operational">Operational</option>
              </select>
            </div>
            
            <div>
              <label className="block text-sm font-medium mb-2">Likelihood (1-5)</label>
              <input
                type="number"
                min="1"
                max="5"
                value={formData.likelihood}
                onChange={(e) => setFormData({ ...formData, likelihood: parseInt(e.target.value) })}
                className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                required
              />
            </div>
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-2">Severity (1-5)</label>
            <input
              type="number"
              min="1"
              max="5"
              value={formData.severity}
              onChange={(e) => setFormData({ ...formData, severity: parseInt(e.target.value) })}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
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
              Create Assessment
            </button>
          </div>
        </form>
      </motion.div>
    </div>
  );
}
