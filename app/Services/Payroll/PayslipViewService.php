<?php

namespace App\Services\Payroll;

use App\Models\Payslip;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PayslipViewService
{
    /**
     * Stream the stored payslip HTML inline (in-browser, not a download) with a
     * print trigger injected, so the browser's "Save as PDF" / print dialog opens
     * automatically. The payslip markup is self-contained (emblem is embedded), so
     * it renders identically to the on-screen preview.
     */
    public function inlinePrintResponse(Payslip $payslip): Response
    {
        $document = $payslip->document;

        abort_if(! $document, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        $html = $this->injectPrintScript(
            Storage::disk($document->disk)->get($document->file_path),
        );

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$payslip->payslip_number.'.html"',
        ]);
    }

    private function injectPrintScript(string $html): string
    {
        $script = '<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},200);});</script>';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $script.'</body>', $html, 1);
        }

        return $html.$script;
    }
}
