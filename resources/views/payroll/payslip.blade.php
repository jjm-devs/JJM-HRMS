<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payslip->payslip_number }}</title>
    <style>
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
    <header>
        <h1>JJM HRMS Payslip</h1>
        <p class="muted">{{ $payslip->payslip_number }} | {{ $batch->period_from->format('d M Y') }} to {{ $batch->period_to->format('d M Y') }}</p>
    </header>

    <section class="grid">
        <div class="box">
            <strong>{{ $employee->full_name }}</strong><br>
            Employee Code: {{ $employee->employee_code }}<br>
            Designation: {{ $employee->designation?->name ?? '-' }}<br>
            Office: {{ $employee->orgUnit?->name ?? '-' }}
        </div>
        <div class="box">
            Batch: {{ $batch->batch_number }}<br>
            Type: {{ $batch->typeLabel() }}<br>
            Payment Date: {{ $batch->payment_date?->format('d M Y') ?? '-' }}<br>
            Generated: {{ now()->format('d M Y, h:i A') }}
        </div>
    </section>

    <h2>Earnings</h2>
    <table>
        <thead>
            <tr>
                <th>Component</th>
                <th>Details</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($earnings as $component)
                <tr>
                    <td>{{ $component->name }}</td>
                    <td>{{ $component->calculation_details ?? '-' }}</td>
                    <td class="right">Rs. {{ number_format((float) $component->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No earnings recorded.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td colspan="2">Gross Salary</td>
                <td class="right">Rs. {{ number_format((float) $item->gross_salary, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Deductions</h2>
    <table>
        <thead>
            <tr>
                <th>Component</th>
                <th>Details</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deductions as $component)
                <tr>
                    <td>{{ $component->name }}</td>
                    <td>{{ $component->calculation_details ?? '-' }}</td>
                    <td class="right">Rs. {{ number_format((float) $component->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No deductions recorded.</td>
                </tr>
            @endforelse
            @foreach ($adjustments as $adjustment)
                <tr>
                    <td>{{ $adjustment->label }}</td>
                    <td>{{ $adjustment->note ?? 'Manual '.$adjustment->type }}</td>
                    <td class="right">{{ $adjustment->type === 'addition' ? '-' : '' }}Rs. {{ number_format((float) $adjustment->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="2">Total Deductions</td>
                <td class="right">Rs. {{ number_format((float) $item->total_deductions, 2) }}</td>
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

    <p class="muted">This is a system-generated payslip from JJM HRMS.</p>
</body>
</html>
