<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_queues_job_for_processing(): void
    {
        Queue::fake();

        $webhookBody = "20250615156,50#202506159000001#note/debt payment";

        $response = $this->postJson('/api/webhooks/foodics', ['client_id' => 1], [], [], $webhookBody);

        $response->assertStatus(202);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook received and queued for processing',
        ]);

        Queue::assertPushed(ProcessWebhookJob::class);
    }

    public function test_foodics_webhook_creates_transactions(): void
    {
        $webhookBody = "20250615156,50#202506159000001#note/debt payment\n20250616200,00#202506169000002#type/salary";

        $this->postJson('/api/webhooks/foodics', ['client_id' => 1], [], [], $webhookBody);

        $this->artisan('queue:work --once');

        $this->assertDatabaseCount('transactions', 2);
        
        $this->assertDatabaseHas('transactions', [
            'reference' => '202506159000001',
            'amount' => 156.50,
            'client_id' => 1,
        ]);

        $this->assertDatabaseHas('transactions', [
            'reference' => '202506169000002',
            'amount' => 200.00,
            'client_id' => 1,
        ]);
    }

    public function test_acme_webhook_creates_transactions(): void
    {
        $webhookBody = "156,50//202506159000001//20250615";

        $this->postJson('/api/webhooks/acme', ['client_id' => 1], [], [], $webhookBody);

        $this->artisan('queue:work --once');

        $this->assertDatabaseHas('transactions', [
            'reference' => '202506159000001',
            'amount' => 156.50,
            'client_id' => 1,
            'bank_type' => 'acme',
        ]);
    }

    public function test_duplicate_transactions_are_ignored(): void
    {
        $webhookBody = "20250615156,50#202506159000001#note/debt payment";

        $this->postJson('/api/webhooks/foodics', ['client_id' => 1], [], [], $webhookBody);
        $this->artisan('queue:work --once');

        $this->assertDatabaseCount('transactions', 1);

        $this->postJson('/api/webhooks/foodics', ['client_id' => 1], [], [], $webhookBody);
        $this->artisan('queue:work --once');

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_handles_large_webhook_with_1000_transactions_efficiently(): void
    {
        $lines = [];
        for ($i = 1; $i <= 1000; $i++) {
            $reference = sprintf('REF%06d', $i);
            $amount = number_format(rand(100, 10000) / 100, 2, ',', '');
            $lines[] = "20250615{$amount}#{$reference}#note/transaction {$i}";
        }
        $webhookBody = implode("\n", $lines);

        $startTime = microtime(true);

        $this->postJson('/api/webhooks/foodics', ['client_id' => 1], [], [], $webhookBody);
        $this->artisan('queue:work --once --timeout=120');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertDatabaseCount('transactions', 1000);
        $this->assertLessThan(10, $executionTime, 'Processing 1000 transactions should take less than 10 seconds');
    }

    public function test_returns_error_for_empty_webhook_body(): void
    {
        $response = $this->postJson('/api/webhooks/foodics', ['client_id' => 1]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Empty webhook body',
        ]);
    }

    public function test_metadata_is_stored_correctly(): void
    {
        $webhookBody = "20250615156,50#REF001#note/payment\ncategory/expense";

        $this->postJson('/api/webhooks/foodics', ['client_id' => 1], [], [], $webhookBody);
        $this->artisan('queue:work --once');

        $transaction = Transaction::where('reference', 'REF001')->first();
        
        $this->assertNotNull($transaction);
        $this->assertIsArray($transaction->metadata);
        $this->assertEquals('payment', $transaction->metadata['note']);
        $this->assertEquals('expense', $transaction->metadata['category']);
    }
}