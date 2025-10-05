<?php

namespace Tests\Unit\BankParsers;

use App\Services\BankParsers\AcmeBankParser;
use PHPUnit\Framework\TestCase;

class AcmeBankParserTest extends TestCase
{
    private AcmeBankParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AcmeBankParser();
    }

    public function test_parses_single_transaction_correctly(): void
    {
        $webhookBody = "156,50//202506159000001//20250615";

        $result = $this->parser->parse($webhookBody);

        $this->assertCount(1, $result);
        $this->assertEquals('202506159000001', $result[0]['reference']);
        $this->assertEquals(156.50, $result[0]['amount']);
        $this->assertEquals('2025-06-15', $result[0]['transaction_date']);
        $this->assertEmpty($result[0]['metadata']);
    }

    public function test_parses_multiple_transactions(): void
    {
        $webhookBody = "100,00//REF001//20250615\n200,50//REF002//20250616";

        $result = $this->parser->parse($webhookBody);

        $this->assertCount(2, $result);
        $this->assertEquals('REF001', $result[0]['reference']);
        $this->assertEquals(100.00, $result[0]['amount']);
        $this->assertEquals('2025-06-15', $result[0]['transaction_date']);
        $this->assertEquals('REF002', $result[1]['reference']);
        $this->assertEquals(200.50, $result[1]['amount']);
        $this->assertEquals('2025-06-16', $result[1]['transaction_date']);
    }

    public function test_throws_exception_for_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $webhookBody = "invalid//format";
        $this->parser->parse($webhookBody);
    }
}