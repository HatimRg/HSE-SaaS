import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus, Filter, Download } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

export default function KpiPage() {
  const { t } = useTranslation();

  const { data: reports, isLoading } = useQuery({
    queryKey: ['kpi-reports'],
    queryFn: async () => {
      const response = await api.get('/kpi-reports');
      return response.data.data.items;
    },
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{t('kpi.title')}</h1>
          <p className="text-sm text-muted-foreground mt-1">Manage safety KPI reports</p>
        </div>
        <div className="flex items-center gap-2">
          <button className="flex items-center gap-2 rounded-lg border border-input bg-background px-3 py-2 text-sm hover:bg-muted transition-colors">
            <Filter className="h-4 w-4" />
            {t('common.filter')}
          </button>
          <button className="flex items-center gap-2 rounded-lg border border-input bg-background px-3 py-2 text-sm hover:bg-muted transition-colors">
            <Download className="h-4 w-4" />
            {t('common.export')}
          </button>
          <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
            <Plus className="h-4 w-4" />
            {t('kpi.newReport')}
          </button>
        </div>
      </div>

      {/* Table */}
      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4">
            <SkeletonTable rows={5} />
          </div>
        ) : reports?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">Period</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Project</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Hours</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Injuries</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {reports.map((report: any, index: number) => (
                  <motion.tr
                    key={report.id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.05 }}
                    className="border-b border-border last:border-0 hover:bg-muted/30"
                  >
                    <td className="px-4 py-3 text-sm">
                      {new Date(report.period_start).toLocaleDateString()} - {new Date(report.period_end).toLocaleDateString()}
                    </td>
                    <td className="px-4 py-3 text-sm">{report.project?.name}</td>
                    <td className="px-4 py-3 text-right text-sm">{report.total_hours}</td>
                    <td className="px-4 py-3 text-right text-sm">{report.injuries}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        report.status === 'approved' ? 'bg-green-500/10 text-green-600' :
                        report.status === 'submitted' ? 'bg-blue-500/10 text-blue-600' :
                        report.status === 'rejected' ? 'bg-red-500/10 text-red-600' :
                        'bg-gray-500/10 text-gray-600'
                      }`}>
                        {report.status}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right text-sm">
                      <button className="text-primary hover:underline">View</button>
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              title="No KPI reports"
              description="Start by creating your first KPI report"
              action="Create Report"
              onAction={() => {}}
            />
          </div>
        )}
      </div>
    </div>
  );
}
