<?php

namespace App\Services;

use DOMDocument;
use DOMElement;

class PaymentXmlService
{
    /**
     * Generate payment XML based on transfer details
     *
     * @param array $transferData
     * @return string XML string
     */
    public function generatePaymentXml(array $transferData): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('PaymentRequestMessage');
        $dom->appendChild($root);

        $this->addTransferInfo($dom, $root, $transferData);
        $this->addSenderInfo($dom, $root, $transferData);
        $this->addReceiverInfo($dom, $root, $transferData);
        $this->addNotes($dom, $root, $transferData);
        $this->addPaymentType($dom, $root, $transferData);
        $this->addChargeDetails($dom, $root, $transferData);

        return $dom->saveXML();
    }

    private function addTransferInfo(DOMDocument $dom, DOMElement $root, array $data): void
    {
        $transferInfo = $dom->createElement('TransferInfo');
        $root->appendChild($transferInfo);

        $this->appendElement($dom, $transferInfo, 'Reference', $data['reference']);
        $this->appendElement($dom, $transferInfo, 'Date', $data['date']);
        $this->appendElement($dom, $transferInfo, 'Amount', $data['amount']);
        $this->appendElement($dom, $transferInfo, 'Currency', $data['currency']);
    }

    private function addSenderInfo(DOMDocument $dom, DOMElement $root, array $data): void
    {
        $senderInfo = $dom->createElement('SenderInfo');
        $root->appendChild($senderInfo);

        $this->appendElement($dom, $senderInfo, 'AccountNumber', $data['sender_account']);
    }

    private function addReceiverInfo(DOMDocument $dom, DOMElement $root, array $data): void
    {
        $receiverInfo = $dom->createElement('ReceiverInfo');
        $root->appendChild($receiverInfo);

        $this->appendElement($dom, $receiverInfo, 'BankCode', $data['receiver_bank_code']);
        $this->appendElement($dom, $receiverInfo, 'AccountNumber', $data['receiver_account']);
        $this->appendElement($dom, $receiverInfo, 'BeneficiaryName', $data['beneficiary_name']);
    }

    private function addNotes(DOMDocument $dom, DOMElement $root, array $data): void
    {
        if (!empty($data['notes']) && is_array($data['notes'])) {
            $notesElement = $dom->createElement('Notes');
            $root->appendChild($notesElement);

            foreach ($data['notes'] as $note) {
                $this->appendElement($dom, $notesElement, 'Note', $note);
            }
        }
    }

    private function addPaymentType(DOMDocument $dom, DOMElement $root, array $data): void
    {
        $paymentType = $data['payment_type'] ?? 99;

        if ($paymentType !== 99) {
            $this->appendElement($dom, $root, 'PaymentType', $paymentType);
        }
    }

    private function addChargeDetails(DOMDocument $dom, DOMElement $root, array $data): void
    {
        $chargeDetails = $data['charge_details'] ?? 'SHA';

        if ($chargeDetails !== 'SHA') {
            $this->appendElement($dom, $root, 'ChargeDetails', $chargeDetails);
        }
    }

    private function appendElement(DOMDocument $dom, DOMElement $parent, string $name, $value): void
    {
        $element = $dom->createElement($name, htmlspecialchars((string) $value));
        $parent->appendChild($element);
    }
}