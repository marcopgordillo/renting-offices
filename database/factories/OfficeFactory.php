<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\ApprovalStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Office::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'           => User::factory(),
            'title'             => $this->faker->sentence,
            'description'       => $this->faker->paragraph,
            'lat'               => $this->faker->latitude,
            'lng'               => $this->faker->longitude,
            'address_line1'     => $this->faker->address,
            'approval_status'   => ApprovalStatus::APPROVED,
            'hidden'            => false,
            'price_per_day'     => $this->faker->numberBetween(1_000, 2_000),
            'monthly_discount'  => 0,
        ];
    }
}
