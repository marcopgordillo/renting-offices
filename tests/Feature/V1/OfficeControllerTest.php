<?php

namespace Tests\Feature\V1;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use App\Enums\ApprovalStatus;
use App\Enums\ReservationStatus;
use App\Models\Image;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
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

        Office::factory()
                    ->hidden()
                    ->create();
        Office::factory()
                    ->pending()
                    ->create();
        Office::factory()
                    ->hidden()
                    ->pending()
                    ->create();

        $response = $this->getJson('/api/v1/offices');

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', OfficeSeeder::NR_OFFICES_NO_HIDDEN_NO_PENDING, fn ($json) =>
                            $json->whereType('approval_status', 'integer')
                                ->where('approval_status', ApprovalStatus::APPROVED->value)
                                ->where('hidden', false)
                                ->etc()
                        )
                    );
    }

    /**
     * @test
     */
    public function it_filters_by_host_id()
    {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        $response = $this->getJson("/api/v1/offices?host_id={$host->id}");

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 1, fn ($json) =>
                            $json
                                ->whereAllType([
                                    'id' => 'integer',
                                    'user_id' => 'integer',
                                ])
                                ->where('id', $office->id)
                                ->where('user_id', $host->id)
                                ->etc()
                        )
                    );
    }

    /**
     * @test
     */
    public function it_filters_by_user_id()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();
        Reservation::factory()->create();
        $reservation = Reservation::factory()->for($office)->for($user)->create();

        $response = $this->getJson("/api/v1/offices?user_id={$user->id}");

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 1, fn ($json) =>
                            $json
                                ->whereAllType([
                                    'id' => 'integer',
                                    'user_id' => 'integer',
                                ])
                                ->where('id', $office->id)
                                ->where('user_id', $user->id)
                                // ->where('reservations.0.id', $reservation->id)
                                ->has('reservations', 1, fn ($json) =>
                                    $json->where('id', $reservation->id)
                                        ->etc()
                                )
                                ->etc()
                        )
                    );
    }

    /**
     * @test
     */
    public function it_includes_images_tags_user_and_reservations()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $office = Office::factory()->for($user)->has(Image::factory())->create();

        $office->tags()->attach($tag);
        // $office->images()->create(['path' => 'image.jpg']);

        $reservation = Reservation::factory()->for($office)->for($user)->create();

        $response = $this->getJson("/api/v1/offices");

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 4, fn ($json) =>
                            $json
                                ->whereAllType([
                                    'id'        => 'integer',
                                    'user_id'   => 'integer',
                                    'tags'      => 'array',
                                    'reservations'  => 'array',
                                    'images'    => 'array',
                                    'user'    => 'array',
                                ])
                                ->where('id', $office->id)
                                ->where('user_id', $user->id)
                                // ->where('reservations.0.id', $reservation->id)
                                ->where('user.id', $user->id)
                                ->has('reservations', 1, fn ($json) =>
                                    $json->where('id', $reservation->id)
                                        ->etc()
                                )
                                ->has('images', 1, fn ($json) =>
                                    $json->where('id', $office->images[0]->id)
                                        ->etc()
                                )
                                ->has('tags', 1, fn ($json) =>
                                    $json->where('id', $tag->id)
                                        ->etc()
                                )
                                ->etc()
                        )
                    );
    }

    /**
     * @test
     */
    public function it_returns_the_number_of_active_reservations()
    {
        Office::factory()
                    ->hasReservations(1, [
                        'status'    => ReservationStatus::ACTIVE,
                    ])
                    ->hasReservations(1, [
                        'status'    => ReservationStatus::CANCELLED,
                    ])
                    // ->has(
                    //     Reservation::factory()
                    //         ->state(fn (array $attributes) => ['status' => ReservationStatus::ACTIVE])
                    // )
                    // ->has(
                    //     Reservation::factory()
                    //         ->state(fn (array $attributes) => ['status' => ReservationStatus::CANCELLED])
                    // )
                    ->create();

        $response = $this->getJson("/api/v1/offices");

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 4, fn ($json) =>
                            $json
                                ->where('reservations_count', 1)
                                ->has('reservations', 2, fn ($json) =>
                                    $json->where('status', ReservationStatus::ACTIVE->value)
                                        ->etc()
                                )
                                ->etc()
                        )
                    );
    }
}
