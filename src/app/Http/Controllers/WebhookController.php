<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook from banks
     *
     * @param Request $request
     * @param string $bankType
     * @return JsonResponse
     */
    public function handle(Request $request, string $bankType): JsonResponse
    {
        $webhookBody = $request->getContent();

        if (empty($webhookBody)) {
            return response()->json([
                'success' => false,
                'message' => 'Empty webhook body',
            ], 400);
        }

        $clientId = $request->input('client_id', 1);

        ProcessWebhookJob::dispatch($webhookBody, $clientId, $bankType);

        return response()->json([
            'success' => true,
            'message' => 'Webhook received and queued for processing',
        ], 202);
    }
}