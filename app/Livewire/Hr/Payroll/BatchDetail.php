<?php

namespace App\Livewire\Hr\Payroll;

use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemAdjustment;
use App\Models\Role;
use App\Services\Payroll\PayrollBatchDocumentService;
use App\Services\Payroll\PayslipGenerationService;
use App\Services\Payroll\PayrollWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class BatchDetail extends Component
{
    use WithFileUploads;
    use WithPagination;

    public PayrollBatch $batch;

    public string $search = '';

    public string $filterStatus = '';

    public string $workflowRemarks = '';

    public string $batchDocumentTitle = '';

    public string $batchDocumentRemarks = '';

    public $batchDocumentFile = null;

    // ── actions ───────────────────────────────────────────────────────────────

    public function openSubmitModal(): void
    {
        $this->workflowRemarks = '';
        $this->dispatch('open-modal', name: 'submit-payroll-batch');
    }

    public function openApproveModal(): void
    {
        $this->workflowRemarks = '';
        $this->dispatch('open-modal', name: 'approve-payroll-batch');
    }

    public function openReturnModal(): void
    {
        $this->workflowRemarks = '';
        $this->dispatch('open-modal', name: 'return-payroll-batch');
    }

    public function openDiscardModal(): void
    {
        $this->dispatch('open-modal', name: 'discard-payroll-batch');
    }

    public function openGeneratePayslipsModal(): void
    {
        $this->dispatch('open-modal', name: 'generate-payslips');
    }

    public function openUploadBatchDocumentModal(): void
    {
        $this->resetBatchDocumentForm();
        $this->dispatch('open-modal', name: 'upload-batch-document');
    }

    public function downloadFinalPayrollDocument(string $type)
    {
        $document = app(PayrollBatchDocumentService::class)->generate($this->batch, $type);

        $this->batch->refresh();
        $this->logDocumentAccess($document, 'generated');

        return Storage::disk($document->disk)->download($document->file_path, $document->file_name);
    }

    public function uploadBatchDocument(): void
    {
        abort_unless(app(PayrollWorkflowService::class)->canGenerateFinalPayrollDocuments($this->batch), 403);

        $data = $this->validate([
            'batchDocumentTitle' => ['required', 'string', 'max:255'],
            'batchDocumentRemarks' => ['nullable', 'string', 'max:1000'],
            'batchDocumentFile' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $file = $data['batchDocumentFile'];
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $path = $file->store("payroll-batch-documents/{$this->batch->id}/uploads", 'local');
        $fileSize = Storage::disk('local')->size($path);

        $document = new Document([
            'title' => $data['batchDocumentTitle'],
            'file_name' => $fileName,
            'file_path' => $path,
            'disk' => 'local',
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'version' => 1,
            'status' => 'uploaded',
            'uploaded_by' => Auth::id(),
            'remarks' => $data['batchDocumentRemarks'] ?: null,
        ]);
        $document->documentable()->associate($this->batch);
        $document->save();

        $this->logDocumentAccess($document, 'uploaded');
        $this->resetBatchDocumentForm();
        $this->batch->refresh();
        $this->dispatch('close-modal', name: 'upload-batch-document');
        session()->flash('status', 'Batch document uploaded.');
    }

    public function downloadBatchDocument(int $documentId)
    {
        $document = $this->batch->documents()->findOrFail($documentId);
        $workflow = app(PayrollWorkflowService::class);
        $documentService = app(PayrollBatchDocumentService::class);

        if ($documentService->isGeneratedFinalDocument($document)) {
            abort_unless($workflow->canGenerateFinalPayrollDocuments($this->batch), 403);
        } else {
            abort_unless(Auth::user()?->canAccessPayrollWorkflow(), 403);
        }

        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        $this->logDocumentAccess($document, 'downloaded');

        return Storage::disk($document->disk)->download($document->file_path, $document->file_name);
    }

    public function generatePayslips(): void
    {
        abort_unless(Auth::user()?->hasRole('hr'), 403);

        $payslips = app(PayslipGenerationService::class)->generateForBatch($this->batch);

        $this->batch->refresh();
        $this->dispatch('close-modal', name: 'generate-payslips');
        session()->flash('status', "{$payslips->count()} payslip(s) generated for {$this->batch->batch_number}.");
    }

    public function submitBatch(): void
    {
        $this->validate([
            'workflowRemarks' => ['nullable', 'string', 'max:1000'],
        ]);

        app(PayrollWorkflowService::class)->submitFromHr($this->batch, $this->workflowRemarks ?: null);

        $this->batch->refresh();
        $this->dispatch('close-modal', name: 'submit-payroll-batch');
        session()->flash('status', "Batch {$this->batch->batch_number} submitted to SPO FM.");
    }

    public function approveCurrentStep(): void
    {
        $this->validate([
            'workflowRemarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $beforeRole = app(PayrollWorkflowService::class)->currentRoleLabel($this->batch);
        app(PayrollWorkflowService::class)->approveCurrentStep($this->batch, $this->workflowRemarks ?: null);

        $this->batch->refresh();
        $nextRole = app(PayrollWorkflowService::class)->currentRoleLabel($this->batch);
        $this->dispatch('close-modal', name: 'lock-payroll-batch');
        $this->dispatch('close-modal', name: 'approve-payroll-batch');

        $message = $this->batch->isLocked()
            ? "Batch {$this->batch->batch_number} finally approved by {$beforeRole} and locked."
            : "Batch {$this->batch->batch_number} approved by {$beforeRole} and forwarded to {$nextRole}.";

        session()->flash('status', $message);
    }

    public function returnBatchToHr(): void
    {
        $this->validate([
            'workflowRemarks' => ['required', 'string', 'max:1000'],
        ]);

        app(PayrollWorkflowService::class)->returnToHr($this->batch, $this->workflowRemarks);

        $this->batch->refresh();
        $this->dispatch('close-modal', name: 'return-payroll-batch');
        session()->flash('status', "Batch {$this->batch->batch_number} returned to HR for correction.");
    }

    public function discardBatch(): void
    {
        abort_unless(app(PayrollWorkflowService::class)->canDiscardFromHr($this->batch), 403);

        $this->batch->items()->each(fn ($item) => $item->leaveAdjustments()->delete());
        $this->batch->items()->each(fn ($item) => $item->components()->delete());
        $this->batch->items()->delete();
        $this->batch->delete();

        $this->dispatch('close-modal', name: 'discard-payroll-batch');
        session()->flash('status', 'Payroll batch discarded.');

        $this->redirect(route('hr.payroll.index'));
    }

    private function resetBatchDocumentForm(): void
    {
        $this->batchDocumentTitle = '';
        $this->batchDocumentRemarks = '';
        $this->batchDocumentFile = null;
        $this->resetValidation(['batchDocumentTitle', 'batchDocumentRemarks', 'batchDocumentFile']);
    }

    private function logDocumentAccess(Document $document, string $action): void
    {
        DocumentAccessLog::query()->create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
        ]);
    }

    // ── render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $this->batch->loadMissing('parentBatch:id,batch_number', 'orgUnit:id,name');
        $workflowService = app(PayrollWorkflowService::class);
        $workflowInstance = $workflowService->workflowInstanceFor($this->batch);
    
        $items = PayrollItem::query()
            ->where('payroll_batch_id', $this->batch->id)
            ->with([
                'employee:id,full_name,employee_code,org_unit_id',
                'employee.orgUnit:id,name',
                'adjustments',
                'payslip:id,payroll_item_id,status,generated_at',
            ])
            ->withCount([
                'leaveAdjustments',
                'leaveAdjustments as pending_review_count' => fn ($q) => $q->whereNull('hr_classification'),
                'adjustments',
            ])
            ->when($this->search, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq
                ->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('employee_code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('id')
            ->paginate(20);
    
        $itemIds = $this->batch->items()->pluck('id');
    
        $adjTotals = \App\Models\PayrollItemAdjustment::whereIn('payroll_item_id', $itemIds)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');
    
        return view('livewire.hr.payroll.batch-detail', [
            'items' => $items,
            'workflowInstance' => $workflowInstance,
            'workflowActions' => $workflowInstance?->actions ?? collect(),
            'adjustmentLogs' => $this->batch->adjustmentLogs()
                ->with([
                    'actor:id,name',
                    'workflowStep:id,name,role',
                    'payrollItem.employee:id,full_name,employee_code',
                ])
                ->latest()
                ->limit(20)
                ->get(),
            'currentRoleLabel' => $workflowService->currentRoleLabel($this->batch),
            'canSubmitWorkflow' => $workflowService->canSubmitFromHr($this->batch),
            'canDiscardBatch' => $workflowService->canDiscardFromHr($this->batch),
            'canActOnWorkflow' => $workflowService->canCurrentUserAct($this->batch),
            'canEditPayroll' => $workflowService->canCurrentUserEdit($this->batch),
            'canGeneratePayslips' => Auth::user()?->hasRole('hr')
                && (in_array($this->batch->status, ['approved', 'locked', 'disbursed'], true) || $this->batch->isLocked()),
            'canGenerateFinalDocuments' => $workflowService->canGenerateFinalPayrollDocuments($this->batch),
            'finalDocumentTypes' => PayrollBatchDocumentService::TYPES,
            'batchDocuments' => $this->batch->documents()
                ->with('uploadedBy:id,name')
                ->latest()
                ->get(),
            'finalApproverLabel' => Role::labelFor('md'),
            'summary' => [
                'employees'      => $this->batch->items()->count(),
                'gross'          => $this->batch->gross_total,
                'deductions'     => $this->batch->items()->sum('total_deductions')
                                + ($adjTotals['deduction'] ?? 0)
                                - ($adjTotals['addition'] ?? 0),
                'net'            => $this->batch->net_total,
                'disbursed'      => $this->batch->disbursed_total,
                'payslips'       => $this->batch->items()->whereHas('payslip', fn ($q) => $q->where('status', 'issued'))->count(),
                'pending_review' => $this->batch->items()
                    ->whereHas('leaveAdjustments', fn ($q) => $q->whereNull('hr_classification'))
                    ->count(),
            ],
        ]);
    }
}
