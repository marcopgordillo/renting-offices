<?php

namespace Tests\Feature\V1;

use App\Models\Office;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeImageControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_uploads_an_image_and_stores_under_office()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->post(route('offices.images.store', $office), [
            'image'     => UploadedFile::fake()->image('image.jpg'),
        ]);

        $response->assertCreated();

        Storage::disk('public')->assertExists(
            $response->json('data.path')
        );
    }

    /** @test */
    public function it_cannot_upload_an_image_if_user_doesnt_owns_the_office()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $office = Office::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('offices.images.store', $office), [
            'image'     => UploadedFile::fake()->image('image.jpg'),
        ]);

        $response->assertForbidden();
    }
}
