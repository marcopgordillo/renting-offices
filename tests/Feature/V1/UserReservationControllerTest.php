<?php

namespace Tests\Feature\V1;

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
        $reservation1 = Reservation::factory()->for($user)->create([
            'start_date'    => '2021-03-01',
            'end_date'      => '2021-03-15',
        ]);

        $reservation2 = Reservation::factory()->for($user)->create([
            'start_date'    => '2021-03-25',
            'end_date'      => '2021-04-15',
        ]);

        Reservation::factory()->create([
            'start_date'    => '2021-03-25',
            'end_date'      => '2021-04-15',
        ]);

        // Outside the date range
        Reservation::factory()->for($user)->create([
            'start_date'    => '2021-02-25',
            'end_date'      => '2021-03-02',
        ]);

        Reservation::factory()->for($user)->create([
            'start_date'    => '2021-04-25',
            'end_date'      => '2021-04-29',
        ]);

        $this->actingAs($user);

        $response = $this->getJson(route('reservations.index', [
            'from_date'     => $fromDate,
            'to_date'       => $toDate,
        ]));

        $response->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJson(fn (AssertableJson $json) =>
                    $json->hasAll('data', 'meta', 'links')
                        ->has('data', 2)
                        ->has('data.0', fn ($json) =>
                            $json
                                ->where('id', $reservation1->id)
                                ->etc()
                        )
                        ->has('data.1', fn ($json) =>
                            $json
                                ->where('id', $reservation2->id)
                                ->etc()
                        )
                );

        $this->assertEquals([$reservation1->id, $reservation2->id],
            collect($response->json('data'))->pluck('id')->toArray());
    }
}
