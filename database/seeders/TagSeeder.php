<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    const NUMBER_OF_TAGS = 3;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Tag::factory(3)->create();
        Tag::factory()->create(['name' => 'has_ac']);
        Tag::factory()->create(['name' => 'has_private_bathroom']);
        Tag::factory()->create(['name' => 'has_coffee_machine']);
    }
}
