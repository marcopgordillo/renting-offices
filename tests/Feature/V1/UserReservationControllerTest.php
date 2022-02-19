<?php

namespace Tests\Feature\V1;

use App\Enums\ReservationStatus;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserReservationControllerTest extends TestCase
{
    use LazilyRefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_lists_reservation_that_belong_to_the_user()
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->for($user)->create();
        $image = $reservation->office->images()->create([
            'path'  => 'office_image.jpg',
        ]);
        $reservation->office()->update(['featured_image_id' => $image->id]);
        Reservation::factory(2)->for($user)->create();
        Reservation::factory(3)->create();

        $this->actingAs($user);

        $response = $this->getJson(route('reservations.index'));

        $response->assertOk()
                ->assertJsonStructure(['data', 'meta', 'links'])
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure(['data' => ['*' => ['id', 'office']]])
                ->assertJsonPath('data.0.office.featured_image.id', $image->id);
    }

    /** @test */
    public function it_lists_reservation_filtered_by_date_range()
    {
        $user = User::factory()->create();

        $fromDate = '2021-03-03';
        $toDate = '2021-04-03';

        // Whithin the range date
        $reservations = Reservation::factory()->for($user)->createMany([
            [
                'start_date'    => '2021-03-01',
                'end_date'      => '2021-03-15',
            ],
            [
                'start_date'    => '2021-03-25',
                'end_date'      => '2021-04-15',
            ],
            [
                'start_date'    => '2021-03-01',
                'end_date'      => '2021-04-15',
            ],
            [   // Outside the date range
                'start_date'    => '2021-02-25',
                'end_date'      => '2021-03-02',
            ],
            [
                'start_date'    => '2021-04-25',
                'end_date'      => '2021-04-29',
            ],
        ]);

        Reservation::factory()->create([
            'start_date'    => '2021-03-25',
            'end_date'      => '2021-04-15',
        ]);

        $this->actingAs($user);

        $response = $this->getJson(route('reservations.index', [
            'from_date'     => $fromDate,
            'to_date'       => $toDate,
        ]));

        $response->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data.0', fn ($json) =>
                            $json
                                ->where('id', $reservations[0]->id)
                                ->etc()
                        )
                        ->has('data.1', fn ($json) =>
                            $json
                                ->where('id', $reservations[1]->id)
                                ->etc()
                        )
                        ->has('data.2', fn ($json) =>
                            $json
                                ->where('id', $reservations[2]->id)
                                ->etc()
                        )
                );
    }

    /** @test */
    public function it_lists_reservation_filtered_by_status()
    {
        $user = User::factory()->create();

        $reservation = Reservation::factory()->for($user)->create();
        Reservation::factory()->for($user)->cancelled()->create();

        $this->actingAs($user);

        $response = $this->getJson(route('reservations.index', [
            'status'     => ReservationStatus::ACTIVE->value,
        ]));

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 1)
                        ->has('data.0', fn ($json) =>
                            $json
                                ->where('id', $reservation->id)
                                ->where('status', ReservationStatus::ACTIVE->value)
                                ->etc()
                        )
                );
    }

    /** @test */
    public function it_lists_reservation_filtered_by_office()
    {
        $user = User::factory()->create();

        $office = Office::factory()->create();

        $reservation = Reservation::factory()->for($user)->for($office)->create();
        Reservation::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->getJson(route('reservations.index', [
            'office_id'     => $office->id,
        ]));

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 1)
                        ->has('data.0', fn ($json) =>
                            $json
                                ->where('id', $reservation->id)
                                ->where('office.id', $office->id)
                                ->etc()
                        )
                );
    }

    /** @test */
    public function it_lists_reservation_owned_by_host()
    {
        $user = User::factory()->create();

        $office = Office::factory()->for($user)->create();

        $reservation = Reservation::factory()->for($user)->for($office)->create();
        Reservation::factory()->for($user)->create();
        Reservation::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson(route('host.reservations.index', [
            'office_id'     => $office->id,
        ]));

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 1)
                        ->has('data.0', fn ($json) =>
                            $json
                                ->where('id', $reservation->id)
                                ->where('office.id', $office->id)
                                ->etc()
                        )
                );
    }

    /** @test */
    public function it_makes_reservations()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create([
            'price_per_day'     => 1_000,
            'monthly_discount'  => 10,
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => now()->addDays(1),
            'end_date'      => now()->addDays(40),
        ]);

        $response->assertCreated()
                ->assertJsonPath('data.price', 36000)
                ->assertJsonPath('data.user.id', $user->id)
                ->assertJsonPath('data.office.id', $office->id)
                ->assertJsonPath('data.status', ReservationStatus::ACTIVE->value);
    }

    /** @test */
    public function it_cannot_makes_reservations_on_not_existing_office()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => 10000,
            'start_date'    => now()->addDays(1),
            'end_date'      => now()->addDays(40),
        ]);

        $response->assertUnprocessable()
                ->assertInvalid('office_id')
                ->assertJsonValidationErrors(['office_id' => 'Invalid office_id']);
    }

    /** @test */
    public function it_cannot_makes_reservations_on_office_that_belongs_to_the_user()
    {
        $user = User::factory()->create();

        $office = Office::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => now()->addDays(1),
            'end_date'      => now()->addDays(40),
        ]);

        $response->assertUnprocessable()
                ->assertInvalid('office_id')
                ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation in your own office']);
    }

    /** @test */
    public function it_cannot_makes_reservations_less_than_2_days()
    {
        $user = User::factory()->create();

        $office = Office::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => now()->addDays(1),
            'end_date'      => now()->addDays(1),
        ]);

        $response->assertUnprocessable()
                ->assertInvalid('end_date')
                ->assertJsonValidationErrors(['end_date' => 'The end date must be a date after start date.']);
    }

    /** @test */
    public function it_makes_reservations_for_2_days()
    {
        $user = User::factory()->create();

        $office = Office::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => now()->addDays(1),
            'end_date'      => now()->addDays(2),
        ]);

        $response->assertCreated();
    }

    /** @test */
    public function it_cannot_makes_reservations_thats_conflicting()
    {
        $user = User::factory()->create();

        $fromDate = now()->addDay();
        $toDate = now()->addDays(15);

        $office = Office::factory()->create();

        Reservation::factory()->for($office)->create([
            'start_date'    => now()->addDays(2),
            'end_date'    => $toDate,
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => $fromDate,
            'end_date'      => $toDate,
        ]);

        $response->assertUnprocessable()
                ->assertInvalid('start_date')
                ->assertJsonValidationErrors(['start_date' => 'You cannot make a reservation during this time.']);
    }
}
