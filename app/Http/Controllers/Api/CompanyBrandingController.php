<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\CompanyBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyBrandingController extends BaseController
{
    /**
     * Get company branding configuration
     */
    public function show(Request $request)
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return $this->errorResponse('No company assigned', 403);
        }

        $branding = $company->branding ?? $this->getDefaultBranding($company);

        return $this->successResponse($branding);
    }

    /**
     * Update company branding
     */
    public function update(Request $request)
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return $this->errorResponse('No company assigned', 403);
        }

        $validated = $request->validate([
            'logo_light' => 'nullable|image|max:2048',
            'logo_dark' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:512',
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'primary_color_dark' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color_dark' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color_dark' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'custom_css' => 'nullable|string|max:10000',
            'navigation_items' => 'nullable|array',
            'navigation_items.*.label' => 'required|string|max:50',
            'navigation_items.*.route' => 'required|string|max:100',
            'navigation_items.*.icon' => 'required|string|max:50',
            'navigation_items.*.order' => 'required|integer|min:0',
            'navigation_items.*.enabled' => 'required|boolean',
        ]);

        // Handle file uploads
        $logoData = [];
        if ($request->hasFile('logo_light')) {
            $logoData['logo_light'] = $request->file('logo_light')->store('company-logos', 'public');
        }
        if ($request->hasFile('logo_dark')) {
            $logoData['logo_dark'] = $request->file('logo_dark')->store('company-logos', 'public');
        }
        if ($request->hasFile('favicon')) {
            $logoData['favicon'] = $request->file('favicon')->store('company-favicons', 'public');
        }

        // Update or create branding
        $branding = $company->branding()->updateOrCreate(
            ['company_id' => $company->id],
            array_merge($validated, $logoData)
        );

        // Clear cache for this company
        Cache::forget("company_branding_{$company->id}");

        return $this->successResponse($branding, 'Branding updated successfully');
    }

    /**
     * Get default branding for new companies
     */
    private function getDefaultBranding(Company $company)
    {
        return [
            'company_id' => $company->id,
            'logo_light' => null,
            'logo_dark' => null,
            'favicon' => null,
            'primary_color' => '#2563eb', // Blue
            'background_color' => '#ffffff',
            'accent_color' => '#10b981', // Green
            'primary_color_dark' => '#3b82f6',
            'background_color_dark' => '#111827',
            'accent_color_dark' => '#34d399',
            'custom_css' => '',
            'navigation_items' => $this->getDefaultNavigation(),
        ];
    }

    /**
     * Get default navigation items
     */
    private function getDefaultNavigation()
    {
        return [
            ['label' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'LayoutDashboard', 'order' => 1, 'enabled' => true],
            ['label' => 'Admin Dashboard', 'route' => '/admin', 'icon' => 'BarChart3', 'order' => 2, 'enabled' => true],
            ['label' => 'Enterprise', 'route' => '/enterprise', 'icon' => 'Activity', 'order' => 3, 'enabled' => true],
            ['label' => 'OSHA Compliance', 'route' => '/osha', 'icon' => 'Shield', 'order' => 4, 'enabled' => true],
            ['label' => 'Risk Assessment', 'route' => '/risk', 'icon' => 'AlertTriangle', 'order' => 5, 'enabled' => true],
            ['label' => 'Investigations', 'route' => '/investigation', 'icon' => 'Search', 'order' => 6, 'enabled' => true],
            ['label' => 'KPI Reports', 'route' => '/kpi', 'icon' => 'TrendingUp', 'order' => 7, 'enabled' => true],
            ['label' => 'Safety Reports', 'route' => '/sor', 'icon' => 'FileText', 'order' => 8, 'enabled' => true],
            ['label' => 'Work Permits', 'route' => '/permits', 'icon' => 'FileCheck', 'order' => 9, 'enabled' => true],
            ['label' => 'Inspections', 'route' => '/inspections', 'icon' => 'Eye', 'order' => 10, 'enabled' => true],
            ['label' => 'Workers', 'route' => '/workers', 'icon' => 'Users', 'order' => 11, 'enabled' => true],
            ['label' => 'Training', 'route' => '/training', 'icon' => 'GraduationCap', 'order' => 12, 'enabled' => true],
            ['label' => 'PPE', 'route' => '/ppe', 'icon' => 'Shield', 'order' => 13, 'enabled' => true],
            ['label' => 'Library', 'route' => '/library', 'icon' => 'BookOpen', 'order' => 14, 'enabled' => true],
            ['label' => 'Community', 'route' => '/community', 'icon' => 'Users', 'order' => 15, 'enabled' => true],
            ['label' => 'Settings', 'route' => '/settings', 'icon' => 'Settings', 'order' => 16, 'enabled' => true],
        ];
    }

    /**
     * Preview branding changes
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'primary_color_dark' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color_dark' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color_dark' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // Generate CSS variables for preview
        $css = $this->generateCSSVariables($validated);

        return $this->successResponse(['css' => $css]);
    }

    /**
     * Generate CSS variables from branding data
     */
    private function generateCSSVariables(array $branding)
    {
        return "
            :root {
                --brand-primary: {$branding['primary_color']};
                --brand-background: {$branding['background_color']};
                --brand-accent: {$branding['accent_color']};
            }
            
            [data-theme='dark'] {
                --brand-primary: {$branding['primary_color_dark']};
                --brand-background: {$branding['background_color_dark']};
                --brand-accent: {$branding['accent_color_dark']};
            }
        ";
    }

    /**
     * Reset branding to defaults
     */
    public function reset(Request $request)
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return $this->errorResponse('No company assigned', 403);
        }

        // Delete existing branding
        $company->branding()->delete();

        // Clear cache
        Cache::forget("company_branding_{$company->id}");

        return $this->successResponse($this->getDefaultBranding($company), 'Branding reset to defaults');
    }

    /**
     * Export branding configuration
     */
    public function export(Request $request)
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return $this->errorResponse('No company assigned', 403);
        }

        $branding = $company->branding ?? $this->getDefaultBranding($company);

        // Remove file paths from export
        unset($branding['logo_light'], $branding['logo_dark'], $branding['favicon']);

        return response()->json($branding, 200, [
            'Content-Disposition' => 'attachment; filename="branding-config.json"',
        ]);
    }

    /**
     * Import branding configuration
     */
    public function import(Request $request)
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return $this->errorResponse('No company assigned', 403);
        }

        $validated = $request->validate([
            'config' => 'required|json',
        ]);

        $config = json_decode($validated['config'], true);

        // Validate required fields
        $required = ['primary_color', 'background_color', 'accent_color'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return $this->errorResponse("Missing required field: {$field}", 400);
            }
        }

        // Update branding
        $branding = $company->branding()->updateOrCreate(
            ['company_id' => $company->id],
            $config
        );

        // Clear cache
        Cache::forget("company_branding_{$company->id}");

        return $this->successResponse($branding, 'Branding imported successfully');
    }

    /**
     * Upload company logo.
     */
    public function uploadLogo(Request $request)
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $path = $request->file('logo')->store('company-logos', 'public');
        $company->update(['logo' => $path]);

        $this->logActivity('company_logo_uploaded', $company);

        return $this->successResponse($company->fresh(), 'Logo uploaded successfully');
    }

    /**
     * Remove company logo.
     */
    public function removeLogo(Request $request)
    {
        $company = auth()->user()->company;

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
            $company->update(['logo' => null]);
        }

        $this->logActivity('company_logo_removed', $company);

        return $this->successResponse($company->fresh(), 'Logo removed successfully');
    }
}
