<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    /**
     * Bulk insert transactions with idempotency
     * Ignores duplicates based on unique reference constraint
     *
     * @param array $transactions
     * @param int $clientId
     * @param string $bankType
     * @return int Number of inserted transactions
     */
    public function bulkInsert(array $transactions, int $clientId, string $bankType): int
    {
        if (empty($transactions)) {
            return 0;
        }

        $insertData = [];
        $now = now();

        foreach ($transactions as $transaction) {
            $insertData[] = [
                'reference' => $transaction['reference'],
                'client_id' => $clientId,
                'amount' => $transaction['amount'],
                'transaction_date' => $transaction['transaction_date'],
                'bank_type' => $bankType,
                'metadata' => json_encode($transaction['metadata']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inserted = 0;

        try {
            DB::transaction(function () use ($insertData, &$inserted) {
                foreach ($insertData as $data) {
                    try {
                        DB::table('transactions')->insert($data);
                        $inserted++;
                    } catch (\Illuminate\Database\QueryException $e) {
                        if ($e->getCode() === '23000') {
                            continue;
                        }
                        throw $e;
                    }
                }
            });
        } catch (\Exception $e) {
            throw $e;
        }

        return $inserted;
    }

    /**
     * Get transactions by client ID
     *
     * @param int $clientId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByClientId(int $clientId)
    {
        return Transaction::where('client_id', $clientId)
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Check if reference exists
     *
     * @param string $reference
     * @return bool
     */
    public function referenceExists(string $reference): bool
    {
        return Transaction::where('reference', $reference)->exists();
    }
}