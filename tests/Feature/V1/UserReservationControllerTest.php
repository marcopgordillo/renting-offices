<?php

namespace Tests\Feature\V1;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserReservationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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
}
