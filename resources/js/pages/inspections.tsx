import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { Plus, ClipboardCheck, Settings, FileText, Wrench, Map, Truck, TrendingUp, AlertTriangle, CheckCircle } from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';
import { SkeletonTable } from '../components/skeleton';

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

const inspectionTemplates: InspectionTemplate[] = [
  {
    id: 'equipment-1',
    name: 'Equipment Safety Check',
    category: 'equipment',
    items: [
      { id: 'e1', question: 'Equipment has valid inspection certificate', required: true, weight: 20 },
      { id: 'e2', question: 'Safety guards are in place', required: true, weight: 20 },
      { id: 'e3', question: 'Emergency stop functional', required: true, weight: 25 },
      { id: 'e4', question: 'Operator trained and certified', required: true, weight: 20 },
      { id: 'e5', question: 'Maintenance log up to date', required: false, weight: 15 },
    ],
  },
  {
    id: 'area-1',
    name: 'Toilets & Sanitation',
    category: 'area',
    items: [
      { id: 'a1', question: 'Cleaning schedule posted', required: true, weight: 15 },
      { id: 'a2', question: 'Hand soap and sanitizer available', required: true, weight: 20 },
      { id: 'a3', question: 'Ventilation working', required: true, weight: 15 },
      { id: 'a4', question: 'No blocked drains', required: true, weight: 20 },
      { id: 'a5', question: 'Lighting adequate', required: false, weight: 15 },
      { id: 'a6', question: 'PPE available', required: true, weight: 15 },
    ],
  },
  {
    id: 'area-2',
    name: 'Base Vie (Living Quarters)',
    category: 'area',
    items: [
      { id: 'b1', question: 'Fire extinguishers accessible', required: true, weight: 20 },
      { id: 'b2', question: 'Emergency exits clear', required: true, weight: 20 },
      { id: 'b3', question: 'Electrical outlets safe', required: true, weight: 20 },
      { id: 'b4', question: 'First aid kit stocked', required: true, weight: 20 },
      { id: 'b5', question: 'Waste disposal organized', required: false, weight: 20 },
    ],
  },
  {
    id: 'area-3',
    name: 'Stockage Area',
    category: 'area',
    items: [
      { id: 's1', question: 'Materials properly stacked', required: true, weight: 25 },
      { id: 's2', question: 'Aisles clear and marked', required: true, weight: 20 },
      { id: 's3', question: 'Hazardous materials labeled', required: true, weight: 25 },
      { id: 's4', question: 'PPE signage visible', required: true, weight: 15 },
      { id: 's5', question: 'Spill kit available', required: true, weight: 15 },
    ],
  },
  {
    id: 'vehicle-1',
    name: 'Vehicle Pre-Start Check',
    category: 'vehicle',
    items: [
      { id: 'v1', question: 'Brakes functional', required: true, weight: 25 },
      { id: 'v2', question: 'Lights and signals working', required: true, weight: 20 },
      { id: 'v3', question: 'Tires in good condition', required: true, weight: 20 },
      { id: 'v4', question: 'Mirrors intact', required: true, weight: 15 },
      { id: 'v5', question: 'Seatbelt functional', required: true, weight: 20 },
    ],
  },
];

export default function InspectionsPage() {
  const { t } = useTranslation();
  const [showTemplateBuilder, setShowTemplateBuilder] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<'all' | 'equipment' | 'area' | 'vehicle'>('all');

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

  const getRiskLevel = (score: number) => {
    if (score >= 90) return { level: 'Low', color: 'text-green-500', icon: CheckCircle };
    if (score >= 70) return { level: 'Medium', color: 'text-amber-500', icon: TrendingUp };
    return { level: 'High', color: 'text-red-500', icon: AlertTriangle };
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
    ? inspectionTemplates 
    : inspectionTemplates.filter(t => t.category === selectedCategory);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('navigation.inspections')}</h1>
          <p className="text-muted-foreground">Standardized inspection system with templates & auto-scoring</p>
        </div>
        <div className="flex gap-2">
          <button 
            onClick={() => setShowTemplateBuilder(!showTemplateBuilder)}
            className="flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted"
          >
            <Settings className="h-4 w-4" />
            Template Builder
          </button>
          <button className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
            <Plus className="h-4 w-4" />
            New Inspection
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
              <h2 className="font-semibold">Inspection Templates</h2>
              <div className="flex gap-2">
                <button 
                  onClick={() => setSelectedCategory('all')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'all' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  All
                </button>
                <button 
                  onClick={() => setSelectedCategory('equipment')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'equipment' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  Equipment
                </button>
                <button 
                  onClick={() => setSelectedCategory('area')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'area' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  Areas
                </button>
                <button 
                  onClick={() => setSelectedCategory('vehicle')}
                  className={`rounded-lg px-3 py-1 text-xs font-medium ${selectedCategory === 'vehicle' ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted'}`}
                >
                  Vehicles
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
                    <p className="text-sm text-muted-foreground">{template.items.length} checklist items</p>
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
                  <th className="px-4 py-3 text-left text-sm font-medium">Reference</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Type</th>
                  <th className="px-4 py-3 text-left text-sm font-medium">Date</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Result</th>
                  <th className="px-4 py-3 text-right text-sm font-medium">Score</th>
                  <th className="px-4 py-3 text-center text-sm font-medium">Risk Level</th>
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
                      <td className="px-4 py-3 text-center">
                        <div className={`flex items-center justify-center gap-1 text-xs font-medium ${risk.color}`}>
                          <RiskIcon className="h-3 w-3" />
                          {risk.level}
                        </div>
                      </td>
                    </motion.tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="p-8">
            <EmptyState
              title="No inspections"
              description="Start by creating your first inspection using a template"
              action="Create Inspection"
              icon={<ClipboardCheck className="h-8 w-8" />}
              onAction={() => setShowTemplateBuilder(true)}
            />
          </div>
        )}
      </div>
    </div>
  );
}
