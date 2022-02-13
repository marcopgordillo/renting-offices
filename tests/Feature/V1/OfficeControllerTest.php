<?php

namespace Tests\Feature\V1;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class OfficeControllerTest extends TestCase
{
    use RefreshDatabase;

    const NUMBER_OF_OFFICES = 3;

    public function setUp(): void
    {
        parent::setUp();
        Office::factory(self::NUMBER_OF_OFFICES)->create();
    }
    /**
     * @test
     */
    public function it_get_offices()
    {
        $response = $this->getJson('/api/v1/offices');

        // $this->assertCount(self::NUMBER_OF_OFFICES, $response->json('data'));
        // $this->assertNotNull($response->json('data')[0]['id']);

        $response->assertOk()
                ->assertJsonCount(self::NUMBER_OF_OFFICES, 'data')
                ->assertJsonPath('data.0.id', 3)
                ->assertJson(fn (AssertableJson $json) =>
                    $json->has('data', self::NUMBER_OF_OFFICES, fn ($json) =>
                        $json->where('id', 3)
                            ->etc()
                )
        );
    }
}
