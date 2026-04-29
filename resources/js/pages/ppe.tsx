import React from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus, HardHat } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

export default function PpePage() {
  const { t } = useTranslation();

  const { data: items, isLoading } = useQuery({
    queryKey: ['ppe-items'],
    queryFn: async () => {
      const response = await api.get('/ppe/items');
      return response.data.data;
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('navigation.ppe')}</h1>
          <p className="text-muted-foreground">Personal protective equipment inventory</p>
        </div>
        <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-dark">
          <Plus className="h-4 w-4" />
          Add Item
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
              title="No PPE items"
              description="Start by adding your first PPE item"
              action="Add Item"
              icon={<HardHat className="h-8 w-8" />}
              onAction={() => {}}
            />
          </div>
        )}
      </div>
    </div>
  );
}
