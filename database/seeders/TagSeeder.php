<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Tag::factory()->create(['name' => 'has_ac']);
        Tag::factory()->create(['name' => 'has_private_bathroom']);
        Tag::factory()->create(['name' => 'has_coffee_machine']);
    }
}
