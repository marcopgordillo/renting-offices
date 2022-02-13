<?php

namespace Tests\Feature\V1;

use App\Models\Tag;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // Tag::factory(self::NUMBER_OF_TAGS)->create();
    }
    /**
     * @test
     */
    public function it_get_tags()
    {
        $response = $this->get('/api/v1/tags');

        $response->assertOk();

        $this->assertCount(TagSeeder::NUMBER_OF_TAGS, $response->json('data'));
        $this->assertNotNull($response->json('data')[0]['id']);
    }
}
