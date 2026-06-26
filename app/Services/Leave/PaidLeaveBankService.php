<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the Paid Leave monthly bank.
 *
 * Rule: each employee gets a fixed number of Paid Leave days per CALENDAR month
 * (no carry-over). Within a month the earliest days are covered by the bank; any
 * days beyond the allowance are "excess" and become a salary deduction in payroll.
 */
class PaidLeaveBankService
{
    public const MONTHLY_ALLOWANCE = 2.0;

    private ?LeaveType $cachedType = null;

    private bool $resolved = false;

    public function paidLeaveType(): ?LeaveType
    {
        if (! $this->resolved) {
            $this->cachedType = LeaveType::query()
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query
                        ->where('code', 'PL')
                        ->orWhere('code', 'like', 'PL-%')
                        ->orWhere('name', 'like', '%Paid%');
                })
                ->orderByRaw("CASE WHEN code = 'PL' THEN 0 ELSE 1 END")
                ->first();
            $this->resolved = true;
        }

        return $this->cachedType;
    }

    public function isPaidLeaveType(?int $leaveTypeId): bool
    {
        $type = $this->paidLeaveType();

        return $type !== null && $leaveTypeId !== null && (int) $leaveTypeId === (int) $type->id;
    }

    /**
     * Paid Leave days the employee has used in the given calendar month,
     * counted from application date ranges (submitted, under review, or approved).
     */
    public function usedInMonth(Employee $employee, int $year, int $month, ?int $excludeApplicationId = null): float
    {
        $type = $this->paidLeaveType();

        if (! $type) {
            return 0.0;
        }

        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        return $employee->leaveApplications()
            ->where('leave_type_id', $type->id)
            ->when($excludeApplicationId, fn ($q) => $q->whereKeyNot($excludeApplicationId))
            ->whereIn('status', [
                LeaveApplication::STATUS_SUBMITTED,
                LeaveApplication::STATUS_UNDER_REVIEW,
                LeaveApplication::STATUS_APPROVED,
            ])
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->get()
            ->sum(fn (LeaveApplication $leave): int => $this->overlapDays($leave, $monthStart, $monthEnd));
    }

    public function remainingInMonth(Employee $employee, int $year, int $month, ?int $excludeApplicationId = null): float
    {
        return max(self::MONTHLY_ALLOWANCE - $this->usedInMonth($employee, $year, $month, $excludeApplicationId), 0.0);
    }

    /**
     * From a chronologically sorted list of dates, return the ones that fall
     * beyond the monthly allowance (i.e. should be deducted).
     *
     * @param  array<int,string>  $sortedDates  Y-m-d strings, ascending
     * @return array<int,string>
     */
    public function excessFromSortedDates(array $sortedDates): array
    {
        return array_slice($sortedDates, (int) self::MONTHLY_ALLOWANCE);
    }

    private function overlapDays(LeaveApplication $leave, CarbonImmutable $monthStart, CarbonImmutable $monthEnd): int
    {
        $start = CarbonImmutable::parse($leave->start_date)->max($monthStart);
        $end = CarbonImmutable::parse($leave->end_date)->min($monthEnd);

        if ($end->lessThan($start)) {
            return 0;
        }

        return iterator_count(CarbonPeriod::create($start, $end));
    }

    /**
     * Allocate a Collection of approved Paid Leave applications against the
     * monthly bank, de-duplicated by calendar date — an employee can only be on
     * leave once per day, so two applications covering the same date count once.
     *
     * Each distinct date is owned by the earliest application that covers it.
     * Per calendar month the first {allowance} distinct dates are banked; any
     * further dates are "excess" and attributed to their owning application.
     *
     * @param  Collection<int,LeaveApplication>  $applications
     * @return array<int, array<int,string>>  applicationId => list of excess Y-m-d dates it owns
     */
    public function allocateExcess(Collection $applications): array
    {
        // Earliest start (then id) claims each date first.
        $ordered = $applications
            ->sort(fn (LeaveApplication $a, LeaveApplication $b): int => [$a->start_date->timestamp, $a->id] <=> [$b->start_date->timestamp, $b->id])
            ->values();

        // Distinct date => owning application id.
        $owner = [];
        foreach ($ordered as $application) {
            foreach (CarbonPeriod::create($application->start_date, $application->end_date) as $date) {
                $key = $date->format('Y-m-d');
                $owner[$key] ??= $application->id;
            }
        }

        // Group distinct dates per calendar month.
        $byMonth = [];
        foreach (array_keys($owner) as $date) {
            $byMonth[substr($date, 0, 7)][] = $date;
        }

        $excessByApp = [];
        foreach ($byMonth as $dates) {
            sort($dates);
            foreach ($this->excessFromSortedDates($dates) as $excessDate) {
                $excessByApp[$owner[$excessDate]][] = $excessDate;
            }
        }

        return $excessByApp;
    }
}
