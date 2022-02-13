<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    const NR_OFFICES_NO_HIDDEN_NO_PENDING = 3;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Office::factory(self::NR_OFFICES_NO_HIDDEN_NO_PENDING)->create();
    }
}
