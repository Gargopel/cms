<?php

namespace Tests\Feature;

use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Migrations\PluginMigrationService;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionOperationalStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Core\Extensions\Settings\PluginSettingsManager;
use App\Core\Install\InstallationState;
use App\Core\Media\Models\MediaAsset;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Settings\Enums\CoreSettingType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Blog\Models\Tag;
use Tests\TestCase;

class BlogPluginTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    /**
     * @var array<int, string>
     */
    protected array $temporaryThemePaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/blog-plugin-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        Storage::fake('public');
        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
        $this->bootBlogPlugin();
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        foreach ($this->temporaryThemePaths as $path) {
            File::deleteDirectory($path);
        }

        parent::tearDown();
    }

    public function test_blog_plugin_migrations_are_detected_and_run_by_plugin_migration_service(): void
    {
        $record = ExtensionRecord::query()->where('slug', 'blog')->firstOrFail();
        $status = app(PluginMigrationService::class)->statusFor($record);

        $this->assertTrue($status->hasMigrationsDirectory());
        $this->assertTrue($status->hasMigrations());
        $this->assertSame(0, $status->pendingCount());
        $this->assertTrue(Schema::hasTable('plugin_blog_posts'));
    }

    public function test_admin_blog_routes_require_authentication(): void
    {
        $response = $this->get($this->adminBlogIndexPath());

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_blog_routes_require_plugin_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
        ]);

        $response = $this->actingAs($user)->get($this->adminBlogIndexPath());

        $response->assertForbidden();
    }

    public function test_category_routes_require_manage_categories_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
        ]);

        $response = $this->actingAs($user)->get($this->adminBlogCategoriesIndexPath());

        $response->assertForbidden();
    }

    public function test_tag_routes_require_manage_tags_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
        ]);

        $response = $this->actingAs($user)->get($this->adminBlogTagsIndexPath());

        $response->assertForbidden();
    }

    public function test_it_can_create_and_edit_categories_with_real_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.manage_categories',
        ]);

        $this->actingAs($user)->post($this->adminBlogCategoriesStorePath(), [
            'name' => 'News',
            'slug' => 'news',
            'description' => 'Editorial updates.',
        ])->assertRedirect($this->adminBlogCategoriesIndexPath());

        $this->assertDatabaseHas('plugin_blog_categories', [
            'slug' => 'news',
        ]);

        $category = Category::query()->where('slug', 'news')->firstOrFail();

        $this->actingAs($user)->put($this->adminBlogCategoriesUpdatePath($category), [
            'name' => 'Platform News',
            'slug' => 'platform-news',
            'description' => 'Updated editorial line.',
        ])->assertRedirect($this->adminBlogCategoriesIndexPath());

        $this->assertDatabaseHas('plugin_blog_categories', [
            'slug' => 'platform-news',
            'name' => 'Platform News',
        ]);
    }

    public function test_it_can_create_and_edit_tags_with_real_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.manage_tags',
        ]);

        $this->actingAs($user)->post($this->adminBlogTagsStorePath(), [
            'name' => 'Laravel',
            'slug' => 'laravel',
            'description' => 'Framework tag.',
        ])->assertRedirect($this->adminBlogTagsIndexPath());

        $this->assertDatabaseHas('plugin_blog_tags', [
            'slug' => 'laravel',
        ]);

        $tag = Tag::query()->where('slug', 'laravel')->firstOrFail();

        $this->actingAs($user)->put($this->adminBlogTagsUpdatePath($tag), [
            'name' => 'Laravel CMS',
            'slug' => 'laravel-cms',
            'description' => 'Updated framework tag.',
        ])->assertRedirect($this->adminBlogTagsIndexPath());

        $this->assertDatabaseHas('plugin_blog_tags', [
            'slug' => 'laravel-cms',
            'name' => 'Laravel CMS',
        ]);
    }

    public function test_it_can_create_edit_publish_and_delete_posts_with_real_permissions(): void
    {
        $category = Category::query()->create([
            'name' => 'Releases',
            'slug' => 'releases',
            'description' => 'Release notes and announcements.',
        ]);
        $tag = Tag::query()->create([
            'name' => 'Platform',
            'slug' => 'platform',
        ]);
        $secondTag = Tag::query()->create([
            'name' => 'Launch',
            'slug' => 'launch',
        ]);

        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.create_posts',
            'blog.edit_posts',
            'blog.publish_posts',
            'blog.delete_posts',
        ]);

        $createResponse = $this->actingAs($user)->post($this->adminBlogStorePath(), [
            'title' => 'Launch Notes',
            'slug' => 'launch-notes',
            'excerpt' => 'Resumo editorial curto do post.',
            'content' => 'Conteudo editorial inicial',
            'status' => 'published',
            'category_id' => $category->getKey(),
            'tag_ids' => [$tag->getKey(), $secondTag->getKey()],
        ]);

        $createResponse->assertRedirect($this->adminBlogIndexPath());
        $this->assertDatabaseHas('plugin_blog_posts', [
            'slug' => 'launch-notes',
            'status' => 'published',
            'category_id' => $category->getKey(),
        ]);
        $post = Post::query()->where('slug', 'launch-notes')->firstOrFail();
        $this->assertDatabaseHas('plugin_blog_post_tag', [
            'post_id' => $post->getKey(),
            'tag_id' => $tag->getKey(),
        ]);
        $this->assertDatabaseHas('plugin_blog_post_tag', [
            'post_id' => $post->getKey(),
            'tag_id' => $secondTag->getKey(),
        ]);

        $updateResponse = $this->actingAs($user)->put($this->adminBlogUpdatePath($post), [
            'title' => 'Launch Notes Updated',
            'slug' => 'launch-notes',
            'excerpt' => 'Resumo atualizado.',
            'content' => 'Conteudo editorial atualizado',
            'status' => 'draft',
            'category_id' => $category->getKey(),
            'tag_ids' => [$secondTag->getKey()],
        ]);

        $updateResponse->assertRedirect($this->adminBlogIndexPath());
        $this->assertDatabaseHas('plugin_blog_posts', [
            'slug' => 'launch-notes',
            'title' => 'Launch Notes Updated',
            'status' => 'draft',
            'category_id' => $category->getKey(),
        ]);
        $this->assertDatabaseMissing('plugin_blog_post_tag', [
            'post_id' => $post->getKey(),
            'tag_id' => $tag->getKey(),
        ]);
        $this->assertDatabaseHas('plugin_blog_post_tag', [
            'post_id' => $post->getKey(),
            'tag_id' => $secondTag->getKey(),
        ]);

        $deleteResponse = $this->actingAs($user)->delete($this->adminBlogDestroyPath($post));

        $deleteResponse->assertRedirect($this->adminBlogIndexPath());
        $this->assertDatabaseMissing('plugin_blog_posts', [
            'slug' => 'launch-notes',
        ]);
    }

    public function test_it_persists_featured_image_reference_for_post(): void
    {
        $category = Category::query()->create([
            'name' => 'Highlights',
            'slug' => 'highlights',
        ]);
        $tag = Tag::query()->create([
            'name' => 'Media',
            'slug' => 'media',
        ]);

        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.create_posts',
            'blog.edit_posts',
            'blog.publish_posts',
        ]);

        $asset = $this->createImageAsset('blog-cover.jpg');

        $this->actingAs($user)->post($this->adminBlogStorePath(), [
            'title' => 'Media Post',
            'slug' => 'media-post',
            'excerpt' => 'Excerpt',
            'content' => 'Post with featured image',
            'status' => 'published',
            'featured_image_id' => $asset->getKey(),
            'category_id' => $category->getKey(),
            'tag_ids' => [$tag->getKey()],
        ])->assertRedirect($this->adminBlogIndexPath());

        $this->assertDatabaseHas('plugin_blog_posts', [
            'slug' => 'media-post',
            'featured_image_id' => $asset->getKey(),
            'category_id' => $category->getKey(),
        ]);
        $post = Post::query()->where('slug', 'media-post')->firstOrFail();
        $this->assertDatabaseHas('plugin_blog_post_tag', [
            'post_id' => $post->getKey(),
            'tag_id' => $tag->getKey(),
        ]);
    }

    public function test_it_blocks_invalid_featured_image_reference_for_post(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.create_posts',
            'blog.edit_posts',
            'blog.publish_posts',
        ]);

        $pdfAsset = MediaAsset::query()->create([
            'disk' => 'public',
            'original_name' => 'manual.pdf',
            'stored_name' => 'manual.pdf',
            'path' => 'media/2026/04/manual.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'extension' => 'pdf',
            'uploaded_by' => null,
        ]);

        $response = $this->actingAs($user)->post($this->adminBlogStorePath(), [
            'title' => 'Broken Media Post',
            'slug' => 'broken-media-post',
            'excerpt' => 'Excerpt',
            'content' => 'Should fail',
            'status' => 'published',
            'featured_image_id' => $pdfAsset->getKey(),
        ]);

        $response->assertSessionHasErrors('featured_image_id');
        $this->assertDatabaseMissing('plugin_blog_posts', [
            'slug' => 'broken-media-post',
        ]);
    }

    public function test_it_blocks_invalid_category_reference_for_post(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.create_posts',
            'blog.edit_posts',
            'blog.publish_posts',
        ]);

        $response = $this->actingAs($user)->post($this->adminBlogStorePath(), [
            'title' => 'Invalid Category Post',
            'slug' => 'invalid-category-post',
            'excerpt' => 'Excerpt',
            'content' => 'Should fail',
            'status' => 'published',
            'category_id' => 999999,
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('plugin_blog_posts', [
            'slug' => 'invalid-category-post',
        ]);
    }

    public function test_it_blocks_invalid_tag_reference_for_post(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.create_posts',
            'blog.edit_posts',
            'blog.publish_posts',
        ]);

        $response = $this->actingAs($user)->post($this->adminBlogStorePath(), [
            'title' => 'Invalid Tag Post',
            'slug' => 'invalid-tag-post',
            'excerpt' => 'Excerpt',
            'content' => 'Should fail',
            'status' => 'published',
            'tag_ids' => [999999],
        ]);

        $response->assertSessionHasErrors('tag_ids.0');
        $this->assertDatabaseMissing('plugin_blog_posts', [
            'slug' => 'invalid-tag-post',
        ]);
    }

    public function test_editor_without_publish_permission_cannot_change_public_status(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'blog.view_posts',
            'blog.create_posts',
            'blog.edit_posts',
        ]);

        $this->actingAs($user)->post($this->adminBlogStorePath(), [
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'excerpt' => 'Resumo de rascunho.',
            'content' => 'Conteudo do rascunho',
            'status' => 'published',
        ])->assertRedirect($this->adminBlogIndexPath());

        $post = Post::query()->where('slug', 'draft-post')->firstOrFail();
        $this->assertSame('draft', $post->status->value);
        $this->assertNull($post->published_at);

        $published = Post::query()->create([
            'title' => 'Published Post',
            'slug' => 'published-post',
            'excerpt' => 'Resumo publicado.',
            'content' => 'Conteudo publicado',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($user)->put($this->adminBlogUpdatePath($published), [
            'title' => 'Published Post Edited',
            'slug' => 'published-post',
            'excerpt' => 'Resumo editado.',
            'content' => 'Conteudo editado',
            'status' => 'draft',
        ])->assertRedirect($this->adminBlogIndexPath());

        $this->assertSame('published', $published->fresh()->status->value);
    }

    public function test_public_blog_routes_only_render_published_posts(): void
    {
        $category = Category::query()->create([
            'name' => 'Editorial',
            'slug' => 'editorial',
        ]);

        Post::query()->create([
            'title' => 'Visible Post',
            'slug' => 'visible-post',
            'excerpt' => 'Resumo visivel.',
            'content' => 'Visible content',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category->getKey(),
        ]);

        Post::query()->create([
            'title' => 'Hidden Draft',
            'slug' => 'hidden-draft',
            'excerpt' => 'Resumo oculto.',
            'content' => 'Hidden content',
            'status' => 'draft',
            'category_id' => $category->getKey(),
        ]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Visible Post')
            ->assertDontSee('Hidden Draft');

        $this->get('/blog/visible-post')
            ->assertOk()
            ->assertSee('Visible Post')
            ->assertSee('Visible content');

        $this->get('/blog/hidden-draft')
            ->assertNotFound();
    }

    public function test_public_category_route_only_renders_published_posts_from_selected_category(): void
    {
        $category = Category::query()->create([
            'name' => 'Releases',
            'slug' => 'releases',
            'description' => 'Platform release notes.',
        ]);

        $otherCategory = Category::query()->create([
            'name' => 'Stories',
            'slug' => 'stories',
        ]);

        Post::query()->create([
            'title' => 'Published Release',
            'slug' => 'published-release',
            'excerpt' => 'Release excerpt.',
            'content' => 'Release content',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category->getKey(),
        ]);

        Post::query()->create([
            'title' => 'Draft Release',
            'slug' => 'draft-release',
            'excerpt' => 'Draft excerpt.',
            'content' => 'Draft content',
            'status' => 'draft',
            'category_id' => $category->getKey(),
        ]);

        Post::query()->create([
            'title' => 'Other Published Story',
            'slug' => 'other-published-story',
            'excerpt' => 'Story excerpt.',
            'content' => 'Story content',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $otherCategory->getKey(),
        ]);

        $this->get('/blog/category/releases')
            ->assertOk()
            ->assertSee('Releases')
            ->assertSee('Published Release')
            ->assertDontSee('Draft Release')
            ->assertDontSee('Other Published Story');
    }

    public function test_public_tag_route_only_renders_published_posts_from_selected_tag(): void
    {
        $tag = Tag::query()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
            'description' => 'Framework posts.',
        ]);

        $otherTag = Tag::query()->create([
            'name' => 'Vue',
            'slug' => 'vue',
        ]);

        $published = Post::query()->create([
            'title' => 'Laravel Published',
            'slug' => 'laravel-published',
            'excerpt' => 'Published excerpt.',
            'content' => 'Published content',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $published->tags()->sync([$tag->getKey()]);

        $draft = Post::query()->create([
            'title' => 'Laravel Draft',
            'slug' => 'laravel-draft',
            'excerpt' => 'Draft excerpt.',
            'content' => 'Draft content',
            'status' => 'draft',
        ]);
        $draft->tags()->sync([$tag->getKey()]);

        $other = Post::query()->create([
            'title' => 'Vue Published',
            'slug' => 'vue-published',
            'excerpt' => 'Vue excerpt.',
            'content' => 'Vue content',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $other->tags()->sync([$otherTag->getKey()]);

        $this->get('/blog/tag/laravel')
            ->assertOk()
            ->assertSee('Laravel')
            ->assertSee('Laravel Published')
            ->assertDontSee('Laravel Draft')
            ->assertDontSee('Vue Published');
    }

    public function test_public_blog_routes_use_theme_override_and_fall_back_to_plugin_views(): void
    {
        $category = Category::query()->create([
            'name' => 'Theme Ready',
            'slug' => 'theme-ready',
        ]);
        $tag = Tag::query()->create([
            'name' => 'Theme Tag',
            'slug' => 'theme-tag',
        ]);

        $post = Post::query()->create([
            'title' => 'Theme Ready Post',
            'slug' => 'theme-ready-post',
            'excerpt' => 'Resumo para tema.',
            'content' => 'Theme content',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category->getKey(),
        ]);
        $post->tags()->sync([$tag->getKey()]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Blog')
            ->assertSee('Theme Ready Post');

        $this->get('/blog/theme-ready-post')
            ->assertOk()
            ->assertSee('Blog Plugin')
            ->assertSee('Theme Ready Post');

        $themePath = storage_path('framework/testing/themes/blog-override');
        $this->temporaryThemePaths[] = $themePath;

        File::ensureDirectoryExists($themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'blog');
        File::put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'blog'.DIRECTORY_SEPARATOR.'index.blade.php',
            <<<'BLADE'
<html>
<body>
    <h1>Theme Blog Index Override</h1>
    @foreach ($posts as $post)
        <div>{{ $post->title }}</div>
    @endforeach
</body>
</html>
BLADE
        );
        File::put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'blog'.DIRECTORY_SEPARATOR.'show.blade.php',
            <<<'BLADE'
<html>
<body>
    <h1>Theme Override For {{ $post->title }}</h1>
</body>
</html>
BLADE
        );
        File::put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'blog'.DIRECTORY_SEPARATOR.'category.blade.php',
            <<<'BLADE'
<html>
<body>
    <h1>Theme Category Override {{ $category->name }}</h1>
</body>
</html>
BLADE
        );
        File::put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'blog'.DIRECTORY_SEPARATOR.'tag.blade.php',
            <<<'BLADE'
<html>
<body>
    <h1>Theme Tag Override {{ $tag->name }}</h1>
</body>
</html>
BLADE
        );

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'blog-theme',
            'name' => 'Blog Theme',
            'description' => 'Temporary theme for blog tests.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $themePath,
            'manifest_path' => $themePath.DIRECTORY_SEPARATOR.'theme.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        app(CoreSettingsManager::class)->put('active_theme_slug', 'blog-theme', CoreSettingType::String, 'themes');

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Theme Blog Index Override')
            ->assertSee('Theme Ready Post')
            ->assertDontSee('Published Posts');

        $this->get('/blog/theme-ready-post')
            ->assertOk()
            ->assertSee('Theme Override For Theme Ready Post')
            ->assertDontSee('Blog Plugin');

        $this->get('/blog/category/theme-ready')
            ->assertOk()
            ->assertSee('Theme Category Override Theme Ready')
            ->assertDontSee('Blog Category');

        $this->get('/blog/tag/theme-tag')
            ->assertOk()
            ->assertSee('Theme Tag Override Theme Tag')
            ->assertDontSee('Blog Tag');
    }

    public function test_public_blog_renders_with_and_without_featured_image(): void
    {
        $asset = $this->createImageAsset('post-cover.jpg');

        Post::query()->create([
            'title' => 'With Image',
            'slug' => 'with-image',
            'excerpt' => 'Excerpt with image.',
            'content' => 'Visible content',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_id' => $asset->getKey(),
        ]);

        Post::query()->create([
            'title' => 'Without Image',
            'slug' => 'without-image',
            'excerpt' => 'Excerpt without image.',
            'content' => 'No image content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/blog/with-image')
            ->assertOk()
            ->assertSee($asset->url(), false)
            ->assertSee('With Image');

        $this->get('/blog/without-image')
            ->assertOk()
            ->assertDontSee('img class="featured-image"', false)
            ->assertSee('Without Image');
    }

    public function test_public_blog_list_uses_persisted_plugin_settings(): void
    {
        Post::query()->create([
            'title' => 'Configured Post',
            'slug' => 'configured-post',
            'excerpt' => 'Resumo configuravel.',
            'content' => 'Configured content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(PluginSettingsManager::class)->update('blog', [
            'blog_title' => 'Operations Journal',
            'blog_intro' => 'Editorial stream for platform operators.',
            'show_excerpts' => false,
        ]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Operations Journal')
            ->assertSee('Editorial stream for platform operators.')
            ->assertSee('Configured Post')
            ->assertDontSee('Resumo configuravel.');
    }

    public function test_plugin_contributes_menu_and_dashboard_surfaces_when_user_can_view_posts(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Blog');
        $response->assertSee('Blog Posts');
    }

    public function test_blog_index_exposes_categories_action_with_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.manage_categories',
        ]);

        $this->actingAs($user)
            ->get($this->adminBlogIndexPath())
            ->assertOk()
            ->assertSee('Categories');
    }

    public function test_blog_index_exposes_tags_action_with_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
            'blog.manage_tags',
        ]);

        $this->actingAs($user)
            ->get($this->adminBlogIndexPath())
            ->assertOk()
            ->assertSee('Tags');
    }

    public function test_admin_blog_index_supports_search_status_and_category_filters(): void
    {
        $category = Category::query()->create([
            'name' => 'Releases',
            'slug' => 'releases',
        ]);

        $otherCategory = Category::query()->create([
            'name' => 'Stories',
            'slug' => 'stories',
        ]);

        Post::query()->create([
            'title' => 'Launch Notes',
            'slug' => 'launch-notes',
            'excerpt' => 'Release summary.',
            'content' => 'Published release content.',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category->getKey(),
        ]);

        Post::query()->create([
            'title' => 'Operator Story',
            'slug' => 'operator-story',
            'excerpt' => 'Story summary.',
            'content' => 'Draft story content.',
            'status' => 'draft',
            'category_id' => $otherCategory->getKey(),
        ]);

        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'blog.view_posts',
        ]);

        $this->actingAs($user)
            ->get($this->adminBlogIndexPath().'?search=launch&status=published&category='.$category->getKey())
            ->assertOk()
            ->assertSee('Launch Notes')
            ->assertDontSee('Operator Story');
    }

    public function test_public_blog_index_supports_simple_search_only_for_published_posts(): void
    {
        Post::query()->create([
            'title' => 'Launch Notes',
            'slug' => 'launch-notes',
            'excerpt' => 'Searchable public excerpt.',
            'content' => 'Published launch content.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::query()->create([
            'title' => 'Launch Draft',
            'slug' => 'launch-draft',
            'excerpt' => 'Draft excerpt.',
            'content' => 'Draft launch content.',
            'status' => 'draft',
        ]);

        $this->get('/blog?q=launch')
            ->assertOk()
            ->assertSee('Launch Notes')
            ->assertSee('Showing public results for "launch". Draft posts never appear here.', false)
            ->assertDontSee('Launch Draft');
    }

    protected function bootBlogPlugin(): void
    {
        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'blog');

        $record = ExtensionRecord::query()->where('slug', 'blog')->firstOrFail();
        app(PluginMigrationService::class)->runPendingFor($record);

        app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, 'blog');
        app(PluginProviderBootstrapper::class)->bootstrap();
    }

    protected function createUserWithPermissions(array $permissionSlugs): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'blog-plugin-role-'.str()->random(8),
            'name' => 'Blog Plugin Role',
            'description' => 'Role used by Blog plugin tests.',
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }

    protected function adminBlogIndexPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/posts';
    }

    protected function adminBlogStorePath(): string
    {
        return $this->adminBlogIndexPath();
    }

    protected function adminBlogUpdatePath(Post $post): string
    {
        return $this->adminBlogIndexPath().'/'.$post->getKey();
    }

    protected function adminBlogDestroyPath(Post $post): string
    {
        return $this->adminBlogUpdatePath($post);
    }

    protected function adminBlogCategoriesIndexPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/categories';
    }

    protected function adminBlogCategoriesStorePath(): string
    {
        return $this->adminBlogCategoriesIndexPath();
    }

    protected function adminBlogCategoriesUpdatePath(Category $category): string
    {
        return $this->adminBlogCategoriesIndexPath().'/'.$category->getKey();
    }

    protected function adminBlogTagsIndexPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/tags';
    }

    protected function adminBlogTagsStorePath(): string
    {
        return $this->adminBlogTagsIndexPath();
    }

    protected function adminBlogTagsUpdatePath(Tag $tag): string
    {
        return $this->adminBlogTagsIndexPath().'/'.$tag->getKey();
    }

    protected function createImageAsset(string $name): MediaAsset
    {
        $path = 'media/2026/04/'.$name;
        Storage::disk('public')->put($path, 'image-binary');

        return MediaAsset::query()->create([
            'disk' => 'public',
            'original_name' => $name,
            'stored_name' => $name,
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'extension' => 'jpg',
            'uploaded_by' => null,
        ]);
    }
}
