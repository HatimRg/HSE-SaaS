import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { Users, Plus, Search, Edit, Trash2, Shield, Mail, Phone, ChevronLeft, ChevronRight } from 'lucide-react';
import { api } from '../lib/api';
import toast from 'react-hot-toast';
import { Modal, FormField, FormActions } from '../components/modal';

export default function UsersPage() {
  const { t } = useTranslation();
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({ name: '', email: '', password: '', role: '' });

  const { data, isLoading } = useQuery({
    queryKey: ['users', page, search],
    queryFn: async () => {
      try {
        const r = await api.get('/users', { params: { page, search } });
        return r.data.data;
      } catch {
        return { data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 };
      }
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => await api.delete(`/users/${id}`),
    onSuccess: () => {
      toast.success(t('messages:toasts.deleted'));
      qc.invalidateQueries({ queryKey: ['users'] });
    },
    onError: () => toast.error(t('common:error')),
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => {
      const response = await api.post('/users', data);
      return response.data.data;
    },
    onSuccess: () => {
      toast.success(t('messages:toasts.created', 'Created successfully'));
      qc.invalidateQueries({ queryKey: ['users'] });
      setShowModal(false);
      setForm({ name: '', email: '', password: '', role: '' });
    },
    onError: () => toast.error(t('common:error')),
  });

  const users = data?.data || [];
  const totalPages = data?.last_page || 1;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{t('modules:users.title', 'User Management')}</h1>
          <p className="text-muted-foreground text-sm mt-1">{data?.total ?? 0} {t('modules:users.title', 'users')}</p>
        </div>
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" />
          {t('modules:users.addUser', 'Add User')}
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
      ) : users.length === 0 ? (
        <div className="rounded-xl border border-border bg-card p-12 text-center">
          <Users className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
          <h3 className="text-lg font-semibold mb-1">{t('messages:empty.title')}</h3>
          <p className="text-sm text-muted-foreground">{t('messages:empty.description')}</p>
        </div>
      ) : (
        <>
          <div className="rounded-xl border border-border bg-card overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-border bg-muted/30">
                    <th className="px-4 py-3 text-left font-medium">{t('common:name')}</th>
                    <th className="px-4 py-3 text-left font-medium">{t('common:email')}</th>
                    <th className="px-4 py-3 text-left font-medium">{t('modules:users.role', 'Role')}</th>
                    <th className="px-4 py-3 text-left font-medium">{t('common:status')}</th>
                    <th className="px-4 py-3 text-right font-medium">{t('common:actions')}</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((u: any) => (
                    <tr key={u.id} className="border-b border-border/50 hover:bg-muted/20 transition-colors">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-3">
                          <img src={u.avatar || `https://ui-avatars.com/api/?name=${u.name}&background=random`} alt="" className="h-8 w-8 rounded-full" />
                          <span className="font-medium">{u.name}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">{u.email}</td>
                      <td className="px-4 py-3">
                        <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                          <Shield className="h-3 w-3" />
                          {u.role?.display_name || u.role?.name || '—'}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${u.is_active ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-red-500/10 text-red-600 dark:text-red-400'}`}>
                          {u.is_active ? t('common:active') : t('common:inactive')}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-right">
                        <button onClick={() => deleteMutation.mutate(u.id)} className="p-1.5 rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors" title={t('common:delete')}>
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
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

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('modules:users.addUser', 'Add User')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('common:name')} required>
            <input type="text" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <FormField label={t('common:email')} required>
            <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <FormField label={t('common:password')} required>
            <input type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required minLength={8} />
          </FormField>
          <FormField label={t('modules:users.role', 'Role')} required>
            <select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required>
              <option value="">{t('modules:users.selectRole', 'Select a role')}</option>
              <option value="admin">Admin</option>
              <option value="safety-officer">Safety Officer</option>
              <option value="supervisor">Supervisor</option>
              <option value="worker">Worker</option>
            </select>
          </FormField>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('modules:users.addUser', 'Add User')} />
        </form>
      </Modal>
    </div>
  );
}
