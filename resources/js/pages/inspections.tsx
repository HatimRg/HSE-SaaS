import React from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus, ClipboardCheck } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

export default function InspectionsPage() {
  const { t } = useTranslation();

  const { data: inspections, isLoading } = useQuery({
    queryKey: ['inspections'],
    queryFn: async () => {
      const response = await api.get('/inspections');
      return response.data.data.items;
    },
  });

  const getResultColor = (result: string) => {
    switch (result) {
      case 'pass': return 'bg-green-500/10 text-green-600';
      case 'fail': return 'bg-red-500/10 text-red-600';
      case 'partial': return 'bg-yellow-500/10 text-yellow-600';
      default: return 'bg-gray-500/10 text-gray-600';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('navigation.inspections')}</h1>
          <p className="text-muted-foreground">Site inspections</p>
        </div>
        <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-dark">
          <Plus className="h-4 w-4" />
          New Inspection
        </button>
      </div>

      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4">
            <SkeletonTable rows={5} />
          </div>
        ) : inspections?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">Reference</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Type</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Date</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Result</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Score</th>
                </tr>
              </thead>
              <tbody>
                {inspections.map((inspection: any, index: number) => (
                  <motion.tr
                    key={inspection.id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.05 }}
                    className="border-b border-border last:border-0 hover:bg-muted/30"
                  >
                    <td className="px-4 py-3 text-sm font-medium">{inspection.reference}</td>
                    <td className="px-4 py-3 text-sm">{inspection.type}</td>
                    <td className="px-4 py-3 text-sm">{new Date(inspection.date).toLocaleDateString()}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getResultColor(inspection.result)}`}>
                        {inspection.result}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right text-sm font-medium">{inspection.score}%</td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              title="No inspections"
              description="Start by creating your first inspection"
              action="Create Inspection"
              icon={<ClipboardCheck className="h-8 w-8" />}
              onAction={() => {}}
            />
          </div>
        )}
      </div>
    </div>
  );
}
