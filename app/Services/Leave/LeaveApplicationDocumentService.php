<?php

namespace App\Services\Leave;

use App\Models\Document;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveApplicationDocumentService
{
    public const PRINT_APPLICATION_TITLE = 'Leave Application Print';

    public const SIGNED_APPLICATION_TITLE = 'Signed Leave Application';

    /**
     * Render the printable leave application as a self-contained HTML document
     * (opened in-browser and printed / saved as PDF). Stored as a Document so it
     * keeps a generated-document trail.
     */
    public function generateApplicationPrint(LeaveApplication $leave, ?User $user = null): Document
    {
        $user ??= Auth::user();
        $leave->loadMissing([
            'employee.designation',
            'employee.departmentStream',
            'leaveType',
            'approvedBy:id,name',
        ]);

        $html = view('leave.application', $this->viewData($leave))->render();

        $existing = $leave->documents()
            ->where('title', self::PRINT_APPLICATION_TITLE)
            ->where('status', 'generated')
            ->latest('version')
            ->first();
        $version = ($existing?->version ?? 0) + 1;
        $fileName = 'leave-application-'.$leave->id.'.html';
        $path = "leave-requests/{$leave->id}/generated/leave-application-v{$version}.html";

        Storage::disk('local')->put($path, $html);

        if ($existing && Storage::disk($existing->disk)->exists($existing->file_path)) {
            Storage::disk($existing->disk)->delete($existing->file_path);
        }

        $document = $existing ?? new Document;
        $document->fill([
            'title' => self::PRINT_APPLICATION_TITLE,
            'file_name' => $fileName,
            'file_path' => $path,
            'disk' => 'local',
            'mime_type' => 'text/html',
            'file_size' => strlen($html),
            'version' => $version,
            'status' => 'generated',
            'uploaded_by' => $user?->id,
            'remarks' => 'Generated printable leave application.',
        ]);
        $document->documentable()->associate($leave);
        $document->save();

        return $document->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(LeaveApplication $leave): array
    {
        $employee = $leave->employee;
        $totalDays = (int) round((float) $leave->total_days);
        $appliedOn = $leave->created_at ?? now();

        return [
            'referenceNo' => 'JJMA/LEAVE/'.$appliedOn->format('Y').'/'.str_pad((string) $leave->id, 4, '0', STR_PAD_LEFT),
            'appliedDate' => $appliedOn->format('d F Y'),
            'employeeName' => $employee?->full_name ?? 'Employee',
            'employeeCode' => $employee?->employee_code ?? '—',
            'designation' => $employee?->designation?->name ?? 'Employee',
            'department' => $employee?->departmentStream?->name ?? 'Public Health Engineering Department',
            'leaveTypeName' => $leave->leaveType?->name ?? 'Leave',
            'fromDate' => $leave->start_date?->format('d F Y') ?? '—',
            'toDate' => $leave->end_date?->format('d F Y') ?? '—',
            'totalDays' => $totalDays,
            'totalDaysRaw' => $leave->total_days,
            'daysInWords' => $this->numberToWords($totalDays),
            'reason' => $leave->reason ?: 'Not provided.',
            'statusLabel' => Str::headline($leave->status),
            'approverName' => $leave->status === LeaveApplication::STATUS_APPROVED && $leave->approvedBy
                ? $leave->approvedBy->name
                : '(HR / Approving Officer)',
        ];
    }

    private function numberToWords(int $n): string
    {
        $ones = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        if ($n < 0) {
            return (string) $n;
        }
        if ($n < 20) {
            return $ones[$n];
        }
        if ($n < 100) {
            $word = $tens[intdiv($n, 10)];

            return $n % 10 ? $word.'-'.$ones[$n % 10] : $word;
        }

        return (string) $n;
    }
}
