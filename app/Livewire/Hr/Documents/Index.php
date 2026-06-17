<?php

namespace App\Livewire\Hr\Documents;

use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\PayrollBatch;
use App\Services\Hr\HrScopeService;
use App\Services\Payroll\PayrollBatchDocumentService;
use App\Services\Payroll\PayrollWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $ownerType = '';

    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOwnerType(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function downloadDocument(int $documentId)
    {
        $document = $this->visibleDocumentQuery()
            ->with('documentable')
            ->findOrFail($documentId);

        $documentService = app(PayrollBatchDocumentService::class);

        if ($documentService->isGeneratedFinalDocument($document)) {
            abort_unless(
                $document->documentable instanceof PayrollBatch
                && app(PayrollWorkflowService::class)->canGenerateFinalPayrollDocuments($document->documentable),
                403,
            );
        }

        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        DocumentAccessLog::query()->create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'downloaded',
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
        ]);

        return Storage::disk($document->disk)->download($document->file_path, $document->file_name);
    }

    public function render()
    {
        return view('livewire.hr.documents.index', [
            'documents' => $this->visibleDocumentQuery()
                ->with(['documentType:id,name', 'uploadedBy:id,name', 'documentable'])
                ->latest()
                ->paginate(15),
            'statusOptions' => [
                'submitted' => 'Submitted',
                'uploaded' => 'Uploaded',
                'generated' => 'Generated',
                'verified' => 'Verified',
                'rejected' => 'Rejected',
                'issued' => 'Issued',
            ],
            'ownerOptions' => [
                'employee' => 'Employee Documents',
                'leave_application' => 'Leave Applications',
                'payroll_batch' => 'Payroll Batch Documents',
            ],
        ]);
    }

    public function ownerLabel(Document $document): string
    {
        $owner = $document->documentable;

        if ($owner instanceof Employee) {
            return trim(($owner->full_name ?? 'Employee').' '.($owner->employee_code ? "({$owner->employee_code})" : ''));
        }

        if ($owner instanceof PayrollBatch) {
            return 'Payroll Batch '.$owner->batch_number;
        }

        if ($owner instanceof LeaveApplication) {
            $owner->loadMissing('employee:id,full_name,employee_code');

            return trim(
                'Leave Request '.$owner->id.' - '.($owner->employee?->full_name ?? 'Employee')
            );
        }

        return 'Document';
    }

    public function ownerTypeLabel(Document $document): string
    {
        return match ($document->documentable_type) {
            (new Employee())->getMorphClass() => 'Employee',
            (new LeaveApplication())->getMorphClass() => 'Leave Application',
            (new PayrollBatch())->getMorphClass() => 'Payroll Batch',
            default => 'Other',
        };
    }

    public function canDownloadDocument(Document $document): bool
    {
        if (! app(PayrollBatchDocumentService::class)->isGeneratedFinalDocument($document)) {
            return true;
        }

        return $document->documentable instanceof PayrollBatch
            && app(PayrollWorkflowService::class)->canGenerateFinalPayrollDocuments($document->documentable);
    }

    private function visibleDocumentQuery(): Builder
    {
        $query = Document::query();
        $scope = app(HrScopeService::class);

        if (! $scope->isUnrestricted()) {
            $scopedIds = $scope->scopedOrgUnitIds();

            $query->where(function (Builder $q) use ($scope, $scopedIds): void {
                $q->where(function (Builder $documentQuery) use ($scope): void {
                    $documentQuery
                        ->where('documentable_type', (new Employee())->getMorphClass())
                        ->whereHasMorph('documentable', [Employee::class], function (Builder $employeeQuery) use ($scope): void {
                            $scope->applyToEmployeeQuery($employeeQuery);
                        });
                })->orWhere(function (Builder $documentQuery) use ($scope): void {
                    $documentQuery
                        ->where('documentable_type', (new LeaveApplication())->getMorphClass())
                        ->whereHasMorph('documentable', [LeaveApplication::class], function (Builder $leaveQuery) use ($scope): void {
                            $scope->applyToLeaveQuery($leaveQuery);
                        });
                })->orWhere(function (Builder $documentQuery) use ($scopedIds): void {
                    $documentQuery
                        ->where('documentable_type', (new PayrollBatch())->getMorphClass())
                        ->whereHasMorph('documentable', [PayrollBatch::class], function (Builder $batchQuery) use ($scopedIds): void {
                            $batchQuery->where(function (Builder $q) use ($scopedIds): void {
                                $q->whereIn('org_unit_id', $scopedIds)
                                    ->orWhereNull('org_unit_id');
                            });
                        });
                });
            });
        }

        $query
            ->when($this->ownerType === 'employee', fn (Builder $q) => $q->where('documentable_type', (new Employee())->getMorphClass()))
            ->when($this->ownerType === 'leave_application', fn (Builder $q) => $q->where('documentable_type', (new LeaveApplication())->getMorphClass()))
            ->when($this->ownerType === 'payroll_batch', fn (Builder $q) => $q->where('documentable_type', (new PayrollBatch())->getMorphClass()))
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->search, function (Builder $q): void {
                $term = "%{$this->search}%";

                $q->where(function (Builder $inner) use ($term): void {
                    $inner
                        ->where('title', 'like', $term)
                        ->orWhere('file_name', 'like', $term)
                        ->orWhereHasMorph('documentable', [Employee::class], function (Builder $employeeQuery) use ($term): void {
                            $employeeQuery
                                ->where('full_name', 'like', $term)
                                ->orWhere('employee_code', 'like', $term);
                        })
                        ->orWhereHasMorph('documentable', [LeaveApplication::class], function (Builder $leaveQuery) use ($term): void {
                            $leaveQuery
                                ->where('reason', 'like', $term)
                                ->orWhereHas('employee', function (Builder $employeeQuery) use ($term): void {
                                    $employeeQuery
                                        ->where('full_name', 'like', $term)
                                        ->orWhere('employee_code', 'like', $term);
                                });
                        })
                        ->orWhereHasMorph('documentable', [PayrollBatch::class], function (Builder $batchQuery) use ($term): void {
                            $batchQuery->where('batch_number', 'like', $term);
                        });
                });
            });

        return $query;
    }
}
