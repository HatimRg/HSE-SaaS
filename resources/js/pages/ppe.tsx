import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, HardHat } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';
import { Modal, FormField, FormActions } from '../components/modal';

export default function PpePage() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({ name: '', category: 'head', stock: '', unit_cost: '', min_stock: '' });

  const { data: items, isLoading } = useQuery({
    queryKey: ['ppe-items'],
    queryFn: async () => {
      try {
        const response = await api.get('/ppe/items');
        return response.data.data;
      } catch {
        return [];
      }
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof form) => {
      const response = await api.post('/ppe/items', data);
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['ppe-items'] });
      setShowModal(false);
      setForm({ name: '', category: 'head', stock: '', unit_cost: '', min_stock: '' });
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('navigation.ppe')}</h1>
          <p className="text-muted-foreground">Personal protective equipment inventory</p>
        </div>
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
          <Plus className="h-4 w-4" />
          {t('modules:ppe.addItem', 'Add Item')}
        </button>
      </div>

      <div className="rounded-xl border border-border bg-card">
        {isLoading ? (
          <div className="p-4">
            <SkeletonTable rows={5} />
          </div>
        ) : items?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-border bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium">Name</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Category</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Stock</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Unit Cost</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item: any, index: number) => (
                  <motion.tr
                    key={item.id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.05 }}
                    className="border-b border-border last:border-0 hover:bg-muted/30"
                  >
                    <td className="px-4 py-3 text-sm font-medium">{item.name}</td>
                    <td className="px-4 py-3 text-sm">{item.category}</td>
                    <td className="px-4 py-3 text-right text-sm">{item.stock || 0}</td>
                    <td className="px-4 py-3 text-right text-sm">${item.unit_cost}</td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              title={t('messages:empty.title')}
              description={t('modules:ppe.noData', 'Start by adding your first PPE item')}
              action={t('modules:ppe.addItem', 'Add Item')}
              icon={<HardHat className="h-8 w-8" />}
              onAction={() => setShowModal(true)}
            />
          </div>
        )}
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={t('modules:ppe.addItem', 'Add PPE Item')}>
        <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(form); }} className="space-y-4">
          <FormField label={t('modules:ppe.name', 'Item Name')} required>
            <input type="text" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required />
          </FormField>
          <FormField label={t('modules:ppe.category', 'Category')} required>
            <select value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background">
              <option value="head">{t('modules:ppe.head', 'Head Protection')}</option>
              <option value="eye">{t('modules:ppe.eye', 'Eye Protection')}</option>
              <option value="hand">{t('modules:ppe.hand', 'Hand Protection')}</option>
              <option value="foot">{t('modules:ppe.foot', 'Foot Protection')}</option>
              <option value="body">{t('modules:ppe.body', 'Body Protection')}</option>
              <option value="respiratory">{t('modules:ppe.respiratory', 'Respiratory')}</option>
            </select>
          </FormField>
          <div className="grid grid-cols-3 gap-4">
            <FormField label={t('modules:ppe.stock', 'Stock')} required>
              <input type="number" value={form.stock} onChange={(e) => setForm({ ...form, stock: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" required min="0" />
            </FormField>
            <FormField label={t('modules:ppe.unitCost', 'Unit Cost')}>
              <input type="number" step="0.01" value={form.unit_cost} onChange={(e) => setForm({ ...form, unit_cost: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" min="0" />
            </FormField>
            <FormField label={t('modules:ppe.minStock', 'Min Stock')}>
              <input type="number" value={form.min_stock} onChange={(e) => setForm({ ...form, min_stock: e.target.value })} className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background" min="0" />
            </FormField>
          </div>
          <FormActions onCancel={() => setShowModal(false)} onSubmit={() => createMutation.mutate(form)} isPending={createMutation.isPending} submitLabel={t('modules:ppe.addItem', 'Add Item')} />
        </form>
      </Modal>
    </div>
  );
}
