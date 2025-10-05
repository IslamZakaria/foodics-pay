<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

class AcmeBankParser implements BankParserInterface
{
    /**
     * Parse Acme Bank webhook format
     * Format: Amount (two decimals), "//", Reference, "//", Date
     * Example: 156,50//202506159000001//20250615
     *
     * @param string $webhookBody
     * @return array
     */
    public function parse(string $webhookBody): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $webhookBody)));
        $transactions = [];

        foreach ($lines as $line) {
            $transactions[] = $this->parseLine($line);
        }

        return $transactions;
    }

    private function parseLine(string $line): array
    {
        $parts = explode('//', $line);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException("Invalid Acme Bank transaction format");
        }

        $amount = $this->parseAmount(trim($parts[0]));
        $reference = trim($parts[1]);
        $date = $this->parseDate(trim($parts[2]));

        return [
            'reference' => $reference,
            'amount' => $amount,
            'transaction_date' => $date,
            'metadata' => [],
        ];
    }

    private function parseDate(string $dateStr): string
    {
        return Carbon::createFromFormat('Ymd', $dateStr)->format('Y-m-d');
    }

    private function parseAmount(string $amountStr): float
    {
        return (float) str_replace(',', '.', $amountStr);
    }
}