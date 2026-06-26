<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payslip->payslip_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        @media print {
            body { margin: 0; }
        }
        body { color: #0f172a; font-family: Arial, sans-serif; font-size: 13px; line-height: 1.45; margin: 32px; }
        h1 { font-size: 22px; margin: 0; }
        h2 { font-size: 15px; margin: 24px 0 8px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; font-size: 11px; letter-spacing: .04em; text-transform: uppercase; }
        .muted { color: #64748b; }
        .grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 18px; }
        .box { border: 1px solid #cbd5e1; padding: 10px; }
        .right { text-align: right; }
        .total { background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <x-documents.govt-letterhead :title="'Salary Slip for '.$batch->period_to->format('F Y')" />

    <section class="grid">
        <div class="box">
            <strong>{{ $employee->full_name }}</strong><br>
            Employee Code: {{ $employee->employee_code }}<br>
            Designation: {{ $employee->designation?->name ?? '-' }}<br>
            Office: {{ $employee->orgUnit?->name ?? '-' }}
        </div>
        <div class="box">
            Salary Month: {{ $batch->period_to->format('F Y') }}<br>
            Pay Period: {{ $batch->period_from->format('d M Y') }} – {{ $batch->period_to->format('d M Y') }}<br>
            Payment Date: {{ $batch->payment_date?->format('d M Y') ?? '-' }}<br>
            Generated: {{ now()->format('d M Y, h:i A') }}
        </div>
    </section>

    <h2>Earnings</h2>
    <table>
        <thead>
            <tr>
                <th>Component</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $additionAdjustments = $adjustments->where('type', 'addition');
                $deductionAdjustments = $adjustments->where('type', 'deduction');
                $additionsTotal = (float) $additionAdjustments->sum('amount');
                $deductionsTotal = (float) $deductionAdjustments->sum('amount');
                $hasEarnings = $earnings->isNotEmpty() || $additionAdjustments->isNotEmpty();
                $hasDeductions = $deductions->isNotEmpty() || $deductionAdjustments->isNotEmpty();
            @endphp
            @foreach ($earnings as $component)
                <tr>
                    <td>{{ $component->name }}</td>
                    <td class="right">Rs. {{ number_format((float) $component->amount, 2) }}</td>
                </tr>
            @endforeach
            @foreach ($additionAdjustments as $adjustment)
                <tr>
                    <td>{{ $adjustment->label }}</td>
                    <td class="right">Rs. {{ number_format((float) $adjustment->amount, 2) }}</td>
                </tr>
            @endforeach
            @unless ($hasEarnings)
                <tr>
                    <td colspan="2">No earnings recorded.</td>
                </tr>
            @endunless
            <tr class="total">
                <td>Gross Salary</td>
                <td class="right">Rs. {{ number_format((float) $item->gross_salary + $additionsTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Deductions</h2>
    <table>
        <thead>
            <tr>
                <th>Component</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deductions as $component)
                <tr>
                    <td>{{ $component->name }}</td>
                    <td class="right">Rs. {{ number_format((float) $component->amount, 2) }}</td>
                </tr>
            @endforeach
            @foreach ($deductionAdjustments as $adjustment)
                <tr>
                    <td>{{ $adjustment->label }}</td>
                    <td class="right">Rs. {{ number_format((float) $adjustment->amount, 2) }}</td>
                </tr>
            @endforeach
            @unless ($hasDeductions)
                <tr>
                    <td colspan="2">No deductions recorded.</td>
                </tr>
            @endunless
            <tr class="total">
                <td>Total Deductions</td>
                <td class="right">Rs. {{ number_format((float) $item->total_deductions + $deductionsTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Net Pay</h2>
    <table>
        <tbody>
            <tr>
                <th>Net Payable</th>
                <td class="right">Rs. {{ number_format((float) $item->net_salary, 2) }}</td>
            </tr>
            <tr>
                <th>Disbursed</th>
                <td class="right">Rs. {{ number_format((float) $item->disbursed_amount, 2) }}</td>
            </tr>
            <tr>
                <th>Outstanding</th>
                <td class="right">Rs. {{ number_format((float) $item->outstanding_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top:24px;">This is a system-generated payslip.</p>
</body>
</html>
