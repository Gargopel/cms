<?php

namespace Tests\Feature;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Install\InstallationState;
use App\Core\Media\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/media-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        Storage::fake('public');
        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        parent::tearDown();
    }

    public function test_media_routes_require_authentication(): void
    {
        $this->get(route('admin.media.index'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.media.store'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_media_listing_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
        ]);

        $response = $this->actingAs($user)->get(route('admin.media.index'));

        $response->assertForbidden();
    }

    public function test_media_upload_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
        ]);

        $response = $this->actingAs($user)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $response->assertForbidden();
    }

    public function test_valid_media_upload_is_persisted_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::UploadMedia,
        ]);

        $response = $this->actingAs($user)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('hero-banner.jpg', 1200, 800),
        ]);

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHas('status');

        /** @var MediaAsset $asset */
        $asset = MediaAsset::query()->firstOrFail();

        $this->assertSame('hero-banner.jpg', $asset->original_name);
        $this->assertSame('jpg', $asset->extension);
        $this->assertSame($user->id, $asset->uploaded_by);
        $this->assertStringContainsString('image/', $asset->mime_type);
        Storage::disk('public')->assertExists($asset->path);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.media.uploaded',
            'user_id' => $user->id,
        ]);
    }

    public function test_invalid_media_upload_is_blocked(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::UploadMedia,
        ]);

        $response = $this->actingAs($user)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_media_listing_is_accessible_with_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
        ]);

        MediaAsset::query()->create([
            'disk' => 'public',
            'original_name' => 'library.pdf',
            'stored_name' => 'asset-file.pdf',
            'path' => 'media/2026/04/asset-file.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'extension' => 'pdf',
            'uploaded_by' => null,
        ]);

        $response = $this->actingAs($user)->get(route('admin.media.index'));

        $response->assertOk();
        $response->assertSee('Media Library');
        $response->assertSee('library.pdf');
        $response->assertSee('application/pdf');
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-media-role-'.str()->random(8),
            'name' => 'Test Media Role',
            'description' => 'Role used by media tests.',
        ]);

        $permissionIds = app(PermissionSynchronizer::class)
            ->syncPermissions(
                collect($permissions)->map(static fn (CorePermission $permission): array => [
                    'scope' => 'core',
                    'slug' => $permission->value,
                    'name' => $permission->label(),
                    'description' => $permission->description(),
                ])->all(),
            )
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
