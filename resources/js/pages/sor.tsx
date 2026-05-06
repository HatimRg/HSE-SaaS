import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, AlertTriangle, User, Calendar, Clock, CheckCircle, ArrowRight, Bell, AlertOctagon } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';
import { Modal, FormField, FormActions } from '../components/modal';

interface HseEvent {
  id: string;
  reference: string;
  title: string;
  type: 'observation' | 'near_miss' | 'incident' | 'hazard' | 'violation' | 'improvement' | 'audit' | 'training';
  severity: 'critical' | 'high' | 'medium' | 'low';
  status: 'open' | 'in_progress' | 'closed' | 'verified' | 'cancelled';
  assignedTo: string;
  dueDate: string;
  createdAt: string;
  occurredAt: string;
  escalated: boolean;
  reminderSent: boolean;
}

const statusFlow = [
  { value: 'open', label: 'open', color: 'bg-red-500/10 text-red-600' },
  { value: 'in_progress', label: 'inProgress', color: 'bg-blue-500/10 text-blue-600' },
  { value: 'closed', label: 'closed', color: 'bg-green-500/10 text-green-600' },
  { value: 'verified', label: 'verified', color: 'bg-purple-500/10 text-purple-600' },
];

const eventTypes = [
  { value: 'observation', label: 'observation', color: 'bg-blue-500/10 text-blue-600' },
  { value: 'near_miss', label: 'nearMiss', color: 'bg-amber-500/10 text-amber-600' },
  { value: 'incident', label: 'incident', color: 'bg-red-500/10 text-red-600' },
  { value: 'hazard', label: 'hazard', color: 'bg-orange-500/10 text-orange-600' },
  { value: 'violation', label: 'violation', color: 'bg-purple-500/10 text-purple-600' },
  { value: 'improvement', label: 'improvement', color: 'bg-green-500/10 text-green-600' },
];

export default function SorPage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [selectedStatus, setSelectedStatus] = useState<string>('all');
  const [showEscalatedOnly, setShowEscalatedOnly] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({ title: '', type: 'observation', severity: 'medium', description: '', location: '', assigned_to: '', due_date: '', occurred_at: '' });

  const { data: reports, isLoading } = useQuery({
    queryKey: ['hse-events'],
    queryFn: async () => {
      const response = await api.get('/hse-events');
      return response.data.data.items;
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => {
      const response = await api.post('/hse-events', data);
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['hse-events'] });
      setShowModal(false);
      setForm({ title: '', type: 'observation', severity: 'medium', description: '', location: '', assigned_to: '', due_date: '', occurred_at: '' });
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

  const filteredReports = reports?.filter((report: HseEvent) => {
    if (selectedStatus !== 'all' && report.status !== selectedStatus) return false;
    if (showEscalatedOnly && !report.escalated) return false;
    return true;
  }) || [];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('modules:sor.title')}</h1>
          <p className="text-muted-foreground">{t('modules:sor.subtitle', 'Corrective Action Engine with assignment, deadlines & escalation')}</p>
        </div>
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" />
          {t('modules:sor.newReport')}
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
              {t(status.label)}
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
          {t('modules:sor.escalatedOnly', 'Escalated Only')}
        </button>
      </div>

      {/* Stats Overview */}
      <div className="grid gap-4 sm:grid-cols-4">
        {eventTypes.map((eventType) => {
          const count = reports?.filter((r: HseEvent) => r.type === eventType.value).length || 0;
          return (
            <div key={eventType.value} className={`rounded-lg border border-border bg-card p-4 ${eventType.color}`}>
              <p className="text-2xl font-bold">{count}</p>
              <p className="text-xs text-muted-foreground">{t(eventType.label)}</p>
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
                  <th className="px-4 py-3 text-left text-sm font-medium">{t('modules:sor.reference')}</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">{t('common:name')}</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">{t('modules:sor.severity')}</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">{t('common:status')}</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">{t('modules:sor.assignedTo', 'Assigned To')}</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">{t('modules:sor.dueDate')}</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">{t('common:actions')}</th>
                </tr>
              </thead>
              <tbody>
                {filteredReports.map((report: HseEvent, index: number) => {
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
                          {t(report.severity)}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(report.status)}`}>
                          {t(statusFlow.find(s => s.value === report.status)?.label || report.status)}
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
                            {t('modules:sor.reminderSent', 'Reminder sent')}
                          </div>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                          {report.escalated && (
                            <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-orange-500/10 text-orange-600">
                              <AlertOctagon className="h-3 w-3" />
                              {t('modules:sor.escalated', 'Escalated')}
                            </span>
                          )}
                          <button className="rounded-lg p-1.5 hover:bg-muted transition-colors" title={t('modules:sor.advanceStatus', 'Advance status')}>
                            <ArrowRight className="h-4 w-4" />
                          </button>
                          {report.status === 'closed' && (
                            <button className="rounded-lg p-1.5 hover:bg-muted transition-colors text-green-600" title={t('modules:sor.verify', 'Verify')}>
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
              title={t('messages:empty.title')}
              description={t('modules:sor.noData', 'Start by creating your first safety observation')}
              action={t('modules:sor.newReport')}
              onAction={() => setShowModal(true)}
            />
          </div>
        )}
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('modules:sor.newReport')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('common:name')} required>
            <input type="text" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:sor.type')} required>
              <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background">
                <option value="observation">{t('modules:sor.types.observation')}</option>
                <option value="near_miss">{t('modules:sor.types.nearMiss')}</option>
                <option value="incident">{t('modules:sor.types.incident')}</option>
                <option value="hazard">{t('modules:sor.types.hazard')}</option>
                <option value="violation">{t('modules:sor.types.violation')}</option>
                <option value="improvement">{t('modules:sor.types.improvement')}</option>
              </select>
            </FormField>
            <FormField label={t('modules:sor.severity')} required>
              <select value={form.severity} onChange={(e) => setForm({ ...form, severity: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background">
                <option value="critical">{t('common:critical')}</option>
                <option value="high">{t('common:high')}</option>
                <option value="medium">{t('common:medium')}</option>
                <option value="low">{t('common:low')}</option>
              </select>
            </FormField>
          </div>
          <FormField label={t('common:description')}>
            <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" rows={3} />
          </FormField>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:sor.location')}>
              <input type="text" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
            </FormField>
            <FormField label={t('modules:sor.occurredAt', 'Occurred At')} required>
              <input type="datetime-local" value={form.occurred_at} onChange={(e) => setForm({ ...form, occurred_at: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
          </div>
          <FormField label={t('modules:sor.dueDate')}>
            <input type="date" value={form.due_date} onChange={(e) => setForm({ ...form, due_date: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
          </FormField>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('modules:sor.newReport')} />
        </form>
      </Modal>
    </div>
  );
}
