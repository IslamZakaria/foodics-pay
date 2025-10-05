<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentTransferTest extends TestCase
{
    public function test_generates_payment_xml_successfully(): void
    {
        $payload = [
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

        $response = $this->postJson('/api/payments/transfer', $payload);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');

        $xml = $response->getContent();
        $this->assertStringContainsString('<Reference>e0f4763d-28ea-42d4-ac1c-c4013c242105</Reference>', $xml);
        $this->assertStringContainsString('<Amount>177.39</Amount>', $xml);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/payments/transfer', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'reference',
            'date',
            'amount',
            'currency',
            'sender_account',
            'receiver_bank_code',
            'receiver_account',
            'beneficiary_name',
        ]);
    }

    public function test_handles_optional_fields_correctly(): void
    {
        $payload = [
            'reference' => 'REF123',
            'date' => '2025-02-25 06:33:00+03',
            'amount' => 100.00,
            'currency' => 'SAR',
            'sender_account' => 'SA123',
            'receiver_bank_code' => 'FDCSSARI',
            'receiver_account' => 'SA456',
            'beneficiary_name' => 'John Doe',
            // No notes, payment_type defaults to 99, charge_details defaults to SHA
        ];

        $response = $this->postJson('/api/payments/transfer', $payload);

        $response->assertStatus(200);

        $xml = $response->getContent();
        // Should not contain optional elements with default values
        $this->assertStringNotContainsString('<Notes>', $xml);
        $this->assertStringNotContainsString('<PaymentType>', $xml);
        $this->assertStringNotContainsString('<ChargeDetails>', $xml);
    }
}
