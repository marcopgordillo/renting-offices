<?php

namespace Tests\Feature\V1;

use App\Models\Image;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase, WithFaker;

    /** @test */
    public function it_uploads_an_image_and_stores_under_office()
    {
        Storage::fake();

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->post(route('offices.images.store', $office), [
            'image'     => UploadedFile::fake()->image('image.jpg'),
        ]);

        $response->assertCreated();

        Storage::assertExists(
            $response->json('data.path')
        );
    }

    /** @test */
    public function it_cannot_upload_an_image_if_user_doesnt_owns_the_office()
    {
        Storage::fake();

        $user = User::factory()->create();
        $office = Office::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('offices.images.store', $office), [
            'image'     => UploadedFile::fake()->image('image.jpg'),
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function it_doesnt_delete_an_image_if_is_the_only()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $image = $office->images()->create([
            'path'  => 'image.jpg'
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson(route('offices.images.destroy', [$office, $image]));

        $response->assertUnprocessable()
                ->assertInvalid('image')
                ->assertJsonValidationErrors(['image' => 'Cannot delete the only image.']);

        $this->assertModelExists($image);
    }

    /** @test */
    public function it_doesnt_delete_the_featured_image()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->images()->create([
            'path'  => 'image.jpg'
        ]);

        $image = $office->images()->create([
            'path'  => 'image.jpg'
        ]);

        $office->update([
            'featured_image_id'     => $image->id,
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson(route('offices.images.destroy', [$office, $image]));

        $response->assertUnprocessable()
                ->assertInvalid('image')
                ->assertJsonValidationErrors(['image' => 'Cannot delete the featured image.']);

        $this->assertModelExists($image);

    }

    /** @test */
    public function it_doesnt_delete_an_image_if_owns_other_office()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $image = Image::factory()->for(Office::factory(), 'imageable')->create();

        $this->actingAs($user);

        $response = $this->deleteJson(route('offices.images.destroy', [$office, $image]));

        $response->assertNotFound();

        $this->assertModelExists($image);

    }

    /** @test */
    public function it_deletes_an_image()
    {
        Storage::put('/office_image.jpg', 'empty');

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->images()->create([
            'path'  => 'image.jpg'
        ]);

        $image = $office->images()->create([
            'path'  => 'office_image.jpg'
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson(route('offices.images.destroy', [$office, $image]));

        $response->assertNoContent();

        Storage::assertMissing(
            $image->path
        );

        $this->assertModelMissing($image);
    }
}
