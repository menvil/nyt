<?php

namespace Tests\Unit\Services;

use App\Services\NewYorkTimesService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\PendingRequest;
use Mockery;
use Closure;

class NewYorkTimesServiceTest extends TestCase
{
    private NewYorkTimesService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        Cache::clear();

        $this->service = new NewYorkTimesService();
        
        // Prevent real API calls
        Http::preventStrayRequests();
        
        // Always fake HTTP
        Http::fake([
            '*' => Http::response(
                require base_path('tests/Fixtures/nyt_bestsellers_response.php'), 
                200
            ),
        ]);
    }
    

    #[Test]
    public function it_can_get_best_sellers_history()
    {
        $result = $this->service->getBestSellersHistory();
        
        $this->assertEquals('OK', $result['status']);
        $this->assertIsArray($result['results']);
        $this->assertCount(2, $result['results']);
        
        // Check the mock was called correctly - use a less strict pattern
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'lists/best-sellers/history.json');
        });
    }
    
    #[Test]
    public function it_formats_isbn_array_as_semicolon_separated_string()
    {
        $this->service->getBestSellersHistory([
            'isbn' => ['1234567890', '0987654321']
        ]);
        
        Http::assertSent(function (Request $request) {
            return $request['isbn'] === '1234567890;0987654321';
        });
    }

    #[Test]
    public function it_handles_api_error_responses()
    {
        // Create a mock exception
        $response = new Response(
            new GuzzleResponse(401, [], json_encode(['fault' => ['faultstring' => 'API key invalid']]))
        );
        $exception = new RequestException($response);

        // Mock the PendingRequest
        $pendingRequestMock = Mockery::mock(PendingRequest::class);
        $pendingRequestMock->shouldReceive('get')
            ->andThrow($exception);

        // Mock the client() method of NewYorkTimesService directly, enabling protected mocking
        $service = Mockery::mock(NewYorkTimesService::class)->makePartial()->shouldAllowMockingProtectedMethods();

        // Use reflection to set the private $apiKey property
        $reflection = new \ReflectionClass($service);
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $apiKeyProperty->setValue($service, 'dummy_api_key');

        $reflection = new \ReflectionClass($service);
        $apiKeyProperty = $reflection->getProperty('baseUrl');
        $apiKeyProperty->setAccessible(true);
        $apiKeyProperty->setValue($service, 'some_base_url');

        $apiKeyProperty = $reflection->getProperty('cacheTtl');
        $apiKeyProperty->setAccessible(true);
        $apiKeyProperty->setValue($service, 0);

        $apiKeyProperty = $reflection->getProperty('endpoints');
        $apiKeyProperty->setAccessible(true);
        $apiKeyProperty->setValue($service, ['best_sellers_history' => 'test']);

        
        $service->shouldReceive('client')
            ->andReturn($pendingRequestMock);

        // We expect this exception to be thrown
        $this->expectException(\Exception::class);

        // Call the service method
        $service->getBestSellersHistory([]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_passes_query_parameters_correctly()
    {
        $this->service->getBestSellersHistory([
            'author' => 'George Orwell',
            'title' => '1984', 
            'offset' => 20,
        ]);
        
        Http::assertSent(function (Request $request) {
            return $request['author'] === 'George Orwell' &&
                   $request['title'] === '1984' &&
                   $request['offset'] === 20;
        });
    }

    #[Test]
    public function it_uses_cache_for_subsequent_requests()
    {
        // Create the cache key that will be used
        $cacheKey = 'nyt_bestsellers_history:' . md5(serialize(['author' => 'Test']));

        // Create a mock response
        Cache::shouldReceive('remember')
            ->twice()
            ->with($cacheKey, 3600, Closure::class)
            ->andReturn(['some_key'=>'some_result']);

        $result1 = $this->service->getBestSellersHistory(['author' => 'Test']);
        $result2 = $this->service->getBestSellersHistory(['author' => 'Test']);

        // Both responses should be identical
        $this->assertEquals(
            $result1,
            $result2
        );
    }

    #[Test]
    public function it_uses_different_cache_keys_for_different_queries()
    {
        $cacheKeyA = 'nyt_bestsellers_history:' . md5(serialize(['author' => 'Author A']));
        $cacheKeyB = 'nyt_bestsellers_history:' . md5(serialize(['author' => 'Author B']));

        $mockResponse1 = ['status' => 'OK', 'num_results' => 1, 'results' => [['title' => 'Book A']]];
        $mockResponse2 = ['status' => 'OK', 'num_results' => 1, 'results' => [['title' => 'Book B']]];

         // Create a mock response
         Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKeyA, 3600, Closure::class)
            ->andReturn($mockResponse1);

         Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKeyB, 3600, Closure::class)
            ->andReturn($mockResponse2);

        $resultA = $this->service->getBestSellersHistory(['author' => 'Author A']);
        $resultB = $this->service->getBestSellersHistory(['author' => 'Author B']);

        $this->assertNotEquals($resultA, $resultB);
        $this->assertEquals($resultA, $mockResponse1);
        $this->assertEquals($resultB, $mockResponse2);
    }
} 