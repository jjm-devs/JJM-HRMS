<?php

namespace App\Services\Leave;

use App\Models\Document;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\Documents\SimplePdfService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveApplicationDocumentService
{
    public const PRINT_APPLICATION_TITLE = 'Leave Application Print';
    public const SIGNED_APPLICATION_TITLE = 'Signed Leave Application';

    public function __construct(
        private readonly SimplePdfService $pdf,
    ) {}

    public function generateApplicationPrint(LeaveApplication $leave, ?User $user = null): Document
    {
        $user ??= Auth::user();
        $leave->loadMissing(['employee.designation', 'employee.departmentStream', 'leaveType', 'submittedBy:id,name']);

        $existing = $leave->documents()
            ->where('title', self::PRINT_APPLICATION_TITLE)
            ->where('status', 'generated')
            ->latest('version')
            ->first();
        $version = ($existing?->version ?? 0) + 1;
        $fileName = 'leave-application-'.$leave->id.'.pdf';
        $path = "leave-requests/{$leave->id}/generated/leave-application-v{$version}.pdf";

        $pdf = $this->pdf->make(
            'Leave Application',
            $this->applicationLines($leave),
        );

        Storage::disk('local')->put($path, $pdf);

        if ($existing && Storage::disk($existing->disk)->exists($existing->file_path)) {
            Storage::disk($existing->disk)->delete($existing->file_path);
        }

        $document = $existing ?? new Document();
        $document->fill([
            'title' => self::PRINT_APPLICATION_TITLE,
            'file_name' => $fileName,
            'file_path' => $path,
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => strlen($pdf),
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
     * @return array<int, string>
     */
    private function applicationLines(LeaveApplication $leave): array
    {
        $employee = $leave->employee;

        return [
            'Application No: LEAVE-'.$leave->id,
            'Generated On: '.now()->format('d M Y, h:i A'),
            '',
            'Employee Name: '.($employee?->full_name ?? 'Employee'),
            'Employee Code: '.($employee?->employee_code ?? 'Not available'),
            'Designation: '.($employee?->designation?->name ?? 'Not available'),
            'Department Stream: '.($employee?->departmentStream?->name ?? 'Not available'),
            '',
            'Leave Type: '.($leave->leaveType?->name ?? 'Leave'),
            'Period: '.($leave->start_date?->format('d M Y') ?? '-').' to '.($leave->end_date?->format('d M Y') ?? '-'),
            'Total Days: '.number_format((float) $leave->total_days, 2),
            'Status: '.Str::of($leave->status)->replace('_', ' ')->title()->toString(),
            'Contact During Leave: '.($leave->contact_during_leave ?: 'Not provided'),
            '',
            'Reason:',
            $leave->reason ?: 'Not provided',
            '',
            'Employee Signature: ______________________________',
            'Date: ______________________________',
            '',
            'HR Remarks:',
            $leave->approval_remarks ?: '',
            '',
            'HR Signature: ______________________________',
            'Date: ______________________________',
        ];
    }
}
