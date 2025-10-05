<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

class FoodicsBankParser implements BankParserInterface
{
    /**
     * Parse Foodics Bank webhook format
     * Format: Date, Amount (two decimals), "#", Reference, "#", Key-value pairs where Key is before "/" and value is after
     * Example: 20250615156,50#202506159000001#note/debt payment\nmarch/internal_reference/A462JE81
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
        $parts = explode('#', $line);

        if (count($parts) < 2) {
            throw new \InvalidArgumentException("Invalid Foodics Bank transaction format");
        }

        $datePart = trim($parts[0]);
        $reference = trim($parts[1]);

        $date = $this->parseDate(substr($datePart, 0, 8));
        $amount = $this->parseAmount(substr($datePart, 8));

        $metadata = [];
        if (isset($parts[2])) {
            $metadata = $this->parseMetadata($parts[2]);
        }

        return [
            'reference' => $reference,
            'amount' => $amount,
            'transaction_date' => $date,
            'metadata' => $metadata,
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

    private function parseMetadata(string $metadataStr): array
    {
        $metadata = [];
        $pairs = array_filter(array_map('trim', explode("\n", $metadataStr)));

        foreach ($pairs as $pair) {
            if (str_contains($pair, '/')) {
                [$key, $value] = explode('/', $pair, 2);
                $metadata[trim($key)] = trim($value);
            }
        }

        return $metadata;
    }
}