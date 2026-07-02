<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $referenceNo }}</title>
    <style>
        @page { size: A4; margin: 14mm 13mm 12mm; }
        @media print { body { margin: 0; } }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            color: #1a1a1a;
            font-size: 12.5pt;
            line-height: 1.6;
            margin: 14mm 13mm 12mm;
        }
        .corner-ref { text-align: right; font-size: 9pt; color: #555; line-height: 1.45; }
        .title {
            text-align: center; font-weight: bold; letter-spacing: 3px; font-size: 14pt;
            margin: 6px 0 22px; text-transform: uppercase; text-decoration: underline; text-underline-offset: 5px;
        }
        .addr { margin-bottom: 14px; }
        .subject { margin: 14px 0 6px; }
        .body p { text-align: justify; text-indent: 2.2em; margin: 9px 0; }
        .body p.flush { text-indent: 0; }
        table.details { width: 100%; border-collapse: collapse; margin: 20px 0 6px; font-size: 11pt; }
        table.details td { padding: 6px 9px; border: 1px solid #8a8a8a; }
        table.details td.k { width: 17%; font-weight: bold; background: #f2f2f2; }
        .signs { display: flex; justify-content: flex-end; margin-top: 54px; }
        .sig { width: 44%; text-align: center; }
        .sig .pad { height: 40px; }
        .sig .line { border-top: 1px solid #111; padding-top: 5px; font-size: 10.5pt; line-height: 1.4; }
        .sig .who { font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 8pt; color: #999; border-top: 0.5px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="corner-ref">No. {{ $referenceNo }}<br>Dated: {{ $appliedDate }}</div>

    <div class="title">Leave Application</div>

    <div class="addr">
        To,<br>
        The Mission Director,<br>
        Jal Jeevan Mission, Assam.
    </div>

    <div class="subject"><strong>Subject:</strong> Application for {{ $leaveTypeName }} for {{ $totalDays }} ({{ $daysInWords }}) day{{ $totalDays == 1 ? '' : 's' }}.</div>

    <div class="body">
        <p class="flush">Respected Sir/Madam,</p>
        <p>I, <strong>{{ $employeeName }}</strong> ({{ $employeeCode }}), working as <strong>{{ $designation }}</strong>
            in the {{ $department }}, most respectfully beg to state that I am in need of leave for a period of
            <strong>{{ $totalDays }} ({{ $daysInWords }}) day{{ $totalDays == 1 ? '' : 's' }}</strong>, with effect from
            <strong>{{ $fromDate }}</strong> to <strong>{{ $toDate }}</strong>, on account of the reason stated below.</p>
        <p><strong>Reason:</strong>&nbsp; {{ $reason }}</p>
        <p>I therefore request you to kindly grant me leave for the above-mentioned period and oblige.</p>
        <p class="flush">Thanking you.</p>
    </div>

    <table class="details">
        <tr><td class="k">Leave Type</td><td>{{ $leaveTypeName }}</td><td class="k">Total Days</td><td>{{ number_format((float) $totalDaysRaw, 2) }}</td></tr>
        <tr><td class="k">From</td><td>{{ $fromDate }}</td><td class="k">To</td><td>{{ $toDate }}</td></tr>
        <tr><td class="k">Status</td><td>{{ $statusLabel }}</td><td class="k">Applied On</td><td>{{ $appliedDate }}</td></tr>
    </table>

    <div class="signs">
        <div class="sig">
            <div class="pad"></div>
            <div class="line">
                Signature of the Sanctioning Authority<br>
                <span class="who">{{ $approverName }}</span><br>
                Date: ____________
            </div>
        </div>
    </div>

    <div class="footer">System-generated leave application — JJM HRMS &nbsp;·&nbsp; Ref. No. {{ $referenceNo }}</div>
</body>
</html>
