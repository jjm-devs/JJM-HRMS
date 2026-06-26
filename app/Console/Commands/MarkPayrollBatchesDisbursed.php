<?php

namespace App\Console\Commands;

use App\Models\PayrollBatch;
use App\Services\Payroll\PayrollWorkflowService;
use Illuminate\Console\Command;

class MarkPayrollBatchesDisbursed extends Command
{
    protected $signature = 'payroll:mark-disbursed';

    protected $description = 'Mark locked payroll batches as disbursed when their payment date has been reached.';

    public function handle(PayrollWorkflowService $workflowService): void
    {
        $batches = PayrollBatch::query()
            ->where('status', 'locked')
            ->whereNotNull('payment_date')
            ->whereDate('payment_date', '<=', now()->toDateString())
            ->get();

        if ($batches->isEmpty()) {
            $this->info('No batches due for disbursement.');
            return;
        }

        foreach ($batches as $batch) {
            $workflowService->markAsDisbursed($batch);
            $this->info("Marked disbursed: {$batch->batch_number} (payment date: {$batch->payment_date->toDateString()})");
        }

        $this->info("{$batches->count()} batch(es) marked as disbursed.");
    }
}
