<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DelhiveryNdrService
{
    public function sendNdrAction(string $awb, string $action, $delhivery): array
    {
        try {
            $payload = [
                'data' => [
                    [
                        'waybill' => $awb,
                        'act' => $action,
                    ]
                ]
            ];

            $baseUrl = rtrim($delhivery->url ?? '', '/');

            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $delhivery->api_key,
                'Accept' => 'application/json',
            ])->withOptions([
                'verify' => true,
            ])->post($baseUrl . "/api/p/update", $payload);


            $body = $response->json(); 
            // Delhivery returns an UPL or request id inside response — attempt to find it
            $upl = null;
            if (is_array($body)) {
                // Attempt several common locations
                $upl = $body['request_id'] ?? $body['request_id'] ?? $body['data'][0]['request_id'] ?? $body['data'][0]['request_id'] ?? null;
            }

            return [
                'success' => $response->successful(),
                'upl_id' => $upl,
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Delhivery NDR API error', ['message' => $e->getMessage(), 'awb' => $awb, 'action' => $action]);
            return ['success' => false, 'upl_id' => null, 'response' => ['error' => $e->getMessage()]];
        }
    }

    /**
     * Get status for a given UPL id
     */
    public function getNdrStatus($uplId, $delhivery): array
    {
        try {

            $baseUrl = rtrim($delhivery->url ?? '', '/');
            $url = $baseUrl . '/api/cmu/get_bulk_upl/' . urlencode($uplId) . '?verbose=true';

            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $delhivery->api_key,
                'Accept' => 'application/json',
            ])->withOptions([
                'verify' => true,
            ])->get($url);
            
            return ['success' => $response->successful(), 'response' => $response->json()];
        } catch (\Throwable $e) {
            Log::error('Delhivery get NDR status error', ['message' => $e->getMessage(), 'upl' => $uplId]);
            return ['success' => false, 'response' => ['error' => $e->getMessage()]];
        }
    }
}
