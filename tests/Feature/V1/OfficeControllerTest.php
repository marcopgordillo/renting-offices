<?php

namespace Tests\Feature\V1;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use App\Enums\ApprovalStatus;
use Database\Seeders\OfficeSeeder;

use function PHPUnit\Framework\assertNotNull;

class OfficeControllerTest extends TestCase
{
    use RefreshDatabase;

    // protected $seeder = OfficeSeeder::class;

    public function setUp(): void
    {
        parent::setUp();
        // $this->seed(OfficeSeeder::class);
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
                ->assertJsonCount(OfficeSeeder::NR_OFFICES_NO_HIDDEN_NO_PENDING, 'data')
                ->assertJsonPath('data.0.id', 3);
    }

    /**
     * @test
     */
    public function it_get_offices_paginated()
    {
        $response = $this->getJson('/api/v1/offices');

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', OfficeSeeder::NR_OFFICES_NO_HIDDEN_NO_PENDING, fn ($json) =>
                            $json->whereType('id', 'integer')
                                ->where('id', 3)
                                ->etc()
                        )
                    );
    }

    /**
     * @test
     */
    public function it_get_offices_with_approved_status_and_no_hidden()
    {
        $response = $this->getJson('/api/v1/offices');

        $response->assertOk()
                // ->dump()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', OfficeSeeder::NR_OFFICES_NO_HIDDEN_NO_PENDING, fn ($json) =>
                            $json->whereType('approval_status', 'integer')
                                ->where('approval_status', 2)
                                ->where('hidden', false)
                                ->etc()
                        )
                    );
    }
}
