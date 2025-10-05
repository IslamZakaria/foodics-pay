<?php

namespace App\Services\BankParsers;

interface BankParserInterface
{
    /**
     * Parse webhook body and return array of transactions
     *
     * @param string $webhookBody
     * @return array Array of transaction data
     */
    public function parse(string $webhookBody): array;
}