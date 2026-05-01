<?php

namespace App\Http\Controllers\Api;

use App\Models\PpeItem;
use App\Models\PpeStock;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PpeController extends BaseController
{
    /**
     * Display a listing of PPE items.
     */
    public function index(Request $request)
    {
        $items = PpeItem::with(['category', 'projectStocks'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('ppe_category_id', $categoryId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('name')
            ->paginate(20);

        return $this->successResponse($items);
    }

    /**
     * Store a newly created PPE item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reference' => 'required|string|max:100|unique:ppe_items,reference',
            'description' => 'nullable|string',
            'ppe_category_id' => 'required|exists:ppe_categories,id',
            'unit_of_measure' => 'required|string|max:50',
            'min_stock_level' => 'required|integer|min:0',
            'max_stock_level' => 'required|integer|min:1',
            'reorder_quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_reference' => 'nullable|string|max:100',
            'certification_required' => 'boolean',
            'certification_expiry' => 'nullable|date',
            'storage_requirements' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'safety_notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'datasheet' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $ppeItem = PpeItem::create($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ppe-images', 'public');
            $ppeItem->update(['image_path' => $path]);
        }

        // Handle datasheet upload
        if ($request->hasFile('datasheet')) {
            $path = $request->file('datasheet')->store('ppe-datasheets', 'public');
            $ppeItem->update(['datasheet_path' => $path]);
        }

        $ppeItem->load(['category']);

        $this->logActivity('ppe_item_created', $ppeItem, $validated);

        return $this->successResponse($ppeItem, 'PPE item created successfully');
    }

    /**
     * Display the specified PPE item.
     */
    public function show(PpeItem $ppeItem)
    {
        $ppeItem->load(['category', 'projectStocks.project', 'issuances.worker']);

        return $this->successResponse($ppeItem);
    }

    /**
     * Update the specified PPE item.
     */
    public function update(Request $request, PpeItem $ppeItem)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'reference' => ['sometimes', 'string', 'max:100', Rule::unique('ppe_items', 'reference')->ignore($ppeItem->id)],
            'description' => 'nullable|string',
            'ppe_category_id' => 'sometimes|exists:ppe_categories,id',
            'unit_of_measure' => 'sometimes|string|max:50',
            'min_stock_level' => 'sometimes|integer|min:0',
            'max_stock_level' => 'sometimes|integer|min:1',
            'reorder_quantity' => 'sometimes|integer|min:1',
            'unit_cost' => 'sometimes|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_reference' => 'nullable|string|max:100',
            'certification_required' => 'boolean',
            'certification_expiry' => 'nullable|date',
            'storage_requirements' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'safety_notes' => 'nullable|string',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'discontinued'])],
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'datasheet' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $ppeItem->update($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($ppeItem->image_path) {
                Storage::disk('public')->delete($ppeItem->image_path);
            }
            $path = $request->file('image')->store('ppe-images', 'public');
            $ppeItem->update(['image_path' => $path]);
        }

        // Handle datasheet upload
        if ($request->hasFile('datasheet')) {
            // Delete old datasheet
            if ($ppeItem->datasheet_path) {
                Storage::disk('public')->delete($ppeItem->datasheet_path);
            }
            $path = $request->file('datasheet')->store('ppe-datasheets', 'public');
            $ppeItem->update(['datasheet_path' => $path]);
        }

        $ppeItem->load(['category']);

        $this->logActivity('ppe_item_updated', $ppeItem, $validated);

        return $this->successResponse($ppeItem, 'PPE item updated successfully');
    }

    /**
     * Remove the specified PPE item.
     */
    public function destroy(PpeItem $ppeItem)
    {
        // Delete associated files
        if ($ppeItem->image_path) {
            Storage::disk('public')->delete($ppeItem->image_path);
        }
        if ($ppeItem->datasheet_path) {
            Storage::disk('public')->delete($ppeItem->datasheet_path);
        }

        $ppeItem->delete();

        $this->logActivity('ppe_item_deleted', $ppeItem);

        return $this->successResponse(null, 'PPE item deleted successfully');
    }

    /**
     * Get project stock levels.
     */
    public function projectStocks(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $stocks = PpeStock::with(['ppeItem.category', 'project'])
            ->where('project_id', $validated['project_id'])
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'ppe_item' => $stock->ppeItem,
                    'current_quantity' => $stock->current_quantity,
                    'available_quantity' => $stock->available_quantity,
                    'issued_quantity' => $stock->issued_quantity,
                    'min_stock_level' => $stock->ppeItem->min_stock_level,
                    'stock_status' => $stock->getStockStatus(),
                    'last_updated' => $stock->updated_at,
                ];
            });

        return $this->successResponse($stocks);
    }

    /**
     * Issue PPE to worker.
     */
    public function issuePpe(Request $request)
    {
        $validated = $request->validate([
            'ppe_item_id' => 'required|exists:ppe_items,id',
            'worker_id' => 'required|exists:workers,id',
            'project_id' => 'required|exists:projects,id',
            'quantity' => 'required|integer|min:1',
            'issue_reason' => 'nullable|string',
            'expected_return_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
        ]);

        $ppeItem = PpeItem::findOrFail($validated['ppe_item_id']);
        $stock = PpeStock::where('ppe_item_id', $validated['ppe_item_id'])
                        ->where('project_id', $validated['project_id'])
                        ->firstOrFail();

        if ($stock->available_quantity < $validated['quantity']) {
            return $this->errorResponse('Insufficient stock available', 400);
        }

        // Create issuance record
        $issuance = $stock->issuances()->create([
            'worker_id' => $validated['worker_id'],
            'quantity_issued' => $validated['quantity'],
            'issue_reason' => $validated['issue_reason'] ?? null,
            'expected_return_date' => $validated['expected_return_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'issued_by' => auth()->id(),
            'issued_at' => now(),
        ]);

        // Update stock
        $stock->update([
            'issued_quantity' => $stock->issued_quantity + $validated['quantity'],
            'available_quantity' => $stock->available_quantity - $validated['quantity'],
        ]);

        $issuance->load(['worker', 'ppeItem', 'project']);

        $this->logActivity('ppe_issued', $stock, $validated);

        return $this->successResponse($issuance, 'PPE issued successfully');
    }

    /**
     * Return PPE from worker.
     */
    public function returnPpe(Request $request, $issuanceId)
    {
        $validated = $request->validate([
            'quantity_returned' => 'required|integer|min:1',
            'return_condition' => 'required|in:excellent,good,fair,poor,damaged',
            'damage_notes' => 'nullable|string',
            'cleaning_required' => 'boolean',
            'repair_required' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $issuance = \App\Models\PpeIssuance::findOrFail($issuanceId);
        $stock = $issuance->stock;

        if ($validated['quantity_returned'] > $issuance->quantity_issued - $issuance->quantity_returned) {
            return $this->errorResponse('Cannot return more than issued quantity', 400);
        }

        // Create return record
        $return = $issuance->returns()->create([
            'quantity_returned' => $validated['quantity_returned'],
            'return_condition' => $validated['return_condition'],
            'damage_notes' => $validated['damage_notes'] ?? null,
            'cleaning_required' => $validated['cleaning_required'] ?? false,
            'repair_required' => $validated['repair_required'] ?? false,
            'notes' => $validated['notes'] ?? null,
            'received_by' => auth()->id(),
            'received_at' => now(),
        ]);

        // Update stock
        $stock->update([
            'issued_quantity' => $stock->issued_quantity - $validated['quantity_returned'],
            'available_quantity' => $stock->available_quantity + $validated['quantity_returned'],
        ]);

        // Update issuance
        $issuance->increment('quantity_returned', $validated['quantity_returned']);

        if ($issuance->quantity_returned >= $issuance->quantity_issued) {
            $issuance->update(['status' => 'returned']);
        }

        $return->load(['issuance.worker', 'ppeItem']);

        $this->logActivity('ppe_returned', $stock, $validated);

        return $this->successResponse($return, 'PPE returned successfully');
    }

    /**
     * Adjust stock levels.
     */
    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'ppe_item_id' => 'required|exists:ppe_items,id',
            'project_id' => 'required|exists:projects,id',
            'adjustment_type' => 'required|in:add,remove,adjust',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string',
            'reference_document' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $stock = PpeStock::where('ppe_item_id', $validated['ppe_item_id'])
                        ->where('project_id', $validated['project_id'])
                        ->firstOrFail();

        $adjustment = $stock->adjustments()->create([
            'adjustment_type' => $validated['adjustment_type'],
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'],
            'reference_document' => $validated['reference_document'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'adjusted_by' => auth()->id(),
            'adjusted_at' => now(),
            'previous_quantity' => $stock->current_quantity,
        ]);

        // Update stock based on adjustment type
        switch ($validated['adjustment_type']) {
            case 'add':
                $stock->increment('current_quantity', $validated['quantity']);
                $stock->increment('available_quantity', $validated['quantity']);
                break;
            case 'remove':
                if ($stock->current_quantity < $validated['quantity']) {
                    return $this->errorResponse('Insufficient stock to remove', 400);
                }
                $stock->decrement('current_quantity', $validated['quantity']);
                if ($stock->available_quantity >= $validated['quantity']) {
                    $stock->decrement('available_quantity', $validated['quantity']);
                }
                break;
            case 'adjust':
                $stock->update(['current_quantity' => $validated['quantity']]);
                $stock->update(['available_quantity' => $validated['quantity'] - $stock->issued_quantity]);
                break;
        }

        $adjustment->load(['ppeItem', 'project', 'adjustedBy']);

        $this->logActivity('ppe_stock_adjusted', $stock, $validated);

        return $this->successResponse($adjustment, 'Stock adjusted successfully');
    }

    /**
     * Get low stock alerts.
     */
    public function lowStockAlerts(Request $request)
    {
        $query = PpeStock::with(['ppeItem.category', 'project'])
            ->whereRaw('available_quantity <= ppe_items.min_stock_level')
            ->join('ppe_items', 'ppe_stocks.ppe_item_id', '=', 'ppe_items.id');

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $alerts = $query->get()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'ppe_item' => $stock->ppeItem,
                'project' => $stock->project,
                'current_quantity' => $stock->current_quantity,
                'available_quantity' => $stock->available_quantity,
                'min_stock_level' => $stock->ppeItem->min_stock_level,
                'reorder_quantity' => $stock->ppeItem->reorder_quantity,
                'shortage' => max(0, $stock->ppeItem->min_stock_level - $stock->available_quantity),
                'urgency' => $stock->getUrgencyLevel(),
            ];
        });

        return $this->successResponse($alerts);
    }

    /**
     * Get PPE statistics.
     */
    public function statistics(Request $request)
    {
        $query = PpeStock::query();

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $stats = [
            'total_items' => PpeItem::count(),
            'active_items' => PpeItem::where('status', 'active')->count(),
            'total_stock_value' => $query->with('ppeItem')->get()->sum(function ($stock) {
                return $stock->current_quantity * $stock->ppeItem->unit_cost;
            }),
            'low_stock_items' => $query->whereRaw('available_quantity <= ppe_items.min_stock_level')->count(),
            'out_of_stock_items' => $query->where('available_quantity', 0)->count(),
            'total_issued' => $query->sum('issued_quantity'),
            'total_available' => $query->sum('available_quantity'),
            'by_category' => PpeItem::with('category')
                ->selectRaw('ppe_category_id, COUNT(*) as count')
                ->groupBy('ppe_category_id')
                ->with(['category'])
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->category->name => $item->count];
                }),
            'recent_issuances' => \App\Models\PpeIssuance::with(['worker', 'ppeItem'])
                ->orderBy('issued_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return $this->successResponse($stats);
    }
}
