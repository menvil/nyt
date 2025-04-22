<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;
use Log;

class NewYorkTimesService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected array $endpoints;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->apiKey = config('nyt.api_key');
        $this->baseUrl = config('nyt.base_url');
        $this->endpoints = config('nyt.endpoints');
        $this->cacheTtl = config('nyt.cache_ttl');
    }

    /**
     * Get best sellers history from the NYT API
     *
     * @param array $params
     * @return array
     * @throws ConnectionException|RequestException|Exception
     */
    public function getBestSellersHistory(array $params = []): array
    {
        if (isset($params['isbn']) && is_array($params['isbn'])) {
            $params['isbn'] = implode(';', $params['isbn']);
        }

        $cacheKey = 'nyt_bestsellers_history:' . md5(serialize($params));
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
            try {
                $response = $this->client()
                    ->get($this->baseUrl . $this->endpoints['best_sellers_history'], array_merge(['api-key' => $this->apiKey], $params));
                $response->throwIf($response->failed());
                
                return $response->json();
            } catch (ConnectionException $e) {
                Log::error('ConnectionException during request to NYT API', [
                    'status' => $e->getCode(),
                    'response' => $e?->response?->json(),
                    'exception' => $e->getMessage(),
                ]);
                
                throw new Exception('Failed to connect to the New York Times API.');
            } catch (RequestException $e) {
                
                Log::error('RequestException during request to NYT API', [
                    'status' => $e->getCode(),
                    'response' => $e?->response?->json(),
                    'exception' => $e->getMessage(),
                ]);

                throw new Exception('New York Times API request failed: ' . $e->getMessage());
            } catch (Exception $e) {
                Log::error('Exception during request to NYT API', [
                    'status' => $e->getCode(),
                    'response' => $e?->response?->json(),
                    'exception' => $e->getMessage(),
                ]);

                throw new Exception('An unexpected error occurred: ' . $e->getMessage());
            }
        });
    }

    /**
     * Create an HTTP client instance with default configuration.
     */
    protected function client(): PendingRequest
    {
        return Http::timeout(10)
            ->retry(3, 100);
    }
} 