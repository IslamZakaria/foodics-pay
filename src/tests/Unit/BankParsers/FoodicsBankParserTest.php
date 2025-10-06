<?php

namespace Tests\Unit\BankParsers;

use App\Services\BankParsers\FoodicsBankParser;
use PHPUnit\Framework\TestCase;

class FoodicsBankParserTest extends TestCase
{
    private FoodicsBankParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FoodicsBankParser();
    }

    public function test_parses_single_transaction_correctly(): void
    {
        $webhookBody = "20250615156,50#202506159000001#note/debt payment";

        $result = $this->parser->parse($webhookBody);

        $this->assertCount(1, $result);
        $this->assertEquals('202506159000001', $result[0]['reference']);
        $this->assertEquals(156.50, $result[0]['amount']);
        $this->assertEquals('2025-06-15', $result[0]['transaction_date']);
        $this->assertArrayHasKey('note', $result[0]['metadata']);
        $this->assertEquals('debt payment', $result[0]['metadata']['note']);
    }

    public function test_parses_multiple_transactions(): void
    {
        $webhookBody = "20250615100,00#REF001#key1/value1\n20250616200,50#REF002#key2/value2";

        $result = $this->parser->parse($webhookBody);

        $this->assertCount(2, $result);
        $this->assertEquals('REF001', $result[0]['reference']);
        $this->assertEquals(100.00, $result[0]['amount']);
        $this->assertEquals('REF002', $result[1]['reference']);
        $this->assertEquals(200.50, $result[1]['amount']);
    }

    public function test_handles_empty_metadata(): void
    {
        $webhookBody = "2025061550,00#REF003";

        $result = $this->parser->parse($webhookBody);

        $this->assertCount(1, $result);
        $this->assertEmpty($result[0]['metadata']);
    }

    public function test_throws_exception_for_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $webhookBody = "invalid_format";
        $this->parser->parse($webhookBody);
    }
}
