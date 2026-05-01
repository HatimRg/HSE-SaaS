import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus, FileCheck } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

export default function PermitsPage() {
  const { t } = useTranslation();

  const { data: permits, isLoading } = useQuery({
    queryKey: ['work-permits'],
    queryFn: async () => {
      const response = await api.get('/work-permits');
      return response.data.data.items;
    },
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'approved': return 'bg-green-500/10 text-green-600';
      case 'pending': return 'bg-yellow-500/10 text-yellow-600';
      case 'rejected': return 'bg-red-500/10 text-red-600';
      case 'expired': return 'bg-gray-500/10 text-gray-600';
      default: return 'bg-blue-500/10 text-blue-600';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('permits.title')}</h1>
          <p className="text-muted-foreground">Manage work permits</p>
        </div>
        <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" />
          {t('permits.newPermit')}
        </button>
      </div>

      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4">
            <SkeletonTable rows={5} />
          </div>
        ) : permits?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">Permit #</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Type</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Location</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Expiry</th>
                </tr>
              </thead>
              <tbody>
                {permits.map((permit: any, index: number) => (
                  <motion.tr
                    key={permit.id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.05 }}
                    className="border-b border-border last:border-0 hover:bg-muted/30"
                  >
                    <td className="px-4 py-3 text-sm font-medium">{permit.permit_number}</td>
                    <td className="px-4 py-3 text-sm">{permit.type}</td>
                    <td className="px-4 py-3 text-sm">{permit.location}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(permit.status)}`}>
                        {permit.status}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-sm">
                      {new Date(permit.expiry_date).toLocaleDateString()}
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              title="No permits"
              description="Start by creating your first work permit"
              action="Create Permit"
              icon={<FileCheck className="h-8 w-8" />}
              onAction={() => {}}
            />
          </div>
        )}
      </div>
    </div>
  );
}
