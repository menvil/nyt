<?php

namespace App\Http\Controllers\Api\V1;

//use App\Http\Controllers\Controller;
use App\Http\Requests\BestSellersHistoryRequest;
use App\Services\NewYorkTimesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;

class BestSellersController extends Controller
{
    private NewYorkTimesService $nytService;

    public function __construct(NewYorkTimesService $nytService)
    {
        $this->nytService = $nytService;
    }

    /**
     * Get best sellers history
     *
     * @param BestSellersHistoryRequest $request
     * @return JsonResponse
     */
    public function history(BestSellersHistoryRequest $request): JsonResponse
    {
        try {
            $data = $this->nytService->getBestSellersHistory($request->validated());
            return response()->json($data);
        } catch (RequestException $e) {
            Log::error('NYT API request failed', [
                'status' => $e->getCode(),
                'response' => $e->response?->json(),
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'ERROR',
                'message' => 'NYT API request failed',
                'error_code' => $e->getCode(),
            ], 502); // Bad Gateway
        } catch (\Exception $e) {
            Log::error('NYT API error', [
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'ERROR',
                'message' => 'An error occurred while processing your request',
            ], 500);
        }
    }
} 