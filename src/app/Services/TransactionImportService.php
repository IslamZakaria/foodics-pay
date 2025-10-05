<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Services\BankParsers\BankParserInterface;
use App\Services\BankParsers\FoodicsBankParser;
use App\Services\BankParsers\AcmeBankParser;
use App\Exceptions\UnsupportedBankException;

class TransactionImportService
{
    private TransactionRepository $repository;

    public function __construct(TransactionRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Import transactions from webhook
     *
     * @param string $webhookBody
     * @param int $clientId
     * @param string $bankType
     * @return array
     * @throws UnsupportedBankException
     */
    public function import(string $webhookBody, int $clientId, string $bankType): array
    {
        $parser = $this->getParser($bankType);
        $transactions = $parser->parse($webhookBody);

        $inserted = $this->repository->bulkInsert($transactions, $clientId, $bankType);

        return [
            'total' => count($transactions),
            'inserted' => $inserted,
            'duplicates' => count($transactions) - $inserted,
        ];
    }

    /**
     * Get appropriate parser based on bank type
     *
     * @param string $bankType
     * @return BankParserInterface
     * @throws UnsupportedBankException
     */
    private function getParser(string $bankType): BankParserInterface
    {
        return match (strtolower($bankType)) {
            'foodics' => new FoodicsBankParser(),
            'acme' => new AcmeBankParser(),
            default => throw new UnsupportedBankException("Bank type '{$bankType}' is not supported"),
        };
    }
}