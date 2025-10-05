<?php

namespace Tests\Unit\Services;

use App\Services\PaymentXmlService;
use PHPUnit\Framework\TestCase;

class PaymentXmlServiceTest extends TestCase
{
    private PaymentXmlService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentXmlService();
    }

    public function test_generates_complete_xml_with_all_fields(): void
    {
        $data = [
            'reference' => 'e0f4763d-28ea-42d4-ac1c-c4013c242105',
            'date' => '2025-02-25 06:33:00+03',
            'amount' => 177.39,
            'currency' => 'SAR',
            'sender_account' => 'SA6980000204608016212908',
            'receiver_bank_code' => 'FDCSSARI',
            'receiver_account' => 'SA6980000204608016211111',
            'beneficiary_name' => 'Jane Doe',
            'notes' => ['Lorem Epsum', 'Dolor Sit Amet'],
            'payment_type' => 421,
            'charge_details' => 'RB',
        ];

        $xml = $this->service->generatePaymentXml($data);

        $this->assertStringContainsString('<Reference>e0f4763d-28ea-42d4-ac1c-c4013c242105</Reference>', $xml);
        $this->assertStringContainsString('<Amount>177.39</Amount>', $xml);
        $this->assertStringContainsString('<Note>Lorem Epsum</Note>', $xml);
        $this->assertStringContainsString('<Note>Dolor Sit Amet</Note>', $xml);
        $this->assertStringContainsString('<PaymentType>421</PaymentType>', $xml);
        $this->assertStringContainsString('<ChargeDetails>RB</ChargeDetails>', $xml);
    }

    public function test_excludes_notes_when_empty(): void
    {
        $data = [
            'reference' => 'REF123',
            'date' => '2025-02-25 06:33:00+03',
            'amount' => 100.00,
            'currency' => 'SAR',
            'sender_account' => 'SA123',
            'receiver_bank_code' => 'FDCSSARI',
            'receiver_account' => 'SA456',
            'beneficiary_name' => 'John Doe',
            'notes' => [],
        ];

        $xml = $this->service->generatePaymentXml($data);

        $this->assertStringNotContainsString('<Notes>', $xml);
        $this->assertStringNotContainsString('<Note>', $xml);
    }

    public function test_excludes_payment_type_when_99(): void
    {
        $data = [
            'reference' => 'REF123',
            'date' => '2025-02-25 06:33:00+03',
            'amount' => 100.00,
            'currency' => 'SAR',
            'sender_account' => 'SA123',
            'receiver_bank_code' => 'FDCSSARI',
            'receiver_account' => 'SA456',
            'beneficiary_name' => 'John Doe',
            'payment_type' => 99,
        ];

        $xml = $this->service->generatePaymentXml($data);

        $this->assertStringNotContainsString('<PaymentType>', $xml);
    }

    public function test_excludes_charge_details_when_sha(): void
    {
        $data = [
            'reference' => 'REF123',
            'date' => '2025-02-25 06:33:00+03',
            'amount' => 100.00,
            'currency' => 'SAR',
            'sender_account' => 'SA123',
            'receiver_bank_code' => 'FDCSSARI',
            'receiver_account' => 'SA456',
            'beneficiary_name' => 'John Doe',
            'charge_details' => 'SHA',
        ];

        $xml = $this->service->generatePaymentXml($data);

        $this->assertStringNotContainsString('<ChargeDetails>', $xml);
    }

    public function test_generates_valid_xml_structure(): void
    {
        $data = [
            'reference' => 'REF123',
            'date' => '2025-02-25 06:33:00+03',
            'amount' => 100.00,
            'currency' => 'SAR',
            'sender_account' => 'SA123',
            'receiver_bank_code' => 'FDCSSARI',
            'receiver_account' => 'SA456',
            'beneficiary_name' => 'John Doe',
        ];

        $xml = $this->service->generatePaymentXml($data);

        $doc = new \DOMDocument();
        $loaded = $doc->loadXML($xml);

        $this->assertTrue($loaded);
        $this->assertEquals('PaymentRequestMessage', $doc->documentElement->nodeName);
    }
}