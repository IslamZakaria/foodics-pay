<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentTransferRequest;
use App\Services\PaymentXmlService;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    private PaymentXmlService $xmlService;

    public function __construct(PaymentXmlService $xmlService)
    {
        $this->xmlService = $xmlService;
    }

    /**
     * Generate payment transfer XML
     *
     * @param PaymentTransferRequest $request
     * @return Response
     */
    public function transfer(PaymentTransferRequest $request): Response
    {
        $xml = $this->xmlService->generatePaymentXml($request->validated());

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }
}