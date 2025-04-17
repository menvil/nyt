<?php

namespace Tests\Feature\Api\V1;

use App\Http\Controllers\Api\V1\BestSellersController;
use App\Http\Requests\BestSellersHistoryRequest;
use App\Services\NewYorkTimesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Client\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Closure;

class BestSellersApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
        // Create a consistent mock response
        $mockResponseData = require base_path('tests/Fixtures/nyt_bestsellers_response.php');
        
        // Mock the NYT service to return our fixture data
        $this->mock(NewYorkTimesService::class)
            ->shouldReceive('getBestSellersHistory')
            ->withAnyArgs()
            ->andReturn($mockResponseData);
        
        // No HTTP requests allowed
        Http::preventStrayRequests();
    }

    #[Test]
    public function it_can_retrieve_best_sellers_history()
    {
        $response = $this->getJson('/api/v1/best-sellers/history');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'copyright',
                'num_results',
                'results'
            ]);
    }


    /**
     * Create a properly validated request for testing
     */
    protected function createValidatedRequest(array $parameters = []): BestSellersHistoryRequest
    {
        // Create a real request with parameters
        $request = Request::create('/api/best-sellers/history', 'GET', $parameters);
        
        // Create the form request
        $formRequest = BestSellersHistoryRequest::createFromBase($request);
        
        // Set the container to resolve dependencies 
        $formRequest->setContainer(app());
        
        // Simulate the validation process
        $formRequest->validateResolved();
        
        return $formRequest;
    }
    
    #[Test]
    public function it_can_test_controller_directly()
    {
        // Get an instance of the controller
        $controller = app()->make(BestSellersController::class);
        
        // Create a properly validated request
        $request = $this->createValidatedRequest();
        
        // Call the controller method directly
        $response = $controller->history($request);
        
        // Assert the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
    
    #[Test]
    public function it_passes_author_parameter_to_service()
    {
        // Mock with parameter checking
        $mock = $this->mock(NewYorkTimesService::class);
        $mock->shouldReceive('getBestSellersHistory')
            ->with(\Mockery::on(function ($params) {
                return isset($params['author']) && $params['author'] === 'Sophia';
            }))
            ->once()
            ->andReturn(require base_path('tests/Fixtures/nyt_bestsellers_response.php'));
            
        // Get controller
        $controller = app()->make(BestSellersController::class);
        
        // Create request with author parameter
        $request = $this->createValidatedRequest(['author' => 'Sophia']);
        
        // Call controller
        $controller->history($request);
    }
    
    #[Test]
    public function it_passes_title_parameter_to_service()
    {
        // Similar pattern for title parameter
        $mock = $this->mock(NewYorkTimesService::class);
        $mock->shouldReceive('getBestSellersHistory')
            ->with(\Mockery::on(function ($params) {
                return isset($params['title']) && $params['title'] === 'GIRLBOSS';
            }))
            ->once()
            ->andReturn(require base_path('tests/Fixtures/nyt_bestsellers_response.php'));
            
        // Get controller
        $controller = app()->make(BestSellersController::class);
        
        // Create request with title parameter - properly validated
        $request = $this->createValidatedRequest(['title' => 'GIRLBOSS']);
        
        // Call controller
        $controller->history($request);
    }
    
    #[Test]
    public function it_validates_offset_must_be_multiple_of_twenty()
    {
        $this->withExceptionHandling();
        
        // Create a request with invalid offset
        $data = ['offset' => 15];
        
        // Test the validation directly
        $request = new BestSellersHistoryRequest();
        $validator = app('validator')->make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('offset', $validator->errors()->toArray());
    }
    
    #[Test]
    public function it_handles_service_exceptions()
    {
        // Create a Guzzle response that represents the error
        $guzzleResponse = new GuzzleResponse(401, [], json_encode(['status' => 'ERROR', 'errors' => ['Invalid API key']]));

        // Create an Illuminate\Http\Client\Response from the Guzzle response
        $clientResponse = new Response($guzzleResponse);

        // Mock service to throw exception with the correct Response object
        $this->mock(NewYorkTimesService::class)
            ->shouldReceive('getBestSellersHistory')
            ->andThrow(new RequestException($clientResponse));

        // Get an instance of the controller
        $controller = app()->make(BestSellersController::class);
        
        // Create a properly validated request
        $request = $this->createValidatedRequest();
        
        // Call the controller method directly
        $response = $controller->history($request);

        // Assert error response
        $this->assertEquals(502, $response->getStatusCode());
        $this->assertStringContainsString('ERROR', $response->getContent());
    }

    #[Test]
    public function it_returns_validation_error_for_invalid_parameters()
    {
        $this->withExceptionHandling(); // Enable exception handling to see validation errors
        
        // Test invalid offset
        $response = $this->getJson('/api/v1/best-sellers/history?offset=15');
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offset'])
            ->assertJson([
                'message' => 'Invalid request parameters',
                'errors' => [
                    'offset' => ['The offset must be a multiple of 20.']
                ]
            ]);
        
        // Test invalid ISBN format
        $response = $this->getJson('/api/v1/best-sellers/history?isbn[]=invalid-isbn');
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn.0'])
            ->assertJson([
                'message' => 'Invalid request parameters',
                'errors' => [
                    'isbn.0' => ['Each ISBN must be a valid 10 or 13 digit number.']
                ]
            ]);
        
        // Test multiple validation errors
        $response = $this->getJson('/api/v1/best-sellers/history?offset=15&isbn[]=invalid');
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offset', 'isbn.0'])
            ->assertJson([
                'message' => 'Invalid request parameters',
                'errors' => [
                    'offset' => ['The offset must be a multiple of 20.'],
                    'isbn.0' => ['Each ISBN must be a valid 10 or 13 digit number.']
                ]
            ]);
    }
} 