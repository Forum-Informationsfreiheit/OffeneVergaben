<?php

namespace Tests\Feature\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * unisharp/laravel-filemanager under /admin/lfm (config/lfm.php), used for the images of posts and pages.
 * Uploads go to the "lfm" disk, i.e. public/uploads: images/<user id>/ and images/shares/.
 */
class FileManagerTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('lfm');
    }

    public function testEditorOpensFileManager()
    {
        $response = $this->actingAs($this->createEditor())->get('/admin/lfm?type=Images');

        $response->assertOk();
        $response->assertViewIs('laravel-filemanager::index');
    }

    public function testUploadsImageToPrivateFolderWithThumbnail()
    {
        $editor = $this->createEditor();

        $response = $this->actingAs($editor)->post('/admin/lfm/upload', [
            'type'        => 'Images',
            'working_dir' => '/'.$editor->id,
            'upload'      => [UploadedFile::fake()->image('foto.jpg', 400, 300)],
        ]);

        $response->assertOk();
        $response->assertExactJson(['OK']);
        Storage::disk('lfm')->assertExists('images/'.$editor->id.'/foto.jpg');
        Storage::disk('lfm')->assertExists('images/'.$editor->id.'/'.config('lfm.thumb_folder_name').'/foto.jpg');
    }

    /**
     * The URLs are those of 1.x, so the images of existing posts and pages keep working.
     */
    public function testDiskServesPublicUploads()
    {
        // the configured disk, Storage::fake() replaces root and url
        $disk = Storage::build(config('filesystems.disks.lfm'));

        $this->assertSame(public_path('uploads/images/4/foto.jpg'), $disk->path('images/4/foto.jpg'));
        $this->assertSame(config('app.url').'/uploads/images/4/foto.jpg', $disk->url('images/4/foto.jpg'));
    }

    /**
     * 1.x accepted any file (should_validate_mime was off): CVE-2021-23814, CVE-2024-21546.
     */
    public function testRejectsPhpAndSvgUploads()
    {
        $editor = $this->createEditor();

        foreach ([
            UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('bild.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ] as $file) {
            $response = $this->actingAs($editor)->post('/admin/lfm/upload', [
                'type'        => 'Images',
                'working_dir' => '/'.$editor->id,
                'upload'      => [$file],
            ]);

            $response->assertOk();
            $this->assertNotSame(['OK'], $response->json(), $file->getClientOriginalName().' was accepted');
        }

        $this->assertSame([], Storage::disk('lfm')->allFiles());
    }
}
