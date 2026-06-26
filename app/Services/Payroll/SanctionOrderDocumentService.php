<?php

namespace App\Services\Payroll;

use App\Models\Document;
use App\Models\PayrollBatch;
use App\Models\PayrollItemComponent;
use App\Models\SalaryComponent;
use App\Models\SanctionActivity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class SanctionOrderDocumentService
{
    public const TITLE = 'Sanction Order';

    public function __construct(
        private readonly PayrollWorkflowService $workflow,
    ) {}

    /**
     * @param  array{activity_id:int, signatory?:string, reference_serial?:?string, reference_date?:?string, memo_serial?:?string, memo_date?:?string, copy_to?:array<int,string>}  $options
     */
    public function generate(PayrollBatch $batch, array $options, ?User $user = null): Document
    {
        $user ??= Auth::user();

        abort_unless($this->workflow->canGenerateFinalPayrollDocuments($batch, $user), 403);

        $activity = SanctionActivity::query()->findOrFail($options['activity_id']);

        $signatories = (array) config('sanction.signatories', []);
        $signatoryKey = $options['signatory'] ?? array_key_first($signatories);
        $signatory = $signatories[$signatoryKey] ?? (array) reset($signatories);

        $copyTo = array_values(array_filter(
            $options['copy_to'] ?? (array) config('sanction.copy_to_default', []),
            fn ($line) => trim((string) $line) !== '',
        ));

        $figures = $this->figures($batch);
        $referenceNo = $this->referenceNumber($batch, $options['reference_serial'] ?? '');
        $memoNo = $this->referenceNumber($batch, $options['memo_serial'] ?? '');

        $phpWord = $this->buildDocument(
            activity: $activity,
            signatory: $signatory,
            copyTo: $copyTo,
            figures: $figures,
            referenceNo: $referenceNo,
            referenceDate: trim((string) ($options['reference_date'] ?? '')),
            memoNo: $memoNo,
            memoDate: trim((string) ($options['memo_date'] ?? '')),
        );

        return $this->store($batch, $phpWord, $user);
    }

    // ── figures ─────────────────────────────────────────────────────────────

    /**
     * @return array{count:int, month:string, gross:float, itax:float, ptax:float, net:float, gross_words:string}
     */
    private function figures(PayrollBatch $batch): array
    {
        $ptaxComponentIds = SalaryComponent::query()->where('code', 'PTAX')->pluck('id');
        $itemIds = $batch->items()->pluck('id');

        $ptax = (float) PayrollItemComponent::query()
            ->whereIn('payroll_item_id', $itemIds)
            ->whereIn('salary_component_id', $ptaxComponentIds)
            ->sum('amount');

        // "Amount to be disbursed": a partial batch releases only its partial
        // disbursed total; regular/arrear batches release the full net.
        $net = (float) ($batch->isPartial() ? $batch->disbursed_total : $batch->net_total);
        $gross = round($net + $ptax, 2);

        return [
            'count' => $itemIds->count(),
            'month' => $batch->period_to->format('F Y'),
            'gross' => $gross,
            'itax' => 0.0,
            'ptax' => round($ptax, 2),
            'net' => round($net, 2),
            'gross_words' => $this->amountInWords($gross),
        ];
    }

    private function referenceNumber(PayrollBatch $batch, string $serial): string
    {
        $prefix = config('sanction.reference_prefix', 'JJMA-127/HRD/');
        $year = $batch->period_to->format('Y');

        return $prefix.$year.'/'.trim($serial);
    }

    // ── document build ──────────────────────────────────────────────────────

    /**
     * @param  array<int,string>  $signatory
     * @param  array<int,string>  $copyTo
     * @param  array<string,mixed>  $figures
     */
    private function buildDocument(
        SanctionActivity $activity,
        array $signatory,
        array $copyTo,
        array $figures,
        string $referenceNo,
        string $referenceDate,
        string $memoNo,
        string $memoDate,
    ): PhpWord {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1100,
            'marginRight' => 1100,
        ]);

        $account = config('sanction.account_no');

        // ── letterhead ──────────────────────────────────────────────────────
        $emblem = Storage::disk('public')->path('pngwing.com.png');
        if (is_file($emblem)) {
            $section->addImage($emblem, [
                'height' => 72,
                'width' => 42,
                'alignment' => Jc::CENTER,
            ]);
        }

        $section->addText('Govt. Of Assam', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText('Office of the Mission Director: Jal Jeevan Mission, Assam', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText('Public Health Engineering Department', [], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText('Hengrabari, Guwahati-36', [], ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);

        // full-width rule under the letterhead
        $section->addText('', [], ['borderBottomSize' => 8, 'borderBottomColor' => '000000', 'spaceAfter' => 120]);

        // ── reference / date line ───────────────────────────────────────────
        $this->addTwoColumnLine(
            $section,
            'NO. '.$referenceNo,
            'Dated: '.$referenceDate,
            ['bold' => true],
        );

        // ── title ───────────────────────────────────────────────────────────
        $section->addTextBreak(1);
        $section->addText('SANCTION ORDER', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);

        // ── subject ─────────────────────────────────────────────────────────
        $subject = $section->addTextRun(['spaceAfter' => 160]);
        $subject->addText('Sub : ');
        $subject->addText(
            'Release of Fund under '.$activity->name.' of Jal Jeevan Mission-Assam State Holding Account No. '.$account.'.',
            ['bold' => true],
        );

        // ── body ────────────────────────────────────────────────────────────
        $body = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 160]);
        $body->addText('With reference to the above, as approved by Mission Director JJM-Assam the fund is sanctioned under ');
        $body->addText($activity->name, ['bold' => true]);
        $body->addText(' of JJM-Assam Account No: ');
        $body->addText($account, ['bold' => true]);
        $body->addText(' against the Remuneration of ');
        $body->addText($figures['count'].' nos of '.$activity->staff_category, ['bold' => true]);
        $body->addText(' for the month of ');
        $body->addText($figures['month'], ['bold' => true]);
        $body->addText(' as mentioned below:');

        // ── amount table ────────────────────────────────────────────────────
        $this->addAmountTable($section, $figures);

        // ── terms ───────────────────────────────────────────────────────────
        $section->addTextBreak(1);
        $section->addText('Terms and conditions to be followed: -', [], ['spaceAfter' => 60]);
        foreach ((array) config('sanction.terms', []) as $index => $term) {
            $section->addText(($index + 1).'. '.$term, [], ['spaceAfter' => 40, 'indentation' => ['left' => 360]]);
        }

        // ── signatory ───────────────────────────────────────────────────────
        $this->addSignatory($section, $signatory);

        // ── memo + copy to ──────────────────────────────────────────────────
        $section->addTextBreak(1);
        $this->addTwoColumnLine(
            $section,
            'Memo No. '.$memoNo,
            'Dated: '.$memoDate,
            ['bold' => true],
        );
        $section->addText('Copy to:', [], ['spaceAfter' => 40]);
        foreach ($copyTo as $index => $line) {
            $section->addText(($index + 1).'. '.$line, [], ['spaceAfter' => 40, 'indentation' => ['left' => 360]]);
        }

        // ── signatory (repeat) ──────────────────────────────────────────────
        $this->addSignatory($section, $signatory);

        return $phpWord;
    }

    /**
     * @param  array<string,mixed>  $figures
     */
    private function addAmountTable(Section $section, array $figures): void
    {
        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'alignment' => JcTable::CENTER,
            'width' => 9000,
            'unit' => 'dxa',
        ]);

        $headStyle = ['bold' => true];
        $headPara = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $cellHead = ['valign' => 'center'];
        $w = [2600, 1900, 1900, 2600];

        // row 1
        $table->addRow();
        $table->addCell($w[0], $cellHead + ['vMerge' => 'restart'])->addText('Gross Amount (in Rs.)', $headStyle, $headPara);
        $table->addCell($w[1] + $w[2], $cellHead + ['gridSpan' => 2])->addText('Deductions', $headStyle, $headPara);
        $table->addCell($w[3], $cellHead + ['vMerge' => 'restart'])->addText('Net Amount (in Rs.)', $headStyle, $headPara);

        // row 2
        $table->addRow();
        $table->addCell($w[0], ['vMerge' => 'continue']);
        $table->addCell($w[1])->addText('I.Tax (in Rs.)', $headStyle, $headPara);
        $table->addCell($w[2])->addText('P.Tax (in Rs.)', $headStyle, $headPara);
        $table->addCell($w[3], ['vMerge' => 'continue']);

        // row 3 — figures
        $valPara = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $table->addRow();
        $table->addCell($w[0])->addText($this->money($figures['gross']), ['bold' => true], $valPara);
        $table->addCell($w[1])->addText($this->money($figures['itax']), [], $valPara);
        $table->addCell($w[2])->addText($this->money($figures['ptax']), [], $valPara);
        $table->addCell($w[3])->addText($this->money($figures['net']), ['bold' => true], $valPara);

        // row 4 — amount in words (spans all)
        $table->addRow();
        $wordsCell = $table->addCell(array_sum($w), ['gridSpan' => 4]);
        $words = $wordsCell->addTextRun(['spaceAfter' => 0]);
        $words->addText('Gross Amount: ', ['bold' => true]);
        $words->addText($figures['gross_words'], ['bold' => true]);
    }

    /**
     * @param  array<int,string>  $signatory
     */
    private function addSignatory(Section $section, array $signatory): void
    {
        $section->addTextBreak(2);
        foreach ($signatory as $line) {
            $section->addText($line, ['bold' => true], ['alignment' => Jc::END, 'spaceAfter' => 0]);
        }
    }

    private function addTwoColumnLine(Section $section, string $left, string $right, array $font = []): void
    {
        $table = $section->addTable([
            'width' => 9000,
            'unit' => 'dxa',
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
        ]);
        $table->addRow();
        $table->addCell(5400)->addText($left, $font, ['spaceAfter' => 0]);
        $table->addCell(3600)->addText($right, $font, ['alignment' => Jc::END, 'spaceAfter' => 0]);
    }

    // ── storage ─────────────────────────────────────────────────────────────

    private function store(PayrollBatch $batch, PhpWord $phpWord, ?User $user): Document
    {
        $title = self::TITLE;
        $existing = $batch->documents()
            ->where('title', $title)
            ->where('status', 'generated')
            ->latest('version')
            ->first();
        $version = ($existing?->version ?? 0) + 1;

        $fileName = 'sanction-order-'.$batch->batch_number.'.docx';
        $path = "payroll-batch-documents/{$batch->id}/sanction-order-v{$version}.docx";

        $tmp = tempnam(sys_get_temp_dir(), 'sanction').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        $contents = file_get_contents($tmp);
        @unlink($tmp);

        Storage::disk('local')->put($path, $contents);

        if ($existing && Storage::disk($existing->disk)->exists($existing->file_path)) {
            Storage::disk($existing->disk)->delete($existing->file_path);
        }

        $document = $existing ?? new Document;
        $document->fill([
            'title' => $title,
            'file_name' => $fileName,
            'file_path' => $path,
            'disk' => 'local',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => strlen($contents),
            'version' => $version,
            'status' => 'generated',
            'uploaded_by' => $user?->id,
            'remarks' => 'Generated sanction order from payroll batch.',
        ]);
        $document->documentable()->associate($batch);
        $document->save();

        return $document->refresh();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function money(float $amount): string
    {
        return number_format($amount, 2);
    }

    private function amountInWords(float $amount): string
    {
        $number = (int) floor($amount);

        if ($number === 0) {
            return 'Rupees Zero only';
        }

        return 'Rupees '.$this->indianWords($number).' only';
    }

    private function indianWords(int $n): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $two = function (int $num) use ($ones, $tens): string {
            if ($num === 0) {
                return '';
            }
            if ($num < 20) {
                return $ones[$num];
            }

            return trim($tens[intdiv($num, 10)].' '.$ones[$num % 10]);
        };

        $three = function (int $num) use ($ones, $two): string {
            $str = '';
            if ($num >= 100) {
                $str .= $ones[intdiv($num, 100)].' Hundred';
                $num %= 100;
                if ($num) {
                    $str .= ' ';
                }
            }

            return trim($str.$two($num));
        };

        $crore = intdiv($n, 10000000);
        $n %= 10000000;
        $lakh = intdiv($n, 100000);
        $n %= 100000;
        $thousand = intdiv($n, 1000);
        $hundred = $n % 1000;

        $parts = [];
        if ($crore) {
            $parts[] = $three($crore).' Crore';
        }
        if ($lakh) {
            $parts[] = $two($lakh).' Lakh';
        }
        if ($thousand) {
            $parts[] = $two($thousand).' Thousand';
        }
        if ($hundred) {
            $parts[] = $three($hundred);
        }

        return implode(' ', $parts);
    }
}
