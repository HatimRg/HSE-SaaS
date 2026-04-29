import React from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus, AlertTriangle } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

export default function SorPage() {
  const { t } = useTranslation();

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
    switch (status) {
      case 'closed': return 'bg-green-500/10 text-green-600';
      case 'in-progress': return 'bg-blue-500/10 text-blue-600';
      case 'open': return 'bg-red-500/10 text-red-600';
      default: return 'bg-gray-500/10 text-gray-600';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('sor.title')}</h1>
          <p className="text-muted-foreground">Safety observation reports</p>
        </div>
        <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-dark">
          <Plus className="h-4 w-4" />
          {t('sor.newReport')}
        </button>
      </div>

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
                  <th className="px-4 py-3 text-left text-sm font-medium">Reference</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Title</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Severity</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Due Date</th>
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
                        {report.status}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-sm">
                      {report.due_date ? new Date(report.due_date).toLocaleDateString() : '-'}
                    </td>
                  </motion.tr>
                ))}
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
