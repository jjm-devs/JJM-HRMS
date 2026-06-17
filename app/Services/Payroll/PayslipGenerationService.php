<?php

namespace App\Services\Payroll;

use App\Models\Document;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\Payslip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayslipGenerationService
{
    public function generateForBatch(PayrollBatch $batch): Collection
    {
        abort_unless(
            in_array($batch->status, ['approved', 'locked', 'disbursed'], true) || $batch->isLocked(),
            422,
            'Payslips can be generated only after payroll is approved or locked.',
        );

        return $batch->items()
            ->with([
                'employee.designation',
                'employee.orgUnit',
                'payrollBatch',
                'components',
                'adjustments.createdBy',
                'payslip.document',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (PayrollItem $item): Payslip => $this->generateForItem($item));
    }

    public function generateForItem(PayrollItem $item): Payslip
    {
        $item->loadMissing([
            'employee.designation',
            'employee.orgUnit',
            'payrollBatch',
            'components',
            'adjustments.createdBy',
            'payslip.document',
        ]);

        $batch = $item->payrollBatch;
        $employee = $item->employee;
        $payslipNumber = $this->payslipNumber($item);

        $payslip = $item->payslip()->firstOrCreate(
            ['payroll_item_id' => $item->id],
            [
                'payslip_number' => $payslipNumber,
                'status' => 'draft',
            ],
        );

        if ($payslip->payslip_number !== $payslipNumber) {
            $payslip->update(['payslip_number' => $payslipNumber]);
        }

        $html = view('payroll.payslip', [
            'batch' => $batch,
            'employee' => $employee,
            'item' => $item,
            'payslip' => $payslip,
            'earnings' => $item->components->where('type', 'earning')->values(),
            'deductions' => $item->components->where('type', 'deduction')->values(),
            'adjustments' => $item->adjustments,
        ])->render();

        $fileName = $payslipNumber.'.html';
        $filePath = 'payslips/'.Str::slug($batch->batch_number).'/'.$fileName;
        $disk = config('filesystems.default', 'local');

        Storage::disk($disk)->put($filePath, $html);

        $document = $payslip->document;
        $version = $document ? $document->version + 1 : 1;

        if ($document) {
            $document->update([
                'title' => 'Payslip '.$batch->period_to->format('M Y').' - '.$employee->full_name,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'disk' => $disk,
                'mime_type' => 'text/html',
                'file_size' => Storage::disk($disk)->size($filePath) ?: strlen($html),
                'version' => $version,
                'status' => 'issued',
                'uploaded_by' => Auth::id(),
            ]);
        } else {
            $document = Document::query()->create([
                'documentable_type' => $payslip->getMorphClass(),
                'documentable_id' => $payslip->id,
                'document_type_id' => null,
                'title' => 'Payslip '.$batch->period_to->format('M Y').' - '.$employee->full_name,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'disk' => $disk,
                'mime_type' => 'text/html',
                'file_size' => Storage::disk($disk)->size($filePath) ?: strlen($html),
                'version' => $version,
                'status' => 'issued',
                'uploaded_by' => Auth::id(),
            ]);
        }

        $payslip->update([
            'document_id' => $document->id,
            'generated_at' => now(),
            'status' => 'issued',
        ]);

        return $payslip->refresh();
    }

    private function payslipNumber(PayrollItem $item): string
    {
        return Str::of('SLIP-'.$item->payrollBatch->batch_number.'-'.$item->employee->employee_code)
            ->replaceMatches('/[^A-Za-z0-9-]+/', '-')
            ->trim('-')
            ->upper()
            ->toString();
    }
}
