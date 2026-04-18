<?php

namespace Tests\Feature;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Migrations\PluginMigrationService;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Core\Install\InstallationState;
use App\Core\Media\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Plugins\Blog\Models\Post;
use Plugins\Pages\Models\Page;
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

        $asset = $this->createStoredAsset();

        $this->post(route('admin.media.replace', $asset))
            ->assertRedirect(route('admin.login'));

        $this->delete(route('admin.media.destroy', $asset))
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

    public function test_media_replace_and_delete_require_manage_permission(): void
    {
        $asset = $this->createStoredAsset();

        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::UploadMedia,
        ]);

        $this->actingAs($user)
            ->post(route('admin.media.replace', $asset), [
                'file' => UploadedFile::fake()->image('replacement.jpg'),
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.media.destroy', $asset))
            ->assertForbidden();
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

    public function test_valid_media_replace_updates_file_and_metadata_and_is_audited(): void
    {
        $asset = $this->createStoredAsset([
            'original_name' => 'legacy-cover.jpg',
            'stored_name' => 'legacy-cover.jpg',
            'path' => 'media/2026/04/legacy-cover.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 1024,
        ]);

        Storage::disk('public')->put($asset->path, 'legacy-binary');

        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::ManageMedia,
        ]);

        $response = $this->actingAs($user)->post(route('admin.media.replace', $asset), [
            'file' => UploadedFile::fake()->image('fresh-cover.png', 640, 480),
        ]);

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHas('status');

        $asset = $asset->fresh();

        $this->assertSame('fresh-cover.png', $asset->original_name);
        $this->assertSame('png', $asset->extension);
        $this->assertSame('image/png', $asset->mime_type);
        $this->assertStringStartsWith('media/2026/04/', $asset->path);
        Storage::disk('public')->assertMissing('media/2026/04/legacy-cover.jpg');
        Storage::disk('public')->assertExists($asset->path);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.media.replaced',
            'user_id' => $user->id,
        ]);
    }

    public function test_invalid_media_replace_is_blocked_by_validation(): void
    {
        $asset = $this->createStoredAsset();

        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::ManageMedia,
        ]);

        $response = $this->actingAs($user)->post(route('admin.media.replace', $asset), [
            'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php'),
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_unused_media_can_be_deleted_and_is_audited(): void
    {
        $asset = $this->createStoredAsset();
        Storage::disk('public')->put($asset->path, 'asset-binary');

        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::ManageMedia,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.media.destroy', $asset));

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('media_assets', [
            'id' => $asset->getKey(),
        ]);
        Storage::disk('public')->assertMissing($asset->path);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.media.deleted',
            'user_id' => $user->id,
        ]);
    }

    public function test_media_delete_is_blocked_when_used_by_page_featured_image(): void
    {
        $this->prepareContentPluginTables(['pages']);

        $asset = $this->createStoredAsset();

        Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => 'About page.',
            'status' => 'published',
            'featured_image_id' => $asset->getKey(),
        ]);

        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::ManageMedia,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.media.destroy', $asset));

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHasErrors('media');
        $this->assertDatabaseHas('media_assets', [
            'id' => $asset->getKey(),
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.media.delete_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_media_delete_is_blocked_when_used_by_blog_post_featured_image(): void
    {
        $this->prepareContentPluginTables(['blog']);

        $asset = $this->createStoredAsset();

        Post::query()->create([
            'title' => 'Launch',
            'slug' => 'launch',
            'excerpt' => 'Launch excerpt.',
            'content' => 'Launch content.',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_id' => $asset->getKey(),
        ]);

        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
            CorePermission::ManageMedia,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.media.destroy', $asset));

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHasErrors('media');
        $this->assertDatabaseHas('media_assets', [
            'id' => $asset->getKey(),
        ]);
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

    public function test_media_listing_supports_search_and_kind_filters(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMedia,
        ]);

        $this->createStoredAsset([
            'original_name' => 'hero-banner.jpg',
            'stored_name' => 'hero-banner.jpg',
            'path' => 'media/2026/04/hero-banner.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
        ]);

        $this->createStoredAsset([
            'original_name' => 'handbook.pdf',
            'stored_name' => 'handbook.pdf',
            'path' => 'media/2026/04/handbook.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
        ]);

        $this->actingAs($user)
            ->get(route('admin.media.index', ['search' => 'hero', 'kind' => 'images']))
            ->assertOk()
            ->assertSee('hero-banner.jpg')
            ->assertDontSee('handbook.pdf');
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createStoredAsset(array $overrides = []): MediaAsset
    {
        return MediaAsset::query()->create(array_merge([
            'disk' => 'public',
            'original_name' => 'asset.jpg',
            'stored_name' => 'asset.jpg',
            'path' => 'media/2026/04/asset.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'extension' => 'jpg',
            'uploaded_by' => null,
        ], $overrides));
    }

    /**
     * @param  array<int, string>  $slugs
     */
    protected function prepareContentPluginTables(array $slugs): void
    {
        app(ExtensionRegistrySynchronizer::class)->sync();

        foreach ($slugs as $slug) {
            app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, $slug);

            /** @var ExtensionRecord $record */
            $record = ExtensionRecord::query()->where('slug', $slug)->firstOrFail();
            app(PluginMigrationService::class)->runPendingFor($record);
        }
    }
}
