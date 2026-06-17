<?php

namespace App\Livewire\Employee\Documents;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public Employee $employee;

    public array $documentForm = [
        'document_type_id' => '',
        'title' => '',
        'expires_at' => '',
        'remarks' => '',
    ];

    public ?TemporaryUploadedFile $documentFile = null;

    public function mount(): void
    {
        $this->employee = Auth::user()->employee()->firstOrFail();
    }

    public function submitDocument(): void
    {
        $data = $this->validateDocument();
        $disk = config('filesystems.default', 'local');
        $fileName = $this->documentFile->getClientOriginalName();
        $mimeType = $this->documentFile->getMimeType();
        $path = $this->documentFile->store('employee-documents/'.$this->employee->id, $disk);
        $fileSize = Storage::disk($disk)->size($path);

        $this->employee->documents()->create([
            ...$data,
            'file_name' => $fileName,
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'status' => 'submitted',
            'uploaded_by' => Auth::id(),
        ]);

        $this->resetDocumentForm();
        session()->flash('document_status', 'Document submitted successfully.');
    }

    public function downloadDocument(int $documentId)
    {
        $document = $this->documentForEmployee($documentId);
        $this->logAccess($document, 'download');

        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        return Storage::disk($document->disk)->download($document->file_path, $document->file_name);
    }

    public function deleteDocument(int $documentId): void
    {
        $document = $this->documentForEmployee($documentId);

        abort_unless(in_array($document->status, ['submitted', 'uploaded', 'rejected'], true), 403);

        Storage::disk($document->disk)->delete($document->file_path);
        $document->delete();

        session()->flash('document_status', 'Document removed.');
    }

    public function resetDocumentForm(): void
    {
        $this->resetErrorBag();
        $this->documentForm = [
            'document_type_id' => '',
            'title' => '',
            'expires_at' => '',
            'remarks' => '',
        ];
        $this->documentFile = null;
    }

    public function render()
    {
        return view('livewire.employee.documents.index', [
            'documents' => $this->employee
                ->documents()
                ->with(['documentType', 'verifiedBy:id,name'])
                ->latest()
                ->get(),
            'documentTypeOptions' => DocumentType::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
        ]);
    }

    private function validateDocument(): array
    {
        $validated = $this->validate([
            'documentForm.document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'documentForm.title' => ['required', 'string', 'max:255'],
            'documentForm.expires_at' => ['nullable', 'date', 'after:today'],
            'documentForm.remarks' => ['nullable', 'string', 'max:1000'],
            'documentFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ])['documentForm'];

        return array_map(fn ($value) => $value === '' ? null : $value, $validated);
    }

    private function documentForEmployee(int $documentId): Document
    {
        return $this->employee
            ->documents()
            ->whereKey($documentId)
            ->firstOrFail();
    }

    private function logAccess(Document $document, string $action): void
    {
        $document->accessLogs()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
