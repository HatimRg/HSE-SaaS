import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, GraduationCap, Users, ChevronRight, X } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';
import { Modal, FormField, FormActions } from '../components/modal';

export default function TrainingPage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [selectedSession, setSelectedSession] = useState<any>(null);
  const [form, setForm] = useState({ title: '', type: 'safety', start_date: '', end_date: '', trainer: '', description: '' });

  const { data: sessions, isLoading } = useQuery({
    queryKey: ['training-sessions'],
    queryFn: async () => {
      try { const r = await api.get('/training-sessions'); return r.data.data.items; } catch { return []; }
    },
  });

  const { data: participants } = useQuery({
    queryKey: ['training-participants', selectedSession?.id],
    queryFn: async () => {
      try { const r = await api.get(`/training-participants?training_session_id=${selectedSession.id}`); return r.data.data.items; } catch { return []; }
    },
    enabled: !!selectedSession,
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => { const r = await api.post('/training-sessions', data); return r.data.data; },
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['training-sessions'] }); setShowModal(false); setForm({ title: '', type: 'safety', start_date: '', end_date: '', trainer: '', description: '' }); },
  });

  const getResultColor = (result: string | null) => {
    switch (result) {
      case 'pass': return 'bg-green-500/10 text-green-600';
      case 'fail': return 'bg-red-500/10 text-red-600';
      default: return 'bg-gray-500/10 text-gray-600';
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'attended': return 'bg-green-500/10 text-green-600';
      case 'absent': return 'bg-red-500/10 text-red-600';
      case 'excused': return 'bg-amber-500/10 text-amber-600';
      default: return 'bg-gray-500/10 text-gray-600';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('navigation:training')}</h1>
          <p className="text-muted-foreground">{t('modules:training.subtitle', 'Training sessions and certifications')}</p>
        </div>
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" /> {t('modules:training.newSession', 'New Session')}
        </button>
      </div>

      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4"><SkeletonTable rows={5} /></div>
        ) : sessions?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">Title</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Type</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Date</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                  <th className="px-4 py-3 text-right text-sm font-medium"></th>
                </tr>
              </thead>
              <tbody>
                {sessions.map((session: any, index: number) => (
                  <motion.tr key={session.id} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: index * 0.05 }}
                    className="border-b border-border last:border-0 hover:bg-muted/30 cursor-pointer"
                    onClick={() => setSelectedSession(session)}>
                    <td className="px-4 py-3 text-sm font-medium">{session.title}</td>
                    <td className="px-4 py-3 text-sm">{session.type}</td>
                    <td className="px-4 py-3 text-sm">{new Date(session.start_date).toLocaleDateString()}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        session.status === 'completed' ? 'bg-green-500/10 text-green-600' :
                        session.status === 'active' ? 'bg-blue-500/10 text-blue-600' :
                        'bg-gray-500/10 text-gray-600'
                      }`}>{session.status}</span>
                    </td>
                    <td className="px-4 py-3 text-right"><ChevronRight className="h-4 w-4 text-muted-foreground" /></td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState title={t('messages:empty.title')} description={t('modules:training.noData', 'Start by creating your first training session')} action={t('modules:training.newSession', 'Create Session')} icon={<GraduationCap className="h-8 w-8" />} onAction={() => setShowModal(true)} />
          </div>
        )}
      </div>

      {/* Participants Drawer */}
      <AnimatePresence>
        {selectedSession && (
          <motion.div initial={{ opacity: 0, x: 40 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 40 }}
            className="fixed inset-y-0 right-0 w-full max-w-lg bg-card border-l border-border shadow-xl z-50 overflow-y-auto">
            <div className="p-6">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h2 className="text-lg font-bold">{selectedSession.title}</h2>
                  <p className="text-sm text-muted-foreground">{selectedSession.type} &middot; {new Date(selectedSession.start_date).toLocaleDateString()}</p>
                </div>
                <button onClick={() => setSelectedSession(null)} className="p-2 rounded-lg hover:bg-muted"><X className="h-5 w-5" /></button>
              </div>

              <h3 className="text-sm font-semibold mb-3 flex items-center gap-2">
                <Users className="h-4 w-4 text-primary" /> {t('modules:training.participants', 'Participants')}
              </h3>

              <div className="space-y-3">
                {participants?.length > 0 ? participants.map((p: any) => (
                  <div key={p.id} className="rounded-lg border border-border p-4">
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-sm font-medium">{p.worker?.full_name || `Worker #${p.worker_id}`}</span>
                      <div className="flex items-center gap-2">
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(p.status)}`}>{p.status}</span>
                        {p.result && <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getResultColor(p.result)}`}>{p.result}</span>}
                      </div>
                    </div>
                    {p.score !== null && p.score !== undefined && (
                      <p className="text-xs text-muted-foreground">Score: {p.score}%</p>
                    )}
                    {p.feedback && <p className="text-xs text-muted-foreground mt-1">{p.feedback}</p>}
                  </div>
                )) : (
                  <p className="text-sm text-muted-foreground text-center py-8">{t('modules:training.noParticipants', 'No participants recorded')}</p>
                )}
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('modules:training.newSession', 'New Training Session')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('common:name')} required>
            <input type="text" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:training.type', 'Type')} required>
              <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background">
                <option value="safety">{t('modules:risk.safety', 'Safety')}</option>
                <option value="health">{t('dashboard:health', 'Health')}</option>
                <option value="environment">{t('navigation:environment')}</option>
                <option value="fire">{t('modules:permits.hotWork', 'Fire')}</option>
                <option value="first_aid">{t('modules:kpi.firstAids', 'First Aid')}</option>
              </select>
            </FormField>
            <FormField label={t('modules:training.trainer', 'Trainer')}>
              <input type="text" value={form.trainer} onChange={(e) => setForm({ ...form, trainer: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
            </FormField>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:inspections.reference', 'Start Date')} required>
              <input type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
            <FormField label={t('modules:permits.endDate', 'End Date')}>
              <input type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
            </FormField>
          </div>
          <FormField label={t('common:description')}>
            <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" rows={3} />
          </FormField>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('modules:training.newSession', 'Create Session')} />
        </form>
      </Modal>
    </div>
  );
}
