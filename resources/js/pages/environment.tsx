import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import {
  LineChart, Line, BarChart, Bar,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts';
import {
  Leaf, Droplets, Wind, ThermometerSun, Volume2, Recycle,
  Plus, AlertTriangle, TrendingUp,
} from 'lucide-react';
import { api } from '../lib/api';
import { useTheme } from '../components/theme-provider';
import { Modal } from '../components/modal';
import toast from 'react-hot-toast';

const CHART_COLORS = { primary: '#1e5f9e', info: '#2980b9', success: '#2d7a4f', warning: '#b87333', danger: '#c0392b' };

function ChartTooltip({ active, payload, label }: any) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg border border-border bg-card p-3 shadow-xl">
      {label && <p className="mb-1 text-xs font-medium text-muted-foreground">{label}</p>}
      {payload.map((entry: any, idx: number) => (
        <div key={idx} className="flex items-center gap-2 text-sm">
          <span className="h-2 w-2 rounded-full" style={{ backgroundColor: entry.color }} />
          <span className="text-muted-foreground">{entry.name}:</span>
          <span className="font-semibold">{typeof entry.value === 'number' ? entry.value.toLocaleString() : entry.value}</span>
        </div>
      ))}
    </div>
  );
}

type Tab = 'overview' | 'readings' | 'waste';

