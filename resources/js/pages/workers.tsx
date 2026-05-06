import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Users, X, FileText, HardHat, AlertTriangle, Briefcase, ChevronRight } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';
import { Modal, FormField, FormActions } from '../components/modal';
import toast from 'react-hot-toast';

type DetailTab = 'documents' | 'ppe' | 'sanctions' | 'assignments';

export default function WorkersPage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [selectedWorker, setSelectedWorker] = useState<any>(null);
  const [activeTab, setActiveTab] = useState<DetailTab>('documents');
  const [form, setForm] = useState({ full_name: '', cin: '', function: '', phone: '', company: '' });

  const { data: workers, isLoading } = useQuery({
    queryKey: ['workers'],
    queryFn: async () => {
      try { const r = await api.get('/workers'); return r.data.data.items; } catch { return []; }
    },
  });

  const { data: workerDocuments } = useQuery({
    queryKey: ['worker-documents', selectedWorker?.id],
    queryFn: async () => {
      try { const r = await api.get(`/worker-documents?worker_id=${selectedWorker.id}`); return r.data.data.items; } catch { return []; }
    },
    enabled: !!selectedWorker && activeTab === 'documents',
  });

  const { data: workerPpe } = useQuery({
    queryKey: ['worker-ppe', selectedWorker?.id],
    queryFn: async () => {
      try { const r = await api.get(`/worker-ppe-issues?worker_id=${selectedWorker.id}`); return r.data.data.items; } catch { return []; }
    },
    enabled: !!selectedWorker && activeTab === 'ppe',
  });

  const { data: workerSanctions } = useQuery({
    queryKey: ['worker-sanctions', selectedWorker?.id],
    queryFn: async () => {
      try { const r = await api.get(`/worker-sanctions?worker_id=${selectedWorker.id}`); return r.data.data.items; } catch { return []; }
    },
    enabled: !!selectedWorker && activeTab === 'sanctions',
  });

  const { data: workerAssignments } = useQuery({
    queryKey: ['worker-assignments', selectedWorker?.id],
    queryFn: async () => {
      try { const r = await api.get(`/worker-project-assignments?worker_id=${selectedWorker.id}`); return r.data.data.items; } catch { return []; }
    },
    enabled: !!selectedWorker && activeTab === 'assignments',
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => { const r = await api.post('/workers', data); return r.data.data; },
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['workers'] }); setShowModal(false); setForm({ full_name: '', cin: '', function: '', phone: '', company: '' }); },
  });

  const tabs: { key: DetailTab; icon: React.ElementType; label: string }[] = [
    { key: 'documents', icon: FileText, label: t('modules:workers.documents', 'Documents') },
    { key: 'ppe', icon: HardHat, label: t('modules:workers.ppe', 'PPE') },
    { key: 'sanctions', icon: AlertTriangle, label: t('modules:workers.sanctions', 'Sanctions') },
    { key: 'assignments', icon: Briefcase, label: t('modules:workers.assignments', 'Assignments') },
  ];

  const getDocStatusColor = (status: string) => {
    switch (status) {
      case 'valid': return 'bg-green-500/10 text-green-600';
      case 'expiring': return 'bg-amber-500/10 text-amber-600';
      case 'expired': return 'bg-red-500/10 text-red-600';
      default: return 'bg-gray-500/10 text-gray-600';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('modules:workers.title')}</h1>
          <p className="text-muted-foreground">{t('modules:workers.subtitle', 'Manage workforce')}</p>
        </div>
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" /> {t('modules:workers.newWorker')}
        </button>
      </div>

      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4"><SkeletonTable rows={5} /></div>
        ) : workers?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">Name</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">CIN</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Function</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Status</th>
                  <th className="px-4 py-3 text-right text-sm font-medium"></th>
                </tr>
              </thead>
              <tbody>
                {workers.map((worker: any, index: number) => (
                  <motion.tr key={worker.id} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: index * 0.05 }}
                    className="border-b border-border last:border-0 hover:bg-muted/30 cursor-pointer"
                    onClick={() => { setSelectedWorker(worker); setActiveTab('documents'); }}>
                    <td className="px-4 py-3 text-sm font-medium">{worker.full_name}</td>
                    <td className="px-4 py-3 text-sm">{worker.cin}</td>
                    <td className="px-4 py-3 text-sm">{worker.function}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${worker.status === 'active' ? 'bg-green-500/10 text-green-600' : 'bg-gray-500/10 text-gray-600'}`}>
                        {worker.status}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right"><ChevronRight className="h-4 w-4 text-muted-foreground" /></td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState title={t('messages:empty.title')} description={t('modules:workers.noData', 'Start by adding your first worker')} action={t('modules:workers.newWorker')} icon={<Users className="h-8 w-8" />} onAction={() => setShowModal(true)} />
          </div>
        )}
      </div>

      {/* Worker Detail Drawer */}
      <AnimatePresence>
        {selectedWorker && (
          <motion.div initial={{ opacity: 0, x: 40 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 40 }}
            className="fixed inset-y-0 right-0 w-full max-w-lg bg-card border-l border-border shadow-xl z-50 overflow-y-auto">
            <div className="p-6">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h2 className="text-lg font-bold">{selectedWorker.full_name}</h2>
                  <p className="text-sm text-muted-foreground">{selectedWorker.function} &middot; {selectedWorker.status}</p>
                </div>
                <button onClick={() => setSelectedWorker(null)} className="p-2 rounded-lg hover:bg-muted"><X className="h-5 w-5" /></button>
              </div>

              {/* Tabs */}
              <div className="flex gap-1 mb-6 rounded-lg bg-muted/50 p-1">
                {tabs.map(tab => (
                  <button key={tab.key} onClick={() => setActiveTab(tab.key)}
                    className={`flex items-center gap-1.5 flex-1 rounded-md px-3 py-2 text-xs font-medium transition-colors ${activeTab === tab.key ? 'bg-card shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'}`}>
                    <tab.icon className="h-3.5 w-3.5" /> {tab.label}
                  </button>
                ))}
              </div>

              {/* Documents Tab */}
              {activeTab === 'documents' && (
                <div className="space-y-3">
                  {workerDocuments?.length > 0 ? workerDocuments.map((doc: any) => (
                    <div key={doc.id} className="rounded-lg border border-border p-4">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-sm font-medium">{doc.name}</span>
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getDocStatusColor(doc.status)}`}>{doc.status}</span>
                      </div>
                      <p className="text-xs text-muted-foreground">{doc.type} &middot; {doc.issuer}</p>
                      <div className="flex gap-4 mt-2 text-xs text-muted-foreground">
                        <span>Issued: {doc.issue_date ? new Date(doc.issue_date).toLocaleDateString() : 'N/A'}</span>
                        <span>Expires: {doc.expiry_date ? new Date(doc.expiry_date).toLocaleDateString() : 'N/A'}</span>
                      </div>
                    </div>
                  )) : <p className="text-sm text-muted-foreground text-center py-8">{t('modules:workers.noDocuments', 'No documents on file')}</p>}
                </div>
              )}

              {/* PPE Tab */}
              {activeTab === 'ppe' && (
                <div className="space-y-3">
                  {workerPpe?.length > 0 ? workerPpe.map((ppe: any) => (
                    <div key={ppe.id} className="rounded-lg border border-border p-4">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-sm font-medium">{ppe.ppe_item?.name || ppe.ppe_item_id}</span>
                        <span className="text-xs text-muted-foreground">Qty: {ppe.quantity}</span>
                      </div>
                      {ppe.size && <p className="text-xs text-muted-foreground">Size: {ppe.size}</p>}
                      <p className="text-xs text-muted-foreground mt-1">Issued: {ppe.issued_at ? new Date(ppe.issued_at).toLocaleDateString() : 'N/A'}</p>
                      {ppe.returned_at && <p className="text-xs text-muted-foreground">Returned: {new Date(ppe.returned_at).toLocaleDateString()}</p>}
                    </div>
                  )) : <p className="text-sm text-muted-foreground text-center py-8">{t('modules:workers.noPpe', 'No PPE issued')}</p>}
                </div>
              )}

              {/* Sanctions Tab */}
              {activeTab === 'sanctions' && (
                <div className="space-y-3">
                  {workerSanctions?.length > 0 ? workerSanctions.map((s: any) => (
                    <div key={s.id} className="rounded-lg border border-border p-4">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-sm font-medium">{s.type}</span>
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${s.status === 'active' ? 'bg-red-500/10 text-red-600' : 'bg-gray-500/10 text-gray-600'}`}>{s.status}</span>
                      </div>
                      <p className="text-xs text-muted-foreground">{s.description}</p>
                      <div className="flex gap-4 mt-2 text-xs text-muted-foreground">
                        <span>Severity: {s.severity}</span>
                        <span>Date: {s.date ? new Date(s.date).toLocaleDateString() : 'N/A'}</span>
                      </div>
                    </div>
                  )) : <p className="text-sm text-muted-foreground text-center py-8">{t('modules:workers.noSanctions', 'No sanctions recorded')}</p>}
                </div>
              )}

              {/* Assignments Tab */}
              {activeTab === 'assignments' && (
                <div className="space-y-3">
                  {workerAssignments?.length > 0 ? workerAssignments.map((a: any) => (
                    <div key={a.id} className="rounded-lg border border-border p-4">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-sm font-medium">{a.project?.name || a.project_id}</span>
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${a.status === 'active' ? 'bg-green-500/10 text-green-600' : 'bg-gray-500/10 text-gray-600'}`}>{a.status}</span>
                      </div>
                      <p className="text-xs text-muted-foreground">Role: {a.role || 'N/A'}</p>
                      <div className="flex gap-4 mt-2 text-xs text-muted-foreground">
                        <span>From: {a.start_date ? new Date(a.start_date).toLocaleDateString() : 'N/A'}</span>
                        {a.end_date && <span>To: {new Date(a.end_date).toLocaleDateString()}</span>}
                      </div>
                    </div>
                  )) : <p className="text-sm text-muted-foreground text-center py-8">{t('modules:workers.noAssignments', 'No project assignments')}</p>}
                </div>
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('modules:workers.newWorker')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('modules:workers.name', 'Full Name')} required>
            <input type="text" value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:workers.cin', 'CIN')} required>
              <input type="text" value={form.cin} onChange={(e) => setForm({ ...form, cin: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
            <FormField label={t('modules:workers.function', 'Function')} required>
              <input type="text" value={form.function} onChange={(e) => setForm({ ...form, function: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
            </FormField>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <FormField label={t('modules:workers.phone', 'Phone')}>
              <input type="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
            </FormField>
            <FormField label={t('modules:workers.company', 'Company')}>
              <input type="text" value={form.company} onChange={(e) => setForm({ ...form, company: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
            </FormField>
          </div>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('modules:workers.newWorker')} />
        </form>
      </Modal>
    </div>
  );
}
