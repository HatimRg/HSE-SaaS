import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { RefreshCw, TrendingUp, TrendingDown, Minus, Download, Filter, BarChart3 } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';
import { Modal, FormField, FormActions } from '../components/modal';
import toast from 'react-hot-toast';

export default function KpiPage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [showComputeModal, setShowComputeModal] = useState(false);
  const [computeForm, setComputeForm] = useState({ project_id: '', period_start: '', period_end: '' });

  const { data: definitions } = useQuery({
    queryKey: ['kpi-definitions'],
    queryFn: async () => {
      try { const r = await api.get('/kpi/definitions'); return r.data.data; } catch { return []; }
    },
  });

  const { data: values, isLoading } = useQuery({
    queryKey: ['kpi-values'],
    queryFn: async () => {
      try { const r = await api.get('/kpi/values'); return r.data.data.items; } catch { return []; }
    },
  });

  const computeMutation = useMutation({
    mutationFn: async (data: typeof computeForm) => {
      const r = await api.post('/kpi/compute', data);
      return r.data.data;
    },
    onSuccess: (computed) => {
      toast.success(`Computed ${computed.length} KPIs`);
      setShowComputeModal(false);
      setComputeForm({ project_id: '', period_start: '', period_end: '' });
      queryClient.invalidateQueries({ queryKey: ['kpi-values'] });
    },
    onError: () => toast.error(t('messages:error', 'Failed to compute KPIs')),
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'on_target': return 'bg-green-500/10 text-green-600';
      case 'warning': return 'bg-amber-500/10 text-amber-600';
      case 'critical': return 'bg-red-500/10 text-red-600';
      default: return 'bg-gray-500/10 text-gray-600';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'on_target': return 'On Target';
      case 'warning': return 'Warning';
      case 'critical': return 'Critical';
      default: return status;
    }
  };

  const getTrendIcon = (status: string) => {
    switch (status) {
      case 'on_target': return <TrendingUp className="h-4 w-4 text-green-500" />;
      case 'warning': return <Minus className="h-4 w-4 text-amber-500" />;
      case 'critical': return <TrendingDown className="h-4 w-4 text-red-500" />;
      default: return <Minus className="h-4 w-4 text-gray-500" />;
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{t('modules:kpi.title', 'KPI Dashboard')}</h1>
          <p className="text-sm text-muted-foreground mt-1">{t('modules:kpi.subtitle', 'Computed safety performance indicators')}</p>
        </div>
        <div className="flex items-center gap-2">
          <button className="flex items-center gap-2 rounded-lg border border-input bg-background px-3 py-2 text-sm hover:bg-muted transition-colors">
            <Filter className="h-4 w-4" /> {t('common.filter', 'Filter')}
          </button>
          <button className="flex items-center gap-2 rounded-lg border border-input bg-background px-3 py-2 text-sm hover:bg-muted transition-colors">
            <Download className="h-4 w-4" /> {t('common.export', 'Export')}
          </button>
          <button onClick={() => setShowComputeModal(true)}
            className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
            <RefreshCw className="h-4 w-4" /> {t('modules:kpi.compute', 'Compute KPIs')}
          </button>
        </div>
      </div>

      {/* KPI Definition Cards */}
      {definitions?.length > 0 && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {definitions.map((def: any) => {
            const latestValue = values?.find((v: any) => v.kpi_definition_id === def.id);
            return (
              <motion.div key={def.id} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
                className="rounded-xl border border-border bg-card p-5">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-2">
                    <BarChart3 className="h-4 w-4 text-primary" />
                    <h3 className="text-sm font-semibold">{def.name}</h3>
                  </div>
                  {latestValue ? getTrendIcon(latestValue.status) : <Minus className="h-4 w-4 text-gray-400" />}
                </div>
                <div className="flex items-end gap-2 mb-2">
                  <span className="text-3xl font-bold tracking-tight">
                    {latestValue ? (typeof latestValue.value === 'number' ? latestValue.value.toFixed(2) : latestValue.value) : '—'}
                  </span>
                  {def.unit && <span className="text-sm text-muted-foreground mb-1">{def.unit}</span>}
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-xs text-muted-foreground">
                    Target: {def.target_value ?? 'N/A'} {def.direction === 'lower_is_better' ? '(↓ better)' : '(↑ better)'}
                  </span>
                  {latestValue && (
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(latestValue.status)}`}>
                      {getStatusLabel(latestValue.status)}
                    </span>
                  )}
                </div>
                {latestValue?.computed_at && (
                  <p className="text-xs text-muted-foreground mt-2">
                    Computed: {new Date(latestValue.computed_at).toLocaleString()}
                  </p>
                )}
              </motion.div>
            );
          })}
        </div>
      )}

      {/* Values Table */}
      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4"><SkeletonTable rows={5} /></div>
        ) : values?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">KPI</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Project</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Value</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Target</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Period</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Computed</th>
                </tr>
              </thead>
              <tbody>
                {values.map((val: any, index: number) => (
                  <motion.tr key={val.id} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: index * 0.05 }}
                    className="border-b border-border last:border-0 hover:bg-muted/30">
                    <td className="px-4 py-3 text-sm font-medium">{val.definition?.name || val.kpi_definition_id}</td>
                    <td className="px-4 py-3 text-sm">{val.project?.name || '—'}</td>
                    <td className="px-4 py-3 text-right text-sm font-semibold">{typeof val.value === 'number' ? val.value.toFixed(2) : val.value}</td>
                    <td className="px-4 py-3 text-right text-sm text-muted-foreground">{val.target_value ?? '—'}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(val.status)}`}>
                        {getTrendIcon(val.status)}
                        {getStatusLabel(val.status)}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-sm text-muted-foreground">
                      {val.period_start ? new Date(val.period_start).toLocaleDateString() : '—'} — {val.period_end ? new Date(val.period_end).toLocaleDateString() : ''}
                    </td>
                    <td className="px-4 py-3 text-sm text-muted-foreground">
                      {val.computed_at ? new Date(val.computed_at).toLocaleString() : '—'}
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              icon={<BarChart3 className="h-8 w-8" />}
              title={t('modules:kpi.noData', 'No KPI values yet')}
              description={t('modules:kpi.computeDesc', 'Compute KPIs from your safety data to see performance indicators')}
              action={t('modules:kpi.compute', 'Compute KPIs')}
              onAction={() => setShowComputeModal(true)}
            />
          </div>
        )}
      </div>

      {/* Compute Modal */}
      <Modal isOpen={showComputeModal} onClose={() => setShowComputeModal(false)} title={t('modules:kpi.compute', 'Compute KPIs')}>
        <form onSubmit={(e) => { e.preventDefault(); computeMutation.mutate(computeForm); }} className="space-y-4">
          <p className="text-sm text-muted-foreground">
            {t('modules:kpi.autoComputeDesc', 'KPIs are computed automatically from your safety data (events, inspections, permits, training). No manual entry needed.')}
          </p>
          <FormField label={t('modules:kpi.project', 'Project')} required>
            <input type="text" value={computeForm.project_id} onChange={(e) => setComputeForm({ ...computeForm, project_id: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" placeholder={t('modules:kpi.projectId', 'Project ID')} required />
          </FormField>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:kpi.periodStart', 'Period Start')} required>
              <input type="date" value={computeForm.period_start} onChange={(e) => setComputeForm({ ...computeForm, period_start: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
            <FormField label={t('modules:kpi.periodEnd', 'Period End')} required>
              <input type="date" value={computeForm.period_end} onChange={(e) => setComputeForm({ ...computeForm, period_end: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
          </div>
          <FormActions onCancel={() => setShowComputeModal(false)} onSubmit={() => computeMutation.mutate(computeForm)} isPending={computeMutation.isPending} submitLabel={t('modules:kpi.compute', 'Compute')} />
        </form>
      </Modal>
    </div>
  );
}