export default function EnvironmentPage() {
  const { t } = useTranslation();
  const { isDark } = useTheme();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<Tab>('overview');
  const [showReadingModal, setShowReadingModal] = useState(false);
  const [showWasteModal, setShowWasteModal] = useState(false);
  const [filterType, setFilterType] = useState('');

  const { data: metrics } = useQuery({
    queryKey: ['environment'],
    queryFn: async () => { try { const r = await api.get('/environment'); return r.data.data; } catch { return null; } },
  });

  const { data: readings } = useQuery({
    queryKey: ['environment-readings', filterType],
    queryFn: async () => {
      try { const r = await api.get('/environment/readings', { params: filterType ? { type: filterType } : {} }); return r.data.data; }
      catch { return null; }
    },
    enabled: activeTab === 'readings',
  });

  const { data: waste } = useQuery({
    queryKey: ['environment-waste'],
    queryFn: async () => { try { const r = await api.get('/environment/waste'); return r.data.data; } catch { return null; } },
    enabled: activeTab === 'waste',
  });

  const { data: charts } = useQuery({
    queryKey: ['environment-charts'],
    queryFn: async () => { try { const r = await api.get('/environment/charts'); return r.data.data; } catch { return null; } },
  });

  const addReading = useMutation({
    mutationFn: async (data: any) => (await api.post('/environment/readings', data)).data,
    onSuccess: () => { toast.success(t('messages:success', 'Reading recorded')); setShowReadingModal(false); queryClient.invalidateQueries({ queryKey: ['environment'] }); },
    onError: () => toast.error(t('messages:error', 'Failed to record')),
  });

  const addWaste = useMutation({
    mutationFn: async (data: any) => (await api.post('/environment/waste', data)).data,
    onSuccess: () => { toast.success(t('messages:success', 'Waste recorded')); setShowWasteModal(false); queryClient.invalidateQueries({ queryKey: ['environment-waste'] }); },
    onError: () => toast.error(t('messages:error', 'Failed to record')),
  });

  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
  const axisColor = isDark ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.3)';

  const metricCards = [
    { key: 'air_quality', label: t('modules:environment.airQuality', 'Air Quality'), icon: Wind, color: 'text-green-500', bgColor: 'bg-green-500/10' },
    { key: 'noise_level', label: t('modules:environment.noiseLevel', 'Noise Level'), icon: Volume2, color: 'text-amber-500', bgColor: 'bg-amber-500/10', unit: 'dB' },
    { key: 'waste_diversion', label: t('modules:environment.wasteDiversion', 'Waste Diversion'), icon: Recycle, color: 'text-blue-500', bgColor: 'bg-blue-500/10', unit: '%' },
    { key: 'water_usage', label: t('modules:environment.waterUsage', 'Water Usage'), icon: Droplets, color: 'text-cyan-500', bgColor: 'bg-cyan-500/10' },
    { key: 'temperature', label: t('modules:environment.temperature', 'Temperature'), icon: ThermometerSun, color: 'text-red-500', bgColor: 'bg-red-500/10', unit: '°C' },
    { key: 'emissions', label: t('modules:environment.emissions', 'CO₂ Emissions'), icon: Leaf, color: 'text-emerald-500', bgColor: 'bg-emerald-500/10' },
  ];

  const readingTypes = ['noise','dust_pm10','dust_pm25','water_ph','water_turbidity','air_quality_aqi','vibration','temperature','humidity','electricity_kwh','water_consumption'];
  const wasteTypes = ['construction_debris','hazardous','metal','concrete','wood','plastic','chemical','asbestos','general','other'];
  const tabs: { key: Tab; label: string }[] = [
    { key: 'overview', label: t('modules:environment.overview', 'Overview') },
    { key: 'readings', label: t('modules:environment.readings', 'Readings') },
    { key: 'waste', label: t('modules:environment.waste', 'Waste Exports') },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{t('modules:environment.title', 'Environment')}</h1>
          <p className="text-muted-foreground text-sm mt-1">{t('modules:environment.subtitle', 'Environmental monitoring and compliance')}</p>
        </div>
        <div className="flex items-center gap-2">
          {activeTab === 'readings' && (
            <button onClick={() => setShowReadingModal(true)} className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
              <Plus className="h-4 w-4" /> {t('modules:environment.addReading', 'Add Reading')}
            </button>
          )}
          {activeTab === 'waste' && (
            <button onClick={() => setShowWasteModal(true)} className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity">
              <Plus className="h-4 w-4" /> {t('modules:environment.addWaste', 'Add Waste Export')}
            </button>
          )}
        </div>
      </div>

      <div className="flex rounded-lg border border-border overflow-hidden w-fit">
        {tabs.map(tab => (
          <button key={tab.key} onClick={() => setActiveTab(tab.key)} className={`px-4 py-2 text-sm font-medium transition-colors ${activeTab === tab.key ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'}`}>
            {tab.label}
          </button>
        ))}
      </div>

      {activeTab === 'overview' && (
        <div className="space-y-6">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {metricCards.map((m, i) => {
              const value = metrics?.[m.key] ?? 0;
              return (
                <motion.div key={m.key} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }} className="rounded-xl border border-border bg-card p-5">
                  <div className="flex items-center gap-3 mb-3">
                    <div className={`rounded-lg ${m.bgColor} p-2`}><m.icon className={`h-5 w-5 ${m.color}`} /></div>
                    <span className="text-sm font-medium text-muted-foreground">{m.label}</span>
                  </div>
                  <p className="text-3xl font-bold tracking-tight">
                    {typeof value === 'number' ? value : 0}
                    {m.unit && <span className="text-lg text-muted-foreground ml-1">{m.unit}</span>}
                  </p>
                </motion.div>
              );
            })}
          </div>
          {charts && (
            <div className="grid gap-6 lg:grid-cols-2">
              {charts.readings_trend?.length > 0 && (
                <div className="rounded-xl border border-border bg-card p-5">
                  <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><TrendingUp className="h-4 w-4 text-blue-500" /> {t('modules:environment.trends', 'Readings Trend')}</h3>
                  <ResponsiveContainer width="100%" height={300}>
                    <LineChart data={charts.readings_trend[0]?.data || []}>
                      <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
                      <XAxis dataKey="date" tick={{ fill: axisColor, fontSize: 11 }} /><YAxis tick={{ fill: axisColor, fontSize: 11 }} />
                      <Tooltip content={<ChartTooltip />} />
                      <Line type="monotone" dataKey="value" stroke={CHART_COLORS.primary} strokeWidth={2} dot={{ r: 3 }} />
                    </LineChart>
                  </ResponsiveContainer>
                </div>
              )}
              {charts.waste_breakdown?.length > 0 && (
                <div className="rounded-xl border border-border bg-card p-5">
                  <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Recycle className="h-4 w-4 text-green-500" /> {t('modules:environment.wasteBreakdown', 'Waste Breakdown')}</h3>
                  <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={charts.waste_breakdown}>
                      <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
                      <XAxis dataKey="waste_type" tick={{ fill: axisColor, fontSize: 10 }} /><YAxis tick={{ fill: axisColor, fontSize: 11 }} />
                      <Tooltip content={<ChartTooltip />} />
                      <Bar dataKey="total" fill={CHART_COLORS.info} radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              )}
              {charts.exceedances?.length > 0 && (
                <div className="rounded-xl border border-border bg-card p-5 lg:col-span-2">
                  <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><AlertTriangle className="h-4 w-4 text-amber-500" /> {t('modules:environment.exceedances', 'Threshold Exceedances')}</h3>
                  <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {charts.exceedances.map((exc: any, i: number) => (
                      <div key={i} className="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3">
                        <p className="text-xs text-muted-foreground">{exc.type}</p>
                        <p className="text-xl font-bold text-amber-600">{exc.count}</p>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {activeTab === 'readings' && (
        <div className="space-y-4">
          <select value={filterType} onChange={e => setFilterType(e.target.value)} className="rounded-lg border border-border bg-background px-3 py-2 text-sm">
            <option value="">{t('modules:environment.allTypes', 'All Types')}</option>
            {readingTypes.map(rt => <option key={rt} value={rt}>{rt.replace(/_/g, ' ')}</option>)}
          </select>
          <div className="rounded-xl border border-border bg-card overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-muted/50">
                  <tr>
                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Type</th>
                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Value</th>
                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Unit</th>
                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Location</th>
                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Measured At</th>
                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {readings?.items?.length > 0 ? readings.items.map((r: any) => (
                    <tr key={r.id} className="hover:bg-muted/30 transition-colors">
                      <td className="px-4 py-3 font-medium">{r.type?.replace(/_/g, ' ')}</td>
                      <td className="px-4 py-3">{r.value}</td>
                      <td className="px-4 py-3 text-muted-foreground">{r.unit}</td>
                      <td className="px-4 py-3 text-muted-foreground">{r.location || '—'}</td>
                      <td className="px-4 py-3 text-muted-foreground">{r.measured_at ? new Date(r.measured_at).toLocaleString() : '—'}</td>
                      <td className="px-4 py-3">
                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${r.is_exceedance ? 'bg-red-500/10 text-red-600' : 'bg-green-500/10 text-green-600'}`}>
                          {r.is_exceedance ? 'Exceedance' : 'Normal'}
                        </span>
                      </td>
                    </tr>
                  )) : (
                    <tr><td colSpan={6} className="px-4 py-12 text-center text-muted-foreground">{t('modules:environment.noReadings', 'No readings recorded yet')}</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'waste' && (
        <div className="rounded-xl border border-border bg-card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-muted/50">
                <tr>
                  <th className="px-4 py-3 text-left font-medium text-muted-foreground">Date</th>
                  <th className="px-4 py-3 text-left font-medium text-muted-foreground">Type</th>
                  <th className="px-4 py-3 text-left font-medium text-muted-foreground">Quantity</th>
                  <th className="px-4 py-3 text-left font-medium text-muted-foreground">Treatment</th>
                  <th className="px-4 py-3 text-left font-medium text-muted-foreground">Hazardous</th>
                  <th className="px-4 py-3 text-left font-medium text-muted-foreground">Carrier</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {waste?.items?.length > 0 ? waste.items.map((w: any) => (
                  <tr key={w.id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-3">{w.date}</td>
                    <td className="px-4 py-3 font-medium">{w.waste_type?.replace(/_/g, ' ')}</td>
                    <td className="px-4 py-3">{w.quantity} {w.unit}</td>
                    <td className="px-4 py-3 text-muted-foreground">{w.treatment || '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${w.is_hazardous ? 'bg-red-500/10 text-red-600' : 'bg-green-500/10 text-green-600'}`}>
                        {w.is_hazardous ? 'Yes' : 'No'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{w.carrier_name || '—'}</td>
                  </tr>
                )) : (
                  <tr><td colSpan={6} className="px-4 py-12 text-center text-muted-foreground">{t('modules:environment.noWaste', 'No waste exports recorded yet')}</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {showReadingModal && (
        <Modal isOpen={showReadingModal} onClose={() => setShowReadingModal(false)} title={t('modules:environment.addReading', 'Add Reading')}>
          <form onSubmit={e => { e.preventDefault(); const fd = new FormData(e.currentTarget); addReading.mutate({ project_id: fd.get('project_id'), type: fd.get('type'), value: fd.get('value'), unit: fd.get('unit'), location: fd.get('location'), measured_at: fd.get('measured_at'), }); }} className="space-y-4">
            <div><label className="block text-sm font-medium mb-1">{t('modules:environment.type')}</label><select name="type" required className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">{readingTypes.map(rt => <option key={rt} value={rt}>{rt.replace(/_/g, ' ')}</option>)}</select></div>
            <div className="grid grid-cols-2 gap-3">
              <div><label className="block text-sm font-medium mb-1">{t('modules:environment.value')}</label><input name="value" type="number" step="any" required className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" /></div>
              <div><label className="block text-sm font-medium mb-1">{t('modules:environment.unit')}</label><input name="unit" type="text" required className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="dB, µg/m³, pH..." /></div>
            </div>
            <div><label className="block text-sm font-medium mb-1">{t('modules:environment.location')}</label><input name="location" type="text" className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" /></div>
            <div><label className="block text-sm font-medium mb-1">{t('modules:environment.measuredAt')}</label><input name="measured_at" type="datetime-local" required className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" /></div>
            <div className="flex justify-end gap-3 pt-2">
              <button type="button" onClick={() => setShowReadingModal(false)} className="rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted transition-colors">{t('common:cancel')}</button>
              <button type="submit" disabled={addReading.isPending} className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-50">{addReading.isPending ? t('common:saving') : t('common:record')}</button>
            </div>
          </form>
        </Modal>
      )}

      {showWasteModal && (
        <Modal isOpen={showWasteModal} onClose={() => setShowWasteModal(false)} title={t('modules:environment.addWaste', 'Add Waste Export')}>
          <form onSubmit={e => { e.preventDefault(); const fd = new FormData(e.currentTarget); addWaste.mutate({ project_id: fd.get('project_id'), date: fd.get('date'), waste_type: fd.get('waste_type'), quantity: fd.get('quantity'), unit: fd.get('unit') || 'tonnes', treatment: fd.get('treatment') || undefined, is_hazardous: fd.get('is_hazardous') === 'on', carrier_name: fd.get('carrier_name') || undefined, }); }} className="space-y-4">
            <div><label className="block text-sm font-medium mb-1">{t('modules:environment.date')}</label><input name="date" type="date" required className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" /></div>
            <div><label className="block text-sm font-medium mb-1">{t('modules:environment.wasteType')}</label><select name="waste_type" required className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">{wasteTypes.map(wt => <option key={wt} value={wt}>{wt.replace(/_/g, ' ')}</option>)}</select></div>
            <div className="grid grid-cols-2 gap-3">
              <div><label className="block text-sm font-medium mb-1">{t('modules:environment.quantity')}</label><input name="quantity" type="number" step="any" min="0" required className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" /></div>
              <div><label className="block text-sm font-medium mb-1">{t('modules:environment.unit')}</label><input name="unit" type="text" defaultValue="tonnes" className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" /></div>
            </div>
            <div><label className="block text-sm font-medium mb-1">{t('modules:environment.treatment')}</label><select name="treatment" className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm"><option value="">—</option>{['recycling','landfill','incineration','reuse','other'].map(tr => <option key={tr} value={tr}>{tr}</option>)}</select></div>
            <div><label className="block text-sm font-medium mb-1">{t('modules:environment.carrier')}</label><input name="carrier_name" type="text" className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" /></div>
            <label className="flex items-center gap-2 text-sm"><input name="is_hazardous" type="checkbox" className="h-4 w-4 rounded border-input" /> {t('modules:environment.hazardousWaste')}</label>
            <div className="flex justify-end gap-3 pt-2">
              <button type="button" onClick={() => setShowWasteModal(false)} className="rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted transition-colors">{t('common:cancel')}</button>
              <button type="submit" disabled={addWaste.isPending} className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-50">{addWaste.isPending ? t('common:saving') : t('common:record')}</button>
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
}
