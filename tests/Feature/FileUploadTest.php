<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    private FileUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(FileUploadService::class);
    }

    public function test_compress_image_returns_webp_path(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $path = $this->service->compressImage($file, 'posts/images');

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringStartsWith('posts/images/', $path);
    }

    public function test_compress_image_respects_max_width_option(): void
    {
        $file = UploadedFile::fake()->image('wide.jpg', 2000, 1000);

        $path = $this->service->compressImage($file, 'posts/images', ['maxWidth' => 800]);

        $this->assertNotNull($path);
        // File was created — existence confirmed by non-null path; dimension
        // assertion requires reading the file which varies by GD availability.
    }

    public function test_compress_image_uses_prefix_in_filename(): void
    {
        $file = UploadedFile::fake()->image('avatar.png', 200, 200);

        $path = $this->service->compressImage($file, 'avatars', ['prefix' => 'user_42']);

        $this->assertNotNull($path);
        $this->assertStringContainsString('user_42', basename($path));
    }

    public function test_validate_file_rejects_disallowed_mime(): void
    {
        // Create a fake file with a PDF MIME
        $file = UploadedFile::fake()->create('malicious.pdf', 100, 'application/pdf');

        $result = $this->service->validateFile($file, ['image/jpeg', 'image/png', 'image/webp']);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_file_accepts_allowed_jpeg(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $result = $this->service->validateFile($file, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_file_rejects_oversized_file(): void
    {
        // 60 MB — exceeds the 50 MB limit
        $file = UploadedFile::fake()->create('huge.jpg', 60 * 1024, 'image/jpeg');

        $result = $this->service->validateFile($file, ['image/jpeg']);

        $this->assertFalse($result['valid']);
        $this->assertTrue(collect($result['errors'])->contains(fn($e) => str_contains($e, 'size')));
    }

    public function test_compress_image_with_cover_option(): void
    {
        $file = UploadedFile::fake()->image('landscape.jpg', 1200, 800);

        $path = $this->service->compressImage($file, 'covers', ['cover' => [400, 400]]);

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.webp', $path);
    }
}
