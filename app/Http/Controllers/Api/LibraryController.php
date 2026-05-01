<?php

namespace App\Http\Controllers\Api;

use App\Models\LibraryDocument;
use App\Models\LibraryFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LibraryController extends BaseController
{
    /**
     * Display a listing of folders and documents.
     */
    public function index(Request $request)
    {
        $folderId = $request->folder_id ?? null;
        $search = $request->search ?? null;

        $folders = LibraryFolder::with(['parent', 'children'])
            ->when($folderId, function ($query, $folderId) {
                $query->where('parent_id', $folderId);
            })
            ->when(!$folderId, function ($query) {
                $query->whereNull('parent_id');
            })
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        $documents = LibraryDocument::with(['folder', 'uploadedBy'])
            ->when($folderId, function ($query, $folderId) {
                $query->where('folder_id', $folderId);
            })
            ->when(!$folderId, function ($query) {
                $query->whereNull('folder_id');
            })
            ->when($search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('keywords', function ($q) use ($search) {
                          $q->where('keyword', 'like', "%{$search}%");
                      });
            })
            ->when($request->document_type, function ($query, $type) {
                $query->where('document_type', $type);
            })
            ->orderBy('title')
            ->paginate(20);

        return $this->successResponse([
            'folders' => $folders,
            'documents' => $documents,
        ]);
    }

    /**
     * Store a newly created folder.
     */
    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:library_folders,id',
            'access_level' => 'required|in:public,restricted,private',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,id',
        ]);

        $folder = LibraryFolder::create($validated);

        $folder->load(['parent', 'children']);

        $this->logActivity('library_folder_created', $folder, $validated);

        return $this->successResponse($folder, 'Folder created successfully');
    }

    /**
     * Store a newly created document.
     */
    public function storeDocument(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'folder_id' => 'nullable|exists:library_folders,id',
            'document_type' => 'required|in:pdf,doc,docx,xls,xlsx,ppt,pptx,image,video,audio,other',
            'file' => 'required|file|max:51200', // 50MB max
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
            'version' => 'nullable|string|max:20',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'access_level' => 'required|in:public,restricted,private',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,id',
            'is_sds' => 'boolean',
            'requires_review' => 'boolean',
            'review_frequency_days' => 'nullable|integer|min:1',
            'pinned' => 'boolean',
        ]);

        $file = $request->file('file');
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('library-documents', $fileName, 'public');

        $validated['file_path'] = $filePath;
        $validated['file_name'] = $file->getClientOriginalName();
        $validated['file_size'] = $file->getSize();
        $validated['file_type'] = $file->getClientMimeType();
        $validated['uploaded_by'] = auth()->id();

        $document = LibraryDocument::create($validated);

        // Add keywords
        if (!empty($validated['keywords'])) {
            foreach ($validated['keywords'] as $keyword) {
                $document->keywords()->create(['keyword' => $keyword]);
            }
        }

        // Generate public access token for SDS documents
        if ($validated['is_sds'] ?? false) {
            $document->update([
                'public_token' => Str::random(32),
                'qr_code_path' => $this->generateQrCode($document),
            ]);
        }

        $document->load(['folder', 'uploadedBy', 'keywords']);

        $this->logActivity('library_document_uploaded', $document, $validated);

        return $this->successResponse($document, 'Document uploaded successfully');
    }

    /**
     * Display the specified folder.
     */
    public function showFolder(LibraryFolder $folder)
    {
        $folder->load(['parent', 'children', 'documents.uploadedBy']);

        return $this->successResponse($folder);
    }

    /**
     * Display the specified document.
     */
    public function showDocument(LibraryDocument $document)
    {
        $document->load(['folder', 'uploadedBy', 'keywords']);

        return $this->successResponse($document);
    }

    /**
     * Update the specified folder.
     */
    public function updateFolder(Request $request, LibraryFolder $folder)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:library_folders,id',
            'access_level' => 'sometimes|in:public,restricted,private',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,id',
        ]);

        $folder->update($validated);

        $folder->load(['parent', 'children']);

        $this->logActivity('library_folder_updated', $folder, $validated);

        return $this->successResponse($folder, 'Folder updated successfully');
    }

    /**
     * Update the specified document.
     */
    public function updateDocument(Request $request, LibraryDocument $document)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'folder_id' => 'nullable|exists:library_folders,id',
            'document_type' => 'sometimes|in:pdf,doc,docx,xls,xlsx,ppt,pptx,image,video,audio,other',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
            'version' => 'nullable|string|max:20',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'access_level' => 'sometimes|in:public,restricted,private',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,id',
            'requires_review' => 'boolean',
            'review_frequency_days' => 'nullable|integer|min:1',
            'pinned' => 'boolean',
            'file' => 'nullable|file|max:51200',
        ]);

        // Handle file replacement
        if ($request->hasFile('file')) {
            // Delete old file
            Storage::disk('public')->delete($document->file_path);
            
            $file = $request->file('file');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('library-documents', $fileName, 'public');

            $validated['file_path'] = $filePath;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
            $validated['file_type'] = $file->getClientMimeType();
        }

        $document->update($validated);

        // Update keywords
        if (isset($validated['keywords'])) {
            $document->keywords()->delete();
            foreach ($validated['keywords'] as $keyword) {
                $document->keywords()->create(['keyword' => $keyword]);
            }
        }

        $document->load(['folder', 'uploadedBy', 'keywords']);

        $this->logActivity('library_document_updated', $document, $validated);

        return $this->successResponse($document, 'Document updated successfully');
    }

    /**
     * Remove the specified folder.
     */
    public function destroyFolder(LibraryFolder $folder)
    {
        // Check if folder has contents
        if ($folder->children()->count() > 0 || $folder->documents()->count() > 0) {
            return $this->errorResponse('Cannot delete folder with contents', 400);
        }

        $folder->delete();

        $this->logActivity('library_folder_deleted', $folder);

        return $this->successResponse(null, 'Folder deleted successfully');
    }

    /**
     * Remove the specified document.
     */
    public function destroyDocument(LibraryDocument $document)
    {
        // Delete file
        Storage::disk('public')->delete($document->file_path);
        if ($document->qr_code_path) {
            Storage::disk('public')->delete($document->qr_code_path);
        }

        $document->delete();

        $this->logActivity('library_document_deleted', $document);

        return $this->successResponse(null, 'Document deleted successfully');
    }

    /**
     * Download document.
     */
    public function downloadDocument(LibraryDocument $document)
    {
        if (!$document->canAccess(auth()->user())) {
            return $this->errorResponse('Access denied', 403);
        }

        $filePath = storage_path('app/public/' . $document->file_path);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File not found', 404);
        }

        return response()->download($filePath, $document->file_name);
    }

    /**
     * Download multiple documents as ZIP.
     */
    public function downloadMultiple(Request $request)
    {
        $validated = $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:library_documents,id',
        ]);

        $documents = LibraryDocument::whereIn('id', $validated['document_ids'])->get();
        
        // Check access permissions
        foreach ($documents as $document) {
            if (!$document->canAccess(auth()->user())) {
                return $this->errorResponse('Access denied for some documents', 403);
            }
        }

        $zipFileName = 'documents-' . date('Y-m-d-H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            foreach ($documents as $document) {
                $filePath = storage_path('app/public/' . $document->file_path);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $document->file_name);
                }
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * Get public SDS document.
     */
    public function getSdsDocument($token)
    {
        $document = LibraryDocument::where('public_token', $token)
            ->where('is_sds', true)
            ->firstOrFail();

        $filePath = storage_path('app/public/' . $document->file_path);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File not found', 404);
        }

        return response()->download($filePath, $document->file_name);
    }

    /**
     * Generate QR code for SDS document.
     */
    private function generateQrCode(LibraryDocument $document): string
    {
        $url = route('library.sds-public', $document->public_token);
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(200)->generate($url);
        
        $qrFileName = 'qr-' . $document->id . '.png';
        $qrPath = 'qr-codes/' . $qrFileName;
        
        Storage::disk('public')->put($qrPath, $qrCode);
        
        return $qrPath;
    }

    /**
     * Search documents.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'document_type' => 'nullable|in:pdf,doc,docx,xls,xlsx,ppt,pptx,image,video,audio,other',
            'folder_id' => 'nullable|exists:library_folders,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $documents = LibraryDocument::with(['folder', 'uploadedBy', 'keywords'])
            ->where(function ($query) use ($validated) {
                $query->where('title', 'like', "%{$validated['query']}%")
                      ->orWhere('description', 'like', "%{$validated['query']}%")
                      ->orWhere('file_name', 'like', "%{$validated['query']}%")
                      ->orWhereHas('keywords', function ($q) use ($validated) {
                          $q->where('keyword', 'like', "%{$validated['query']}%");
                      });
            })
            ->when($validated['document_type'] ?? null, function ($query, $type) {
                $query->where('document_type', $type);
            })
            ->when($validated['folder_id'] ?? null, function ($query, $folderId) {
                $query->where('folder_id', $folderId);
            })
            ->when($validated['date_from'] ?? null, function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($validated['date_to'] ?? null, function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->orderBy('title')
            ->paginate(20);

        return $this->successResponse($documents);
    }

    /**
     * Get library statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_documents' => LibraryDocument::count(),
            'total_folders' => LibraryFolder::count(),
            'by_type' => LibraryDocument::selectRaw('document_type, COUNT(*) as count')
                ->groupBy('document_type')
                ->pluck('count', 'document_type'),
            'total_size' => LibraryDocument::sum('file_size'),
            'sds_documents' => LibraryDocument::where('is_sds', true)->count(),
            'pinned_documents' => LibraryDocument::where('pinned', true)->count(),
            'documents_requiring_review' => LibraryDocument::where('requires_review', true)
                ->where(function ($query) {
                    $query->whereNull('last_reviewed_at')
                          ->orWhereRaw('last_reviewed_at < DATE_SUB(NOW(), INTERVAL review_frequency_days DAY)');
                })->count(),
            'recent_uploads' => LibraryDocument::with(['uploadedBy'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return $this->successResponse($stats);
    }
}
