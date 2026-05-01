import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus, AlertTriangle, User, Calendar, Clock, CheckCircle, ArrowRight, Bell, AlertOctagon } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

interface SorReport {
  id: string;
  reference: string;
  title: string;
  severity: 'critical' | 'high' | 'medium' | 'low';
  status: 'open' | 'in-progress' | 'closed' | 'verified';
  assignedTo: string;
  dueDate: string;
  createdAt: string;
  escalated: boolean;
  reminderSent: boolean;
}

const statusFlow = [
  { value: 'open', label: 'Open', color: 'bg-red-500/10 text-red-600' },
  { value: 'in-progress', label: 'In Progress', color: 'bg-blue-500/10 text-blue-600' },
  { value: 'closed', label: 'Closed', color: 'bg-green-500/10 text-green-600' },
  { value: 'verified', label: 'Verified', color: 'bg-purple-500/10 text-purple-600' },
];

export default function SorPage() {
  const { t } = useTranslation();
  const [selectedStatus, setSelectedStatus] = useState<string>('all');
  const [showEscalatedOnly, setShowEscalatedOnly] = useState(false);

  const { data: reports, isLoading } = useQuery({
    queryKey: ['sor-reports'],
    queryFn: async () => {
      const response = await api.get('/sor-reports');
      return response.data.data.items;
    },
  });

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'critical': return 'bg-red-500/10 text-red-600';
      case 'high': return 'bg-orange-500/10 text-orange-600';
      case 'medium': return 'bg-yellow-500/10 text-yellow-600';
      default: return 'bg-blue-500/10 text-blue-600';
    }
  };

  const getStatusColor = (status: string) => {
    const statusConfig = statusFlow.find(s => s.value === status);
    return statusConfig?.color || 'bg-gray-500/10 text-gray-600';
  };

  const isOverdue = (dueDate: string) => {
    return new Date(dueDate) < new Date();
  };

  const getDaysUntilDue = (dueDate: string) => {
    const today = new Date();
    const due = new Date(dueDate);
    const diff = Math.ceil((due.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
    return diff;
  };

  const filteredReports = reports?.filter((report: SorReport) => {
    if (selectedStatus !== 'all' && report.status !== selectedStatus) return false;
    if (showEscalatedOnly && !report.escalated) return false;
    return true;
  }) || [];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('sor.title')}</h1>
          <p className="text-muted-foreground">Corrective Action Engine with assignment, deadlines & escalation</p>
        </div>
        <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" />
          {t('sor.newReport')}
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <div className="flex items-center gap-2 rounded-lg border border-border bg-card p-1">
          {statusFlow.map((status) => (
            <button
              key={status.value}
              onClick={() => setSelectedStatus(selectedStatus === status.value ? 'all' : status.value)}
              className={`rounded-md px-3 py-1.5 text-xs font-medium transition-colors ${
                selectedStatus === status.value ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'
              }`}
            >
              {status.label}
            </button>
          ))}
        </div>
        <button
          onClick={() => setShowEscalatedOnly(!showEscalatedOnly)}
          className={`flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors ${
            showEscalatedOnly ? 'bg-orange-500/10 border-orange-500 text-orange-600' : 'border-border bg-card text-muted-foreground hover:bg-muted'
          }`}
        >
          <AlertOctagon className="h-3 w-3" />
          Escalated Only
        </button>
      </div>

      {/* Stats Overview */}
      <div className="grid gap-4 sm:grid-cols-4">
        {statusFlow.map((status) => {
          const count = reports?.filter((r: SorReport) => r.status === status.value).length || 0;
          return (
            <div key={status.value} className={`rounded-lg border border-border bg-card p-4 ${status.color}`}>
              <p className="text-2xl font-bold">{count}</p>
              <p className="text-xs text-muted-foreground">{status.label}</p>
            </div>
          );
        })}
      </div>

      {/* Reports List */}
      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4">
            <SkeletonTable rows={5} />
          </div>
        ) : filteredReports.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">Reference</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Title</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Severity</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Assigned To</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Deadline</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredReports.map((report: SorReport, index: number) => {
                  const daysUntilDue = getDaysUntilDue(report.dueDate);
                  const overdue = isOverdue(report.dueDate);
                  return (
                    <motion.tr
                      key={report.id}
                      initial={{ opacity: 0, x: -20 }}
                      animate={{ opacity: 1, x: 0 }}
                      transition={{ delay: index * 0.05 }}
                      className={`border-b border-border last:border-0 hover:bg-muted/30 ${report.escalated ? 'bg-orange-500/5' : ''}`}
                    >
                      <td className="px-4 py-3 text-sm font-medium">{report.reference}</td>
                      <td className="px-4 py-3 text-sm">{report.title}</td>
                      <td className="px-4 py-3 text-center">
                        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${getSeverityColor(report.severity)}`}>
                          <AlertTriangle className="h-3 w-3" />
                          {report.severity}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(report.status)}`}>
                          {statusFlow.find(s => s.value === report.status)?.label || report.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-sm">
                        <div className="flex items-center gap-2">
                          <User className="h-4 w-4 text-muted-foreground" />
                          {report.assignedTo}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-sm">
                        <div className={`flex items-center gap-2 ${overdue ? 'text-red-500' : daysUntilDue <= 3 ? 'text-amber-500' : ''}`}>
                          <Calendar className="h-4 w-4" />
                          <span>{new Date(report.dueDate).toLocaleDateString()}</span>
                          {overdue && <AlertTriangle className="h-3 w-3" />}
                          {!overdue && daysUntilDue <= 3 && <Clock className="h-3 w-3" />}
                        </div>
                        {report.reminderSent && (
                          <div className="flex items-center gap-1 text-xs text-muted-foreground mt-1">
                            <Bell className="h-3 w-3" />
                            Reminder sent
                          </div>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                          {report.escalated && (
                            <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-orange-500/10 text-orange-600">
                              <AlertOctagon className="h-3 w-3" />
                              Escalated
                            </span>
                          )}
                          <button className="rounded-lg p-1.5 hover:bg-muted transition-colors" title="Advance status">
                            <ArrowRight className="h-4 w-4" />
                          </button>
                          {report.status === 'closed' && (
                            <button className="rounded-lg p-1.5 hover:bg-muted transition-colors text-green-600" title="Verify">
                              <CheckCircle className="h-4 w-4" />
                            </button>
                          )}
                        </div>
                      </td>
                    </motion.tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              title="No observations"
              description="Start by creating your first safety observation"
              action="Create Observation"
              onAction={() => {}}
            />
          </div>
        )}
      </div>
    </div>
  );
}
