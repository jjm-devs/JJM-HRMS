<?php

namespace App\Services\Payroll;

use App\Models\Document;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemComponent;
use App\Models\User;
use App\Services\Documents\SimpleXlsxService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayrollBatchDocumentService
{
    public const SALARY_STATEMENT = 'salary_statement';

    public const TYPES = [
        self::SALARY_STATEMENT => 'Salary Statement',
    ];

    public function __construct(
        private readonly PayrollWorkflowService $workflow,
        private readonly SimpleXlsxService $xlsx,
    ) {}

    public function generate(PayrollBatch $batch, string $type, ?User $user = null): Document
    {
        $user ??= Auth::user();

        abort_unless($this->workflow->canGenerateFinalPayrollDocuments($batch, $user), 403);
        abort_unless(array_key_exists($type, self::TYPES), 404);

        return $this->generateSalaryStatement($batch, $user);
    }

    public function isGeneratedFinalDocument(Document $document): bool
    {
        return $document->documentable_type === (new PayrollBatch)->getMorphClass()
            && $document->status === 'generated'
            && in_array($document->title, self::TYPES, true);
    }

    private function generateSalaryStatement(PayrollBatch $batch, ?User $user = null): Document
    {
        $batch->loadMissing(['generatedBy:id,name', 'approvedBy:id,name', 'orgUnit:id,name', 'departmentStream:id,name']);

        $title = self::TYPES[self::SALARY_STATEMENT];
        $slug = Str::slug($title);
        $existing = $batch->documents()
            ->where('title', $title)
            ->where('status', 'generated')
            ->latest('version')
            ->first();
        $version = ($existing?->version ?? 0) + 1;
        $fileName = "{$slug}-{$batch->batch_number}.xlsx";
        $path = "payroll-batch-documents/{$batch->id}/{$slug}-v{$version}.xlsx";

        $statement = $this->salaryStatementWorkbook($batch);

        Storage::disk('local')->put($path, $statement);

        if ($existing && Storage::disk($existing->disk)->exists($existing->file_path)) {
            Storage::disk($existing->disk)->delete($existing->file_path);
        }

        $document = $existing ?? new Document;
        $document->fill([
            'title' => $title,
            'file_name' => $fileName,
            'file_path' => $path,
            'disk' => 'local',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'file_size' => strlen($statement),
            'version' => $version,
            'status' => 'generated',
            'uploaded_by' => $user?->id,
            'remarks' => 'Generated salary statement from payroll batch.',
        ]);
        $document->documentable()->associate($batch);
        $document->save();

        return $document->refresh();
    }

    private function salaryStatementWorkbook(PayrollBatch $batch): string
    {
        $items = $batch->items()
            ->with([
                'employee:id,employee_code,full_name,designation_id,org_unit_id,bank_account_number,bank_ifsc_code,bank_name,bank_branch',
                'employee.designation:id,name',
                'employee.orgUnit:id,name',
                'components',
                'adjustments',
            ])
            ->orderBy('id')
            ->get();

        $componentColumns = $this->componentColumns($items);
        $fixedHeaders = [
            'Employee Code',
            'Employee Name',
            'Designation',
            'Office / Unit',
            'Bank Account Number',
            'IFSC Code',
            'Bank Name',
            'Branch',
        ];
        $amountHeaders = [
            'Basic Salary',
            'Gross Salary',
            'System Deductions',
            'Manual Additions',
            'Manual Deductions',
            'LWP Days',
            'LWP Deduction',
            'Net Payable',
            'Disbursement %',
            'Disbursed Amount',
            'Outstanding Amount',
            'Status',
            'Adjustment Notes',
        ];
        $headers = array_merge($fixedHeaders, array_values($componentColumns), $amountHeaders);
        $headerRow = 8;
        $rows = [
            ['Salary Statement', $batch->batch_number],
            ['Period', $batch->periodLabel()],
            ['Payment Date', $batch->payment_date?->format('d M Y') ?? 'Not specified'],
            ['Office', $batch->orgUnit?->name ?? 'All scoped offices'],
            ['Department Stream', $batch->departmentStream?->name ?? 'Not specified'],
            ['Generated On', now()->format('d M Y, h:i A')],
            [],
            $headers,
        ];
        $totalRow = array_fill(0, count($headers), '');
        $totalRow[0] = 'Totals';
        $sumColumns = [];

        foreach ($items as $item) {
            $componentAmounts = $this->componentAmounts($item, $componentColumns);
            $manualAdditions = (float) $item->adjustments->where('type', 'addition')->sum('amount');
            $manualDeductions = (float) $item->adjustments->where('type', 'deduction')->sum('amount');
            $row = [
                $item->employee?->employee_code,
                $item->employee?->full_name,
                $item->employee?->designation?->name,
                $item->employee?->orgUnit?->name,
                $item->employee?->bank_account_number,
                $item->employee?->bank_ifsc_code,
                $item->employee?->bank_name,
                $item->employee?->bank_branch,
                ...$componentAmounts,
                (float) $item->basic_salary,
                (float) $item->gross_salary,
                (float) $item->total_deductions,
                $manualAdditions,
                $manualDeductions,
                (float) $item->leave_without_pay_days,
                (float) $item->lwp_deduction,
                (float) $item->net_salary,
                (float) $item->disbursement_pct,
                (float) $item->disbursed_amount,
                (float) $item->outstanding_amount,
                str($item->status)->replace('_', ' ')->title()->toString(),
                $this->adjustmentNotes($item),
            ];

            foreach ($row as $index => $value) {
                if ((is_int($value) || is_float($value)) && $headers[$index] !== 'Disbursement %') {
                    $sumColumns[$index] = ($sumColumns[$index] ?? 0) + $value;
                }
            }

            $rows[] = $row;
        }

        foreach ($sumColumns as $index => $total) {
            $totalRow[$index] = round($total, 2);
        }

        if ($items->isNotEmpty()) {
            $rows[] = [];
            $rows[] = $totalRow;
        }

        return $this->xlsx->make('Salary Statement', $rows, [
            'titleRows' => [1],
            'headerRows' => [$headerRow],
            'freezeRow' => $headerRow + 1,
            'autoFilterRow' => $headerRow,
            'columnWidths' => $this->salaryStatementColumnWidths(count($headers)),
        ]);
    }

    /**
     * @param  Collection<int, PayrollItem>  $items
     * @return array<string, string>
     */
    private function componentColumns(Collection $items): array
    {
        $columns = [];

        foreach ($items as $item) {
            foreach ($item->components->sortBy(['type', 'name']) as $component) {
                $key = $this->componentKey($component);
                $columns[$key] = str($component->type)->title().': '.$component->name;
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, string>  $componentColumns
     * @return array<int, float>
     */
    private function componentAmounts(PayrollItem $item, array $componentColumns): array
    {
        $amounts = array_fill_keys(array_keys($componentColumns), 0.0);

        foreach ($item->components as $component) {
            $key = $this->componentKey($component);

            if (array_key_exists($key, $amounts)) {
                $amounts[$key] += (float) $component->amount;
            }
        }

        return array_values($amounts);
    }

    private function componentKey(PayrollItemComponent $component): string
    {
        return "{$component->type}|{$component->name}";
    }

    private function adjustmentNotes(PayrollItem $item): string
    {
        return $item->adjustments
            ->map(fn ($adjustment): string => str($adjustment->type)->title()
                .': '.$adjustment->label
                .' Rs. '.number_format((float) $adjustment->amount, 2)
                .($adjustment->note ? ' - '.$adjustment->note : ''))
            ->implode('; ');
    }

    /**
     * @return array<int, int>
     */
    private function salaryStatementColumnWidths(int $headerCount): array
    {
        $widths = [
            1 => 18,
            2 => 28,
            3 => 24,
            4 => 28,
            5 => 24,
            6 => 16,
            7 => 24,
            8 => 24,
        ];

        for ($index = 9; $index <= $headerCount; $index++) {
            $widths[$index] = 18;
        }

        $widths[$headerCount] = 34;

        return $widths;
    }
}
