import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, ClipboardCheck, Settings, FileText, Wrench, Map, Truck, TrendingUp, AlertTriangle, CheckCircle, ChevronRight, X } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';
import { Modal, FormField, FormActions } from '../components/modal';

interface InspectionTemplate {
  id: string;
  name: string;
  category: 'equipment' | 'area' | 'vehicle';
  items: ChecklistItem[];
}

interface ChecklistItem {
  id: string;
  question: string;
  required: boolean;
  weight: number;
}

const defaultTemplates: InspectionTemplate[] = [];

export default function InspectionsPage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [showTemplateBuilder, setShowTemplateBuilder] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<'all' | 'equipment' | 'area' | 'vehicle'>('all');
  const [showModal, setShowModal] = useState(false);
  const [selectedInspection, setSelectedInspection] = useState<any>(null);
  const [form, setForm] = useState({ template_id: '', location: '', scheduled_date: '', notes: '' });

  const { data: inspections, isLoading } = useQuery({
    queryKey: ['inspections'],
    queryFn: async () => {
      try {
        const response = await api.get('/inspections');
        return response.data.data.items;
      } catch {
        return [];
      }
    },
  });

  const { data: templates } = useQuery({
    queryKey: ['inspection-templates'],
    queryFn: async () => {
      try {
        const response = await api.get('/inspection-templates');
        return response.data.data;
      } catch {
        return defaultTemplates;
      }
    },
  });

  const { data: inspectionItems } = useQuery({
    queryKey: ['inspection-items', selectedInspection?.id],
    queryFn: async () => {
      try { const r = await api.get(`/inspection-items?inspection_id=${selectedInspection.id}`); return r.data.data.items; } catch { return []; }
    },
    enabled: !!selectedInspection,
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => {
      const response = await api.post('/inspections', data);
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inspections'] });
      setShowModal(false);
      setForm({ template_id: '', location: '', scheduled_date: '', notes: '' });
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

  const getRiskLevel = (score: number) => {
    if (score >= 90) return { level: t('common:low'), color: 'text-green-500', icon: CheckCircle };
    if (score >= 70) return { level: t('common:medium'), color: 'text-amber-500', icon: TrendingUp };
    return { level: t('common:high'), color: 'text-red-500', icon: AlertTriangle };
  };

  const getCategoryIcon = (category: string) => {
    switch (category) {
      case 'equipment': return Wrench;
      case 'area': return Map;
      case 'vehicle': return Truck;
      default: return FileText;
    }
  };

  const filteredTemplates = selectedCategory === 'all' 
    ? (templates || []) 
    : (templates || []).filter((t: InspectionTemplate) => t.category === selectedCategory);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('navigation:inspections')}</h1>
          <p className="text-muted-foreground">{t('modules:inspections.subtitle', 'Standardized inspection system with templates & auto-scoring')}</p>
        </div>
        <div className="flex gap-2">
          <button 
            onClick={() => setShowTemplateBuilder(!showTemplateBuilder)}
            className="flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted"
          >
            <Settings className="h-4 w-4" />
            {t('modules:inspections.templateBuilder', 'Template Builder')}
          </button>
          <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
            <Plus className="h-4 w-4" />
            {t('modules:inspections.newInspection', 'New Inspection')}
          </button>
        </div>
      </div>

      {/* Template Builder Section */}
      <AnimatePresence>
        {showTemplateBuilder && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            className="rounded-xl border border-border bg-card p-6"
          >
            <div className="mb-4 flex items-center justify-between">
              <h2 className="font-semibold">{t('modules:inspections.templates', 'Inspection Templates')}</h2>
              <div className="flex gap-2">
                <button 
                  onClick={() => setSelectedCategory('all')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'all' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  {t('common:all', 'All')}
                </button>
                <button 
                  onClick={() => setSelectedCategory('equipment')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'equipment' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  {t('modules:inspections.equipment', 'Equipment')}
                </button>
                <button 
                  onClick={() => setSelectedCategory('area')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'area' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  {t('modules:inspections.areas', 'Areas')}
                </button>
                <button 
                  onClick={() => setSelectedCategory('vehicle')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'vehicle' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  {t('modules:inspections.vehicles', 'Vehicles')}
                </button>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {filteredTemplates.map((template) => {
                const CategoryIcon = getCategoryIcon(template.category);
                return (
                  <motion.div
                    key={template.id}
                    whileHover={{ scale: 1.02 }}
                    className="rounded-lg border border-border bg-muted/30 p-4 cursor-pointer hover:bg-muted/50"
                  >
                    <div className="flex items-start justify-between mb-2">
                      <div className="rounded-lg bg-primary/10 p-2">
                        <CategoryIcon className="h-4 w-4 text-primary" />
                      </div>
                      <span className="text-xs text-muted-foreground capitalize">{template.category}</span>
                    </div>
                    <h3 className="font-semibold mb-2">{template.name}</h3>
                    <p className="text-sm text-muted-foreground">{template.items.length} {t('modules:inspections.checklistItems', 'checklist items')}</p>
                  </motion.div>
                );
              })}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Inspections List */}
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
                  <th className="px-4 py-3 text-left text-sm font-medium">{t('modules:inspections.reference', 'Reference')}</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">{t('modules:inspections.type', 'Type')}</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">{t('common:date')}</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">{t('modules:inspections.result', 'Result')}</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">{t('modules:inspections.score', 'Score')}</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">{t('modules:inspections.riskLevel', 'Risk Level')}</th>
                </tr>
              </thead>
              <tbody>
                {inspections.map((inspection: any, index: number) => {
                  const risk = getRiskLevel(inspection.score);
                  const RiskIcon = risk.icon;
                  return (
                    <motion.tr
                      key={inspection.id}
                      initial={{ opacity: 0, x: -20 }}
                      animate={{ opacity: 1, x: 0 }}
                      transition={{ delay: index * 0.05 }}
                      className="border-b border-border last:border-0 hover:bg-muted/30 cursor-pointer"
                      onClick={() => setSelectedInspection(inspection)}
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
                      <td className="px-4 py-3 text-center">
                        <div className={`flex items-center justify-center gap-1 text-xs font-medium ${risk.color}`}>
                          <RiskIcon className="h-3 w-3" />
                          {risk.level}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right"><ChevronRight className="h-4 w-4 text-muted-foreground" /></td>
                    </motion.tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              title={t('messages:empty.title')}
              description={t('modules:inspections.noData', 'Start by creating your first inspection using a template')}
              action={t('modules:inspections.newInspection', 'Create Inspection')}
              icon={<ClipboardCheck className="h-8 w-8" />}
              onAction={() => setShowModal(true)}
            />
          </div>
        )}
      </div>

      {/* Inspection Detail Drawer */}
      <AnimatePresence>
        {selectedInspection && (
          <motion.div initial={{ opacity: 0, x: 40 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 40 }}
            className="fixed inset-y-0 right-0 w-full max-w-lg bg-card border-l border-border shadow-xl z-50 overflow-y-auto">
            <div className="p-6">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h2 className="text-lg font-bold">{selectedInspection.reference}</h2>
                  <p className="text-sm text-muted-foreground">{selectedInspection.type} &middot; {new Date(selectedInspection.date).toLocaleDateString()}</p>
                </div>
                <button onClick={() => setSelectedInspection(null)} className="p-2 rounded-lg hover:bg-muted"><X className="h-5 w-5" /></button>
              </div>

              <div className="flex items-center gap-4 mb-6">
                <div className="text-center">
                  <p className="text-3xl font-bold">{selectedInspection.score}%</p>
                  <p className="text-xs text-muted-foreground">Score</p>
                </div>
                <span className={`inline-flex rounded-full px-3 py-1 text-sm font-medium ${getResultColor(selectedInspection.result)}`}>{selectedInspection.result}</span>
              </div>

              <h3 className="text-sm font-semibold mb-3">{t('modules:inspections.checklistItems', 'Checklist Items')}</h3>
              <div className="space-y-2">
                {inspectionItems?.length > 0 ? inspectionItems.map((item: any) => {
                  const itemStatus = item.status === 'conform' ? 'pass' : item.status === 'non_conform' ? 'fail' : item.status === 'na' ? 'na' : 'pending';
                  const statusColor = itemStatus === 'pass' ? 'text-green-600 bg-green-500/10' : itemStatus === 'fail' ? 'text-red-600 bg-red-500/10' : itemStatus === 'na' ? 'text-gray-500 bg-gray-500/10' : 'text-amber-600 bg-amber-500/10';
                  const StatusIcon = itemStatus === 'pass' ? CheckCircle : itemStatus === 'fail' ? AlertTriangle : FileText;
                  return (
                    <div key={item.id} className={`rounded-lg border p-3 ${itemStatus === 'fail' ? 'border-red-200 bg-red-500/5' : 'border-border'}`}>
                      <div className="flex items-start gap-3">
                        <StatusIcon className={`h-4 w-4 mt-0.5 flex-shrink-0 ${itemStatus === 'pass' ? 'text-green-500' : itemStatus === 'fail' ? 'text-red-500' : 'text-gray-400'}`} />
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium">{item.question || item.template_item?.question}</p>
                          {item.note && <p className="text-xs text-muted-foreground mt-1">{item.note}</p>}
                          {item.severity && <p className="text-xs text-muted-foreground">Severity: {item.severity}</p>}
                        </div>
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium flex-shrink-0 ${statusColor}`}>{itemStatus}</span>
                      </div>
                    </div>
                  );
                }) : (
                  <p className="text-sm text-muted-foreground text-center py-8">{t('modules:inspections.noItems', 'No checklist items recorded')}</p>
                )}
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('modules:inspections.newInspection', 'New Inspection')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('modules:inspections.templates', 'Template')} required>
            <select value={form.template_id} onChange={(e) => setForm({ ...form, template_id: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required>
              <option value="">{t('modules:inspections.selectTemplate', 'Select a template')}</option>
              {(templates || []).map((tpl: InspectionTemplate) => (
                <option key={tpl.id} value={tpl.id}>{tpl.name}</option>
              ))}
            </select>
          </FormField>
          <FormField label={t('modules:sor.location', 'Location')}>
            <input type="text" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" />
          </FormField>
          <FormField label={t('common:date')} required>
            <input type="date" value={form.scheduled_date} onChange={(e) => setForm({ ...form, scheduled_date: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <FormField label={t('common:notes')}>
            <textarea value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" rows={3} />
          </FormField>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('modules:inspections.newInspection', 'Create Inspection')} />
        </form>
      </Modal>
    </div>
  );
}
