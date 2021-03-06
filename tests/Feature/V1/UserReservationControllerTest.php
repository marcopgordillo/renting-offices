<?php

namespace Tests\Feature\V1;

use App\Console\Commands\SendDueReservationsNotifications;
use App\Enums\ReservationStatus;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\HostReservationStarting;
use App\Notifications\NewUserReservation;
use App\Notifications\NewHostReservation;
use App\Notifications\UserReservationStarting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
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
            'start_date'    => now()->addDays(1)->toDateString(),
            'end_date'      => now()->addDays(40)->toDateString(),
        ]);

        $response->assertCreated()
                ->assertJsonPath('data.price', 36000)
                ->assertJsonPath('data.user.id', $user->id)
                ->assertJsonPath('data.office.id', $office->id)
                ->assertJsonPath('data.status', ReservationStatus::ACTIVE->value);
    }

    /** @test */
    public function it_send_notifications_on_new_reservations()
    {
        Notification::fake();

        $user = User::factory()->create();
        $office = Office::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => now()->addDays(1)->toDateString(),
            'end_date'      => now()->addDays(40)->toDateString(),
        ]);

        Notification::assertSentTo($user, NewUserReservation::class);
        Notification::assertSentTo($office->user, NewHostReservation::class);

        $response->assertCreated();
    }

    /** @test */
    public function it_send_notifications_on_start_date()
    {
        Notification::fake();

        $user = User::factory()->create();
        $office = Office::factory()->create();
        $reservation = Reservation::factory()->for($user)->for($office)->create([
            'start_date'    => today()->toDateString(),
        ]);

        Artisan::call(SendDueReservationsNotifications::class);

        Notification::assertSentTo($user, UserReservationStarting::class);
        Notification::assertSentTo($office->user, HostReservationStarting::class);
    }

    /** @test */
    public function it_cannot_makes_reservations_on_not_existing_office()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => 10000,
            'start_date'    => now()->addDays(1)->toDateString(),
            'end_date'      => now()->addDays(40)->toDateString(),
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
            'start_date'    => now()->addDays(1)->toDateString(),
            'end_date'      => now()->addDays(40)->toDateString(),
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
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
        ]);

        $response->assertUnprocessable()
                ->assertInvalid('end_date')
                ->assertJsonValidationErrors(['end_date' => 'The end date must be a date after start date.']);
    }

    /** @test */
    public function it_cannot_makes_reservations_on_same_day()
    {
        $user = User::factory()->create();

        $office = Office::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => now()->toDateString(),
            'end_date'      => now()->addDays(2)->toDateString(),
        ]);

        $response->assertUnprocessable()
                ->assertInvalid('start_date')
                ->assertJsonValidationErrors(['start_date' => 'The start date must be a date after today.']);
    }

    /** @test */
    public function it_makes_reservations_for_2_days()
    {
        $user = User::factory()->create();

        $office = Office::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => now()->addDays(1)->toDateString(),
            'end_date'      => now()->addDays(2)->toDateString(),
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
            'start_date'    => now()->addDays(2)->toDateString(),
            'end_date'    => $toDate->toDateString(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.store'), [
            'office_id'     => $office->id,
            'start_date'    => $fromDate->toDateString(),
            'end_date'      => $toDate->toDateString(),
        ]);

        $response->assertUnprocessable()
                ->assertInvalid('start_date')
                ->assertJsonValidationErrors(['start_date' => 'You cannot make a reservation during this time.']);
    }

    /** @test */
    public function it_cannot_makes_reservations_on_office_that_is_pending_or_hidden()
    {
        $user = User::factory()->create();

        $office1 = Office::factory()->hidden()->create();
        $office2 = Office::factory()->pending()->create();

        $this->actingAs($user);

        $response1 = $this->postJson(route('reservations.store'), [
            'office_id'     => $office1->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDays(40)->toDateString(),
        ]);

        $response2 = $this->postJson(route('reservations.store'), [
            'office_id'     => $office2->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDays(40)->toDateString(),
        ]);

        $response1->assertUnprocessable()
                ->assertInvalid('office_id')
                ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on a hidden office']);

        $response2->assertUnprocessable()
                ->assertInvalid('office_id')
                ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on a hidden office']);
    }

    /** @test */
    public function it_cancel_a_reservation()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        $reservation = Reservation::factory()->for($user)->for($office)->create();

        $this->actingAs($user);

        $response = $this->deleteJson(route('reservations.cancel', $reservation));

        $response->assertNoContent();

        $this->assertDatabaseHas('reservations', [
            'id'        => $reservation->id,
            'status'    => ReservationStatus::CANCELLED,
        ]);
    }

    /** @test */
    public function it_cannot_cancel_a_reservation_that_owns_other_user()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        $reservation = Reservation::factory()->for($office)->create();

        $this->actingAs($user);

        $response = $this->deleteJson(route('reservations.cancel', $reservation));

        $response->assertUnprocessable()
                ->assertInvalid('reservation')
                ->assertJsonValidationErrors(['reservation' => 'You cannot cancel this reservation']);

        $this->assertDatabaseHas('reservations', [
            'id'        => $reservation->id,
            'status'    => ReservationStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function it_cannot_cancel_a_reservation_that_doesnt_has_status_active()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        $reservation = Reservation::factory()->for($office)->for($user)->cancelled()->create();

        $this->actingAs($user);

        $response = $this->deleteJson(route('reservations.cancel', $reservation));

        $response->assertUnprocessable()
                ->assertInvalid('reservation')
                ->assertJsonValidationErrors(['reservation' => 'You cannot cancel this reservation']);

        $this->assertDatabaseHas('reservations', [
            'id'        => $reservation->id,
            'status'    => ReservationStatus::CANCELLED,
        ]);
    }

    /** @test */
    public function it_cannot_cancel_a_reservation_that_has_a_start_date_before_than_today()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        $reservation = Reservation::factory()->for($office)->for($user)->create([
            'start_date'    => today()->subDay(),
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson(route('reservations.cancel', $reservation));

        $response->assertUnprocessable()
                ->assertInvalid('reservation')
                ->assertJsonValidationErrors(['reservation' => 'You cannot cancel this reservation']);

        $this->assertDatabaseHas('reservations', [
            'id'        => $reservation->id,
            'status'    => ReservationStatus::ACTIVE,
        ]);
    }
}
