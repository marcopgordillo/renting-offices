<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\ReservationStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'           => User::factory(),
            'office_id'         => Office::factory(),
            'price'             => $this->faker->numberBetween(10_000, 20_000),
            'status'            => ReservationStatus::ACTIVE,
            'start_date'        => now()->addDay(1)->format('Y-m-d'),
            'end_date'          => now()->addDay(5)->format('Y-m-d'),
        ];
    }

    public function cancelled()
    {
        return $this->state(fn (array $attributes) =>
            [
                'status'    => ReservationStatus::CANCELLED,
            ]
        );
    }
}
