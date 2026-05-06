import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, FileCheck } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';
import { Modal, FormField, FormActions } from '../components/modal';

export default function PermitsPage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({ type: 'hot_work', description: '', location: '', start_date: '', end_date: '', precautions: '' });

  const { data: permitTypes } = useQuery({
    queryKey: ['permit-types'],
    queryFn: async () => {
      try { const r = await api.get('/permit-types'); return r.data.data; } catch { return []; }
    },
  });

  const { data: permits, isLoading } = useQuery({
    queryKey: ['work-permits'],
    queryFn: async () => {
      try {
        const response = await api.get('/work-permits');
        return response.data.data.items;
      } catch {
        return [];
      }
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => {
      const response = await api.post('/work-permits', data);
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['work-permits'] });
      setShowModal(false);
      setForm({ type: 'hot_work', description: '', location: '', start_date: '', end_date: '', precautions: '' });
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
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
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
              title={t('messages:empty.title')}
              description={t('modules:permits.noData', 'Start by creating your first work permit')}
              action={t('permits.newPermit')}
              icon={<FileCheck className="h-8 w-8" />}
              onAction={() => setShowModal(true)}
            />
          </div>
        )}
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('permits.newPermit')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('modules:permits.type', 'Permit Type')} required>
            <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background">
              {(permitTypes || []).length > 0 ? (
                permitTypes.map((pt: any) => (
                  <option key={pt.id} value={pt.code}>{pt.name}</option>
                ))
              ) : (
                <>
                  <option value="hot_work">{t('modules:permits.hotWork', 'Hot Work')}</option>
                  <option value="confined_space">{t('modules:permits.confinedSpace', 'Confined Space')}</option>
                  <option value="electrical">{t('modules:permits.electrical', 'Electrical')}</option>
                  <option value="excavation">{t('modules:permits.excavation', 'Excavation')}</option>
                  <option value="working_at_height">{t('modules:permits.workingAtHeight', 'Working at Height')}</option>
                </>
              )}
            </select>
          </FormField>
          <FormField label={t('common:description')} required>
            <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" rows={3} required />
          </FormField>
          <FormField label={t('modules:sor.location', 'Location')} required>
            <input type="text" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:permits.startDate', 'Start Date')} required>
              <input type="datetime-local" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
            <FormField label={t('modules:permits.endDate', 'End Date')} required>
              <input type="datetime-local" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
          </div>
          <FormField label={t('modules:permits.precautions', 'Safety Precautions')}>
            <textarea value={form.precautions} onChange={(e) => setForm({ ...form, precautions: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" rows={2} />
          </FormField>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('permits.newPermit')} />
        </form>
      </Modal>
    </div>
  );
}
