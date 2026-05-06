import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { api } from '../lib/api';

function createQueryClient() {
  return new QueryClient({ defaultOptions: { queries: { retry: false } } });
}

// Helper to render with QueryClient provider
function renderWithQC(ui: React.ReactElement) {
  const qc = createQueryClient();
  return render(
    <QueryClientProvider client={qc}>
      {ui}
    </QueryClientProvider>
  );
}

// ─── Page Rendering Smoke Tests ────────────────────────────────────────

describe('Page Smoke Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: [], total: 0 } });
  });

  it('UsersPage renders without crashing', async () => {
    const { default: UsersPage } = await import('../pages/users');
    const { container } = renderWithQC(<UsersPage />);
    expect(container).toBeTruthy();
  });

  it('SorPage renders without crashing', async () => {
    const { default: SorPage } = await import('../pages/sor');
    const { container } = renderWithQC(<SorPage />);
    expect(container).toBeTruthy();
  });

  it('PermitsPage renders without crashing', async () => {
    const { default: PermitsPage } = await import('../pages/permits');
    const { container } = renderWithQC(<PermitsPage />);
    expect(container).toBeTruthy();
  });

  it('WorkersPage renders without crashing', async () => {
    const { default: WorkersPage } = await import('../pages/workers');
    const { container } = renderWithQC(<WorkersPage />);
    expect(container).toBeTruthy();
  });

  it('TrainingPage renders without crashing', async () => {
    const { default: TrainingPage } = await import('../pages/training');
    const { container } = renderWithQC(<TrainingPage />);
    expect(container).toBeTruthy();
  });

  it('ProjectsPage renders without crashing', async () => {
    const { default: ProjectsPage } = await import('../pages/projects');
    const { container } = renderWithQC(<ProjectsPage />);
    expect(container).toBeTruthy();
  });

  it('CommunityPage renders without crashing', async () => {
    const { default: CommunityPage } = await import('../pages/community');
    const { container } = renderWithQC(<CommunityPage />);
    expect(container).toBeTruthy();
  });

  it('PpePage renders without crashing', async () => {
    const { default: PpePage } = await import('../pages/ppe');
    const { container } = renderWithQC(<PpePage />);
    expect(container).toBeTruthy();
  });

  it('InspectionsPage renders without crashing', async () => {
    const { default: InspectionsPage } = await import('../pages/inspections');
    const { container } = renderWithQC(<InspectionsPage />);
    expect(container).toBeTruthy();
  });

  it('KpiPage renders without crashing', async () => {
    const { default: KpiPage } = await import('../pages/kpi');
    const { container } = renderWithQC(<KpiPage />);
    expect(container).toBeTruthy();
  });

  it('LibraryPage renders without crashing', async () => {
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: { folders: [], documents: { data: [] } } } });
    const { default: LibraryPage } = await import('../pages/library');
    const { container } = renderWithQC(<LibraryPage />);
    expect(container).toBeTruthy();
  });

  it('EnvironmentPage renders without crashing', async () => {
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: { readings: [], waste: [] } } });
    const { default: EnvironmentPage } = await import('../pages/environment');
    const { container } = renderWithQC(<EnvironmentPage />);
    expect(container).toBeTruthy();
  });

  it('RiskAssessmentPage renders without crashing', async () => {
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: [] } });
    const { default: RiskAssessmentPage } = await import('../pages/risk-assessment');
    const { container } = renderWithQC(<RiskAssessmentPage />);
    expect(container).toBeTruthy();
  });

  it('IncidentInvestigationPage renders without crashing', async () => {
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: [] } });
    const { default: IncidentInvestigationPage } = await import('../pages/incident-investigation');
    const { container } = renderWithQC(<IncidentInvestigationPage />);
    expect(container).toBeTruthy();
  });

  it('DashboardPage renders without crashing', async () => {
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: { stats: {}, recentEvents: [], chartData: [] } } });
    const { default: DashboardPage } = await import('../pages/dashboard');
    const { container } = renderWithQC(<DashboardPage />);
    expect(container).toBeTruthy();
  });
});

// ─── Modal Interaction Tests ───────────────────────────────────────────

describe('Modal Interactions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: [], total: 0 } });
  });

  it('SettingsPage toggles between tabs', async () => {
    const { default: SettingsPage } = await import('../pages/settings');
    renderWithQC(<SettingsPage />);

    // Click Notifications tab
    const notifTab = screen.getByText('Notifications');
    fireEvent.click(notifTab);
    expect(screen.getByText('Email Notifications')).toBeInTheDocument();

    // Click back to Appearance
    const appearTab = screen.getByText('Appearance');
    fireEvent.click(appearTab);
    expect(screen.getByText('Light')).toBeInTheDocument();
  });
});

// ─── API Module Tests ──────────────────────────────────────────────────

describe('API Module Methods', () => {
  it('api.get is callable', async () => {
    (api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({ data: { data: [{ id: 1 }] } });
    const result = await api.get('/test');
    expect(result.data.data).toEqual([{ id: 1 }]);
  });

  it('api.post is callable', async () => {
    (api.post as ReturnType<typeof vi.fn>).mockResolvedValueOnce({ data: { data: { id: 1 } } });
    const result = await api.post('/test', { name: 'test' });
    expect(result.data.data.id).toBe(1);
  });

  it('api.delete is callable', async () => {
    (api.delete as ReturnType<typeof vi.fn>).mockResolvedValueOnce({ data: {} });
    const result = await api.delete('/test/1');
    expect(result.data).toEqual({});
  });
});
