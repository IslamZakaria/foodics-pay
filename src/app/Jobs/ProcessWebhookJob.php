<?php

namespace App\Jobs;

use App\Services\TransactionImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    private string $webhookBody;
    private int $clientId;
    private string $bankType;

    /**
     * Create a new job instance.
     */
    public function __construct(string $webhookBody, int $clientId, string $bankType)
    {
        $this->webhookBody = $webhookBody;
        $this->clientId = $clientId;
        $this->bankType = $bankType;
    }

    /**
     * Execute the job.
     */
    public function handle(TransactionImportService $importService): void
    {
        try {
            $result = $importService->import(
                $this->webhookBody,
                $this->clientId,
                $this->bankType
            );

            Log::info('Webhook processed successfully', [
                'client_id' => $this->clientId,
                'bank_type' => $this->bankType,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process webhook', [
                'client_id' => $this->clientId,
                'bank_type' => $this->bankType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed permanently', [
            'client_id' => $this->clientId,
            'bank_type' => $this->bankType,
            'error' => $exception->getMessage(),
        ]);
    }
}