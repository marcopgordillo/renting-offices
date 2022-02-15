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
use Illuminate\Http\Response;

class OfficeControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

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
        // $response = $this->getJson('/api/v1/offices');
        $response = $this->getJson(route('offices.index'));

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
        $response = $this->getJson(route('offices.index'));

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

        $response = $this->getJson(route('offices.index'));

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
    public function it_filters_by_user_id()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $response = $this->getJson(route('offices.index', [
            'user_id' => $user->id
        ]));

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 1, fn ($json) =>
                            $json
                                ->whereType('id', 'integer')
                                ->where('id', $office->id)
                                ->missingAll('user_id', 'created_at', 'updated_at', 'deleted_at')
                                ->etc()
                        )
                    );
    }

    /**
     * @test
     */
    public function it_filters_by_visitor_id()
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->for($visitor)->create();
        Reservation::factory()->create();
        $reservation = Reservation::factory()->for($office)->for($visitor)->create();

        $response = $this->getJson("/api/v1/offices?visitor_id={$visitor->id}");
        $response = $this->getJson(route('offices.index', [
            'visitor_id' => $visitor->id
        ]));

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 1, fn ($json) =>
                            $json
                                ->whereType('id', 'integer')
                                ->where('id', $office->id)
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
        $office = Office::factory()
                        ->for($user)
                        ->has(Image::factory())
                        ->create();

        $office->tags()->attach($tag);
        // $office->images()->create(['path' => 'image.jpg']);

        $reservation = Reservation::factory()->for($office)->for($user)->create();

        $response = $this->getJson(route('offices.index'));

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 4, fn ($json) =>
                            $json
                                ->whereAllType([
                                    'id'        => 'integer',
                                    'tags'      => 'array',
                                    'reservations'  => 'array',
                                    'images'    => 'array',
                                    'user'    => 'array',
                                ])
                                ->where('id', $office->id)
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
                    ->create();

        $response = $this->getJson(route('offices.index'));

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

    /**
     * @test
     */
    public function it_orders_by_distance_when_coordinates_are_provided()
    {
        // $response = $this->getJson("/api/v1/offices?lat=38.720661384644046&lng=-9.16044783453807");
        $response = $this->getJson(route('offices.index', [
            'lat'   => 38.720661384644046,
            'lng'   => -9.16044783453807,
        ]));

        $response->assertOk();
    }

    /**
     * @test
     */
    public function it_shows_the_office()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $office = Office::factory()->for($user)->has(Image::factory())->create();

        $office->tags()->attach($tag);
        // $office->images()->create(['path' => 'image.jpg']);

        $reservation = Reservation::factory()->for($office)->for($user)->create();
        Reservation::factory(2)->for($office)->for($user)->create();

        // $response = $this->getJson("/api/v1/offices/{$office->id}");
        $response = $this->getJson(route('offices.show', $office));

        $response->assertOk()
                ->assertJson(fn (AssertableJson $json) =>
                    $json
                        ->has('data', fn ($json) =>
                            $json
                                ->where('reservations_count', 3)
                                ->where('id', $office->id)
                                ->where('user.id', $user->id)
                                ->has('reservations', 3, fn ($json) =>
                                    $json->where('id', $reservation->id)
                                        ->etc()
                                )
                                ->has('images', 1, fn ($json) =>
                                    $json->where('id', $office->images[0]->id)
                                        ->etc()
                                )
                                ->etc()
                        )
                    );
    }

    /**
     * @test
     */
    public function it_should_create_an_office()
    {
        $user = User::factory()->createQuietly();
        $tags = Tag::factory(2)->create();

        $this->actingAs($user);

        $response = $this->postJson(route('offices.store'), [
            'title'             => $this->faker->sentence,
            'description'       => $this->faker->paragraph,
            'lat'               => $this->faker->latitude,
            'lng'               => $this->faker->longitude,
            'address_line1'     => $this->faker->address,
            'hidden'            => false,
            'price_per_day'     => $this->faker->numberBetween(1_000, 4_000),
            'monthly_discount'  => $this->faker->numberBetween(0, 50),
            'tags'              => $tags->pluck('id')->toArray(),
        ]);

        $response->assertCreated()
                ->assertJsonPath('data.approval_status', ApprovalStatus::PENDING->value)
                ->assertJsonPath('data.user.id', $user->id)
                ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseHas('offices', [
            'id'    => $response->json('data')['id'],
        ]);
    }

    /**
     * @test
     */
    public function it_doesnt_allow_create_office_if_scope_is_not_provided()
    {
        $user = User::factory()->createQuietly();

        $token = $user->createToken('test', []);

        $response = $this->postJson(route('offices.store'), [], [
            'Authorization'     => "Bearer {$token->plainTextToken}",
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function it_updates_an_office()
    {
        $TITLE_UPDATED = 'Title updated!';
        $user = User::factory()->createQuietly();
        $tags = Tag::factory(2)->create();
        $office = Office::factory()->for($user)->create();
        $office->tags()->attach($tags);

        $this->actingAs($user);

        $response = $this->putJson(route('offices.update', $office), [
            'title'             => $TITLE_UPDATED,
        ]);

        $response->assertOk()
                ->assertJsonPath('data.title', $TITLE_UPDATED)
                ->assertJsonCount(2, 'data.tags');

    }

    /**
     * @test
     */
    public function it_doesnt_update_an_office_that_doesnt_belong_to_user()
    {
        $TITLE_UPDATED = 'Title updated!';
        $user = User::factory()->createQuietly();
        $anotherUser = User::factory()->createQuietly();
        $office = Office::factory()->for($anotherUser)->create();

        $this->actingAs($user);

        $response = $this->putJson(route('offices.update', $office), [
            'title'             => $TITLE_UPDATED,
        ]);

        $response->assertForbidden();
    }
}
