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
use Illuminate\Http\Client\ConnectionException;
use Exception;

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
    
    /**
     * @return \Mockery\MockInterface|\App\Services\NewYorkTimesService
     */
    protected function prepareMock()
    {
        // Mock the client() method of NewYorkTimesService directly, enabling protected mocking
        $service = Mockery::mock(NewYorkTimesService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Use reflection to set the private properties
        $reflection = new \ReflectionClass($service);
        
        // Set apiKey
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $apiKeyProperty->setValue($service, 'dummy_api_key');

        // Set baseUrl
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $baseUrlProperty->setValue($service, 'some_base_url');

        // Set cacheTtl
        $cacheTtlProperty = $reflection->getProperty('cacheTtl');
        $cacheTtlProperty->setAccessible(true);
        $cacheTtlProperty->setValue($service, 0);

        // Set endpoints
        $endpointsProperty = $reflection->getProperty('endpoints');
        $endpointsProperty->setAccessible(true);
        $endpointsProperty->setValue($service, ['best_sellers_history' => 'test']);

        return $service;
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

        $service = $this->prepareMock();
        
        $service->shouldReceive('client')
            ->andReturn($pendingRequestMock);

        $this->expectException(\Exception::class);

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
        $cacheKey = 'nyt_bestsellers_history:' . md5(serialize(['author' => 'Test']));

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

    #[Test]
    public function it_throws_exception_when_connection_exception_happened()
    {
        $service = $this->prepareMock();
        $service->shouldReceive('client')->andThrow(ConnectionException::class);
        $this->expectException(Exception::class);
        $service->getBestSellersHistory([]);

    }

    #[Test]
    public function it_throws_exception_when_general_exception_happened()
    {
        $service = $this->prepareMock();
        $service->shouldReceive('client')->andThrow(Exception::class);
        $this->expectException(Exception::class);
        $service->getBestSellersHistory(['author' => 'test']);
    }
}