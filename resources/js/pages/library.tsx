import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import {
  Folder,
  FileText,
  Search,
  Upload,
  Download,
  Grid,
  List,
  ChevronRight,
  FolderPlus,
  Eye,
  Edit,
  Trash2,
  QrCode,
  Calendar,
  User,
  Shield,
} from 'lucide-react';
import { api } from '../lib/api';
import { SkeletonCard } from '../components/skeleton';
import { EmptyState } from '../components/empty-state';

export default function LibraryPage() {
  const { t } = useTranslation();
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedFolder, setSelectedFolder] = useState<number | null>(null);
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [showNewFolderModal, setShowNewFolderModal] = useState(false);

  // Fetch library data
  const { data: libraryData, isLoading, refetch } = useQuery({
    queryKey: ['library', selectedFolder, searchQuery],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (selectedFolder) params.append('folder_id', selectedFolder.toString());
      if (searchQuery) params.append('search', searchQuery);
      
      const response = await api.get(`/library?${params}`);
      return response.data.data;
    },
  });

  const handleFileUpload = async (files: FileList, folderId?: number) => {
    const formData = new FormData();
    
    for (let i = 0; i < files.length; i++) {
      formData.append('files[]', files[i]);
    }
    
    if (folderId) {
      formData.append('folder_id', folderId.toString());
    }

    try {
      await api.post('/library/documents/batch', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      
      setShowUploadModal(false);
      refetch();
    } catch (error) {
      console.error('Upload failed:', error);
    }
  };

  const handleDownload = async (documentId: number) => {
    try {
      const response = await api.get(`/library/documents/${documentId}/download`, {
        responseType: 'blob',
      });
      
      const blob = new Blob([response.data]);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = response.headers['content-disposition']?.split('filename=')[1] || 'document';
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Download failed:', error);
    }
  };

  const handleGenerateQrCode = async (documentId: number) => {
    try {
      const response = await api.post(`/library/documents/${documentId}/generate-qr`);
      // Handle QR code generation
      console.log('QR Code generated:', response.data);
    } catch (error) {
      console.error('QR Code generation failed:', error);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
      >
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Document Library</h1>
          <p className="text-muted-foreground">
            Manage and organize your HSE documents
          </p>
        </div>

        <div className="flex items-center gap-4">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search documents..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10 pr-4 py-2 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          {/* View Mode */}
          <div className="flex rounded-lg border border-border">
            <button
              onClick={() => setViewMode('grid')}
              className={`p-2 rounded-l-lg ${viewMode === 'grid' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
            >
              <Grid className="h-4 w-4" />
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`p-2 rounded-r-lg ${viewMode === 'list' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
            >
              <List className="h-4 w-4" />
            </button>
          </div>

          {/* Actions */}
          <div className="flex gap-2">
            <button
              onClick={() => setShowNewFolderModal(true)}
              className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted"
            >
              <FolderPlus className="h-4 w-4" />
              New Folder
            </button>
            <button
              onClick={() => setShowUploadModal(true)}
              className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
              <Upload className="h-4 w-4" />
              Upload
            </button>
          </div>
        </div>
      </motion.div>

      {/* Breadcrumb */}
      <motion.div
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        className="flex items-center gap-2 text-sm text-muted-foreground"
      >
        <button className="hover:text-foreground">Library</button>
        {selectedFolder && (
          <>
            <ChevronRight className="h-4 w-4" />
            <span className="text-foreground">Current Folder</span>
          </>
        )}
      </motion.div>

      {/* Content */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className="space-y-6"
      >
        {isLoading ? (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <SkeletonCard key={i} />
            ))}
          </div>
        ) : (
          <>
            {/* Folders */}
            {libraryData?.folders?.length > 0 && (
              <div className="space-y-4">
                <h2 className="text-lg font-semibold">Folders</h2>
                {viewMode === 'grid' ? (
                  <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {libraryData.folders.map((folder: any) => (
                      <FolderCard
                        key={folder.id}
                        folder={folder}
                        onSelect={() => setSelectedFolder(folder.id)}
                        onEdit={() => console.log('Edit folder:', folder.id)}
                        onDelete={() => console.log('Delete folder:', folder.id)}
                      />
                    ))}
                  </div>
                ) : (
                  <div className="space-y-2">
                    {libraryData.folders.map((folder: any) => (
                      <FolderListItem
                        key={folder.id}
                        folder={folder}
                        onSelect={() => setSelectedFolder(folder.id)}
                        onEdit={() => console.log('Edit folder:', folder.id)}
                        onDelete={() => console.log('Delete folder:', folder.id)}
                      />
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Documents */}
            {libraryData?.documents?.data?.length > 0 && (
              <div className="space-y-4">
                <h2 className="text-lg font-semibold">Documents</h2>
                {viewMode === 'grid' ? (
                  <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {libraryData.documents.data.map((document: any) => (
                      <DocumentCard
                        key={document.id}
                        document={document}
                        onDownload={() => handleDownload(document.id)}
                        onView={() => console.log('View document:', document.id)}
                        onEdit={() => console.log('Edit document:', document.id)}
                        onDelete={() => console.log('Delete document:', document.id)}
                        onGenerateQr={() => handleGenerateQrCode(document.id)}
                      />
                    ))}
                  </div>
                ) : (
                  <div className="space-y-2">
                    {libraryData.documents.data.map((document: any) => (
                      <DocumentListItem
                        key={document.id}
                        document={document}
                        onDownload={() => handleDownload(document.id)}
                        onView={() => console.log('View document:', document.id)}
                        onEdit={() => console.log('Edit document:', document.id)}
                        onDelete={() => console.log('Delete document:', document.id)}
                        onGenerateQr={() => handleGenerateQrCode(document.id)}
                      />
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Empty State */}
            {(!libraryData?.folders?.length && !libraryData?.documents?.data?.length) && (
              <EmptyState
                title="No documents found"
                description="Upload your first document or create a folder to get started"
              />
            )}
          </>
        )}
      </motion.div>

      {/* Upload Modal */}
      {showUploadModal && (
        <UploadModal
          onClose={() => setShowUploadModal(false)}
          onUpload={handleFileUpload}
          selectedFolder={selectedFolder}
        />
      )}

      {/* New Folder Modal */}
      {showNewFolderModal && (
        <NewFolderModal
          onClose={() => setShowNewFolderModal(false)}
          onCreate={(name) => console.log('Create folder:', name)}
        />
      )}
    </div>
  );
}

// Folder Card Component
function FolderCard({ folder, onSelect, onEdit, onDelete }: any) {
  return (
    <motion.div
      whileHover={{ scale: 1.02 }}
      className="rounded-xl border border-border bg-card p-6 cursor-pointer hover:shadow-lg transition-shadow"
      onClick={onSelect}
    >
      <div className="flex items-start justify-between mb-4">
        <Folder className="h-8 w-8 text-blue-500" />
        <div className="flex gap-1">
          <button
            onClick={(e) => {
              e.stopPropagation();
              onEdit();
            }}
            className="p-1 rounded hover:bg-muted"
          >
            <Edit className="h-4 w-4" />
          </button>
          <button
            onClick={(e) => {
              e.stopPropagation();
              onDelete();
            }}
            className="p-1 rounded hover:bg-muted text-destructive"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      </div>
      
      <h3 className="font-semibold mb-2">{folder.name}</h3>
      {folder.description && (
        <p className="text-sm text-muted-foreground mb-4">{folder.description}</p>
      )}
      
      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <span>{folder.document_count || 0} documents</span>
        <span>{folder.access_level}</span>
      </div>
    </motion.div>
  );
}

// Document Card Component
function DocumentCard({ document, onDownload, onView, onEdit, onDelete, onGenerateQr }: any) {
  const getDocumentIcon = (type: string) => {
    switch (type) {
      case 'pdf': return '📄';
      case 'doc':
      case 'docx': return '📝';
      case 'xls':
      case 'xlsx': return '📊';
      case 'ppt':
      case 'pptx': return '📽️';
      default: return '📄';
    }
  };

  return (
    <motion.div
      whileHover={{ scale: 1.02 }}
      className="rounded-xl border border-border bg-card p-6 hover:shadow-lg transition-shadow"
    >
      <div className="flex items-start justify-between mb-4">
        <div className="text-3xl">{getDocumentIcon(document.document_type)}</div>
        <div className="flex gap-1">
          {document.is_sds && (
            <button
              onClick={onGenerateQr}
              className="p-1 rounded hover:bg-muted"
              title="Generate QR Code"
            >
              <QrCode className="h-4 w-4" />
            </button>
          )}
          <button
            onClick={onView}
            className="p-1 rounded hover:bg-muted"
          >
            <Eye className="h-4 w-4" />
          </button>
          <button
            onClick={onDownload}
            className="p-1 rounded hover:bg-muted"
          >
            <Download className="h-4 w-4" />
          </button>
          <button
            onClick={onEdit}
            className="p-1 rounded hover:bg-muted"
          >
            <Edit className="h-4 w-4" />
          </button>
          <button
            onClick={onDelete}
            className="p-1 rounded hover:bg-muted text-destructive"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      </div>
      
      <h3 className="font-semibold mb-2 truncate">{document.title}</h3>
      {document.description && (
        <p className="text-sm text-muted-foreground mb-4 line-clamp-2">{document.description}</p>
      )}
      
      <div className="flex items-center gap-2 mb-3">
        {document.keywords?.slice(0, 2).map((keyword: any, index: number) => (
          <span key={index} className="px-2 py-1 bg-primary/10 text-primary text-xs rounded-full">
            {keyword.keyword}
          </span>
        ))}
      </div>
      
      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <div className="flex items-center gap-2">
          <User className="h-3 w-3" />
          <span>{document.uploaded_by?.name}</span>
        </div>
        <div className="flex items-center gap-2">
          <Calendar className="h-3 w-3" />
          <span>{new Date(document.created_at).toLocaleDateString()}</span>
        </div>
      </div>
      
      {document.is_sds && (
        <div className="mt-3 flex items-center gap-2 text-xs text-green-600">
          <Shield className="h-3 w-3" />
          <span>SDS Document</span>
        </div>
      )}
    </motion.div>
  );
}

// Folder List Item Component
function FolderListItem({ folder, onSelect, onEdit, onDelete }: any) {
  return (
    <div
      className="flex items-center justify-between p-4 rounded-lg border border-border hover:bg-muted cursor-pointer"
      onClick={onSelect}
    >
      <div className="flex items-center gap-3">
        <Folder className="h-5 w-5 text-blue-500" />
        <div>
          <h3 className="font-medium">{folder.name}</h3>
          {folder.description && (
            <p className="text-sm text-muted-foreground">{folder.description}</p>
          )}
        </div>
      </div>
      
      <div className="flex items-center gap-4">
        <span className="text-sm text-muted-foreground">{folder.document_count || 0} documents</span>
        <div className="flex gap-1">
          <button
            onClick={(e) => {
              e.stopPropagation();
              onEdit();
            }}
            className="p-1 rounded hover:bg-muted"
          >
            <Edit className="h-4 w-4" />
          </button>
          <button
            onClick={(e) => {
              e.stopPropagation();
              onDelete();
            }}
            className="p-1 rounded hover:bg-muted text-destructive"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  );
}

// Document List Item Component
function DocumentListItem({ document, onDownload, onView, onEdit, onDelete, onGenerateQr }: any) {
  return (
    <div className="flex items-center justify-between p-4 rounded-lg border border-border hover:bg-muted">
      <div className="flex items-center gap-3">
        <FileText className="h-5 w-5 text-muted-foreground" />
        <div>
          <h3 className="font-medium">{document.title}</h3>
          <div className="flex items-center gap-4 text-sm text-muted-foreground">
            <span>{document.file_type}</span>
            <span>{Math.round(document.file_size / 1024)} KB</span>
            <span>by {document.uploaded_by?.name}</span>
          </div>
        </div>
      </div>
      
      <div className="flex items-center gap-2">
        {document.is_sds && (
          <button
            onClick={onGenerateQr}
            className="p-1 rounded hover:bg-muted"
            title="Generate QR Code"
          >
            <QrCode className="h-4 w-4" />
          </button>
        )}
        <button onClick={onView} className="p-1 rounded hover:bg-muted">
          <Eye className="h-4 w-4" />
        </button>
        <button onClick={onDownload} className="p-1 rounded hover:bg-muted">
          <Download className="h-4 w-4" />
        </button>
        <button onClick={onEdit} className="p-1 rounded hover:bg-muted">
          <Edit className="h-4 w-4" />
        </button>
        <button onClick={onDelete} className="p-1 rounded hover:bg-muted text-destructive">
          <Trash2 className="h-4 w-4" />
        </button>
      </div>
    </div>
  );
}

// Upload Modal Component
function UploadModal({ onClose, onUpload, selectedFolder }: any) {
  const [dragActive, setDragActive] = useState(false);
  const [files, setFiles] = useState<FileList | null>(null);

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setDragActive(false);
    if (e.dataTransfer.files) {
      setFiles(e.dataTransfer.files);
    }
  };

  const handleSubmit = () => {
    if (files) {
      onUpload(files, selectedFolder);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-card rounded-xl p-6 max-w-md w-full mx-4"
      >
        <h2 className="text-xl font-semibold mb-4">Upload Documents</h2>
        
        <div
          className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
            dragActive ? 'border-primary bg-primary/5' : 'border-border'
          }`}
          onDrop={handleDrop}
          onDragOver={(e) => {
            e.preventDefault();
            setDragActive(true);
          }}
          onDragLeave={() => setDragActive(false)}
        >
          <Upload className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
          <p className="text-muted-foreground mb-2">
            Drag and drop files here, or click to select
          </p>
          <input
            type="file"
            multiple
            onChange={(e) => setFiles(e.target.files)}
            className="hidden"
            id="file-upload"
          />
          <label
            htmlFor="file-upload"
            className="px-4 py-2 bg-primary text-primary-foreground rounded-lg cursor-pointer inline-block"
          >
            Select Files
          </label>
        </div>

        {files && (
          <div className="mt-4">
            <p className="text-sm font-medium mb-2">Selected files:</p>
            <div className="space-y-1">
              {Array.from(files).map((file, index) => (
                <div key={index} className="text-sm text-muted-foreground">
                  {file.name} ({Math.round(file.size / 1024)} KB)
                </div>
              ))}
            </div>
          </div>
        )}

        <div className="flex gap-2 mt-6">
          <button
            onClick={onClose}
            className="flex-1 px-4 py-2 border border-border rounded-lg hover:bg-muted"
          >
            Cancel
          </button>
          <button
            onClick={handleSubmit}
            disabled={!files}
            className="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
          >
            Upload
          </button>
        </div>
      </motion.div>
    </div>
  );
}

// New Folder Modal Component
function NewFolderModal({ onClose, onCreate }: { onClose: () => void; onCreate: (name: string, description: string) => void }) {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');

  const handleSubmit = () => {
    if (name.trim()) {
      onCreate(name, description);
      onClose();
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-card rounded-xl p-6 max-w-md w-full mx-4"
      >
        <h2 className="text-xl font-semibold mb-4">Create New Folder</h2>
        
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">Folder Name</label>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Enter folder name"
            />
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-2">Description (Optional)</label>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Enter folder description"
              rows={3}
            />
          </div>
        </div>

        <div className="flex gap-2 mt-6">
          <button
            onClick={onClose}
            className="flex-1 px-4 py-2 border border-border rounded-lg hover:bg-muted"
          >
            Cancel
          </button>
          <button
            onClick={handleSubmit}
            disabled={!name.trim()}
            className="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
          >
            Create
          </button>
        </div>
      </motion.div>
    </div>
  );
}
