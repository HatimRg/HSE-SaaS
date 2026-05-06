import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { FolderKanban, Plus, Search, MapPin, Calendar, Users, ChevronLeft, ChevronRight } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { Modal, FormField, FormActions } from '../components/modal';

export default function ProjectsPage() {
  const { t } = useTranslation();
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({ name: '', location: '', start_date: '', description: '' });

  const { data, isLoading } = useQuery({
    queryKey: ['projects', page, search],
    queryFn: async () => {
      try {
        const r = await api.get('/projects', { params: { page, search } });
        return r.data.data;
      } catch {
        return { data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 };
      }
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => {
      const response = await api.post('/projects', data);
      return response.data.data;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['projects'] });
      setShowModal(false);
      setForm({ name: '', location: '', start_date: '', description: '' });
    },
  });

  const projects = data?.data || [];
  const totalPages = data?.last_page || 1;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{t('modules:projects.title', 'Projects')}</h1>
          <p className="text-muted-foreground text-sm mt-1">{data?.total ?? 0} {t('modules:projects.title', 'projects')}</p>
        </div>
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" />
          {t('modules:projects.addProject', 'New Project')}
        </button>
      </div>

      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder={t('common:search')}
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="h-10 w-full rounded-lg border border-input bg-background pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-20">
          <div className="h-8 w-8 rounded-full border-4 border-primary border-t-transparent animate-spin" />
        </div>
      ) : projects.length === 0 ? (
        <EmptyState
          icon={<FolderKanban className="h-8 w-8" />}
          title={t('messages:empty.title')}
          description={t('modules:projects.noData', 'No projects available yet')}
        />
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {projects.map((p: any) => (
              <div key={p.id} className="rounded-xl border border-border bg-card p-5 hover:shadow-md transition-shadow">
                <div className="flex items-start justify-between mb-3">
                  <h3 className="font-semibold text-sm">{p.name}</h3>
                  <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                    p.status === 'active' ? 'bg-green-500/10 text-green-600 dark:text-green-400' :
                    p.status === 'completed' ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400' :
                    'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                  }`}>
                    {t(p.status || 'active')}
                  </span>
                </div>
                {p.location && (
                  <div className="flex items-center gap-1.5 text-xs text-muted-foreground mb-2">
                    <MapPin className="h-3 w-3" /> {p.location}
                  </div>
                )}
                {p.start_date && (
                  <div className="flex items-center gap-1.5 text-xs text-muted-foreground mb-2">
                    <Calendar className="h-3 w-3" /> {p.start_date}
                  </div>
                )}
                {p.workers_count !== undefined && (
                  <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Users className="h-3 w-3" /> {p.workers_count} {t('modules:workers.title', 'workers')}
                  </div>
                )}
              </div>
            ))}
          </div>

          {totalPages > 1 && (
            <div className="flex items-center justify-center gap-2">
              <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1} className="p-2 rounded-lg border border-border hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed">
                <ChevronLeft className="h-4 w-4" />
              </button>
              <span className="text-sm text-muted-foreground">{page} / {totalPages}</span>
              <button onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages} className="p-2 rounded-lg border border-border hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed">
                <ChevronRight className="h-4 w-4" />
              </button>
            </div>
          )}
        </>
      )}

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('modules:projects.addProject', 'New Project')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('modules:projects.name', 'Project Name')} required>
            <input type="text" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <FormField label={t('modules:sor.location', 'Location')}>
            <input type="text" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
          </FormField>
          <FormField label={t('modules:permits.startDate', 'Start Date')}>
            <input type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
          </FormField>
          <FormField label={t('common:description')}>
            <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" rows={3} />
          </FormField>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('modules:projects.addProject', 'Create Project')} />
        </form>
      </Modal>
    </div>
  );
}
