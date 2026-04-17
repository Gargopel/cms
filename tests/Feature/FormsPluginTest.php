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
use App\Core\Install\InstallationState;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Settings\Enums\CoreSettingType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormField;
use Plugins\Forms\Models\FormSubmission;
use Tests\TestCase;

class FormsPluginTest extends TestCase
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

        $this->installMarkerPath = storage_path('framework/testing/forms-plugin-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
        $this->bootFormsPlugin();
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

    public function test_forms_plugin_migrations_are_detected_and_run_by_plugin_migration_service(): void
    {
        $record = ExtensionRecord::query()->where('slug', 'forms')->firstOrFail();
        $status = app(PluginMigrationService::class)->statusFor($record);

        $this->assertTrue($status->hasMigrationsDirectory());
        $this->assertTrue($status->hasMigrations());
        $this->assertSame(0, $status->pendingCount());
        $this->assertTrue(Schema::hasTable('plugin_forms_forms'));
        $this->assertTrue(Schema::hasTable('plugin_forms_fields'));
        $this->assertTrue(Schema::hasTable('plugin_forms_submissions'));
        $this->assertTrue(Schema::hasTable('plugin_forms_submission_values'));
    }

    public function test_admin_forms_routes_require_authentication(): void
    {
        $this->get($this->adminFormsIndexPath())
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_forms_routes_require_plugin_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
        ]);

        $this->actingAs($user)
            ->get($this->adminFormsIndexPath())
            ->assertForbidden();
    }

    public function test_submissions_route_requires_specific_permission(): void
    {
        $form = Form::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'status' => 'published',
        ]);

        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'forms.view_forms',
        ]);

        $this->actingAs($user)
            ->get($this->adminFormSubmissionsPath($form))
            ->assertForbidden();
    }

    public function test_it_can_create_edit_publish_and_delete_forms_with_real_permissions(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'forms.view_forms',
            'forms.create_forms',
            'forms.edit_forms',
            'forms.publish_forms',
            'forms.delete_forms',
        ]);

        $this->actingAs($user)->post($this->adminFormsStorePath(), [
            'title' => 'Contact Form',
            'slug' => 'contact-form',
            'description' => 'Institutional contact flow.',
            'success_message' => 'Thanks for reaching out.',
            'status' => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('plugin_forms_forms', [
            'slug' => 'contact-form',
            'status' => 'published',
        ]);

        $form = Form::query()->where('slug', 'contact-form')->firstOrFail();

        $this->actingAs($user)->put($this->adminFormsUpdatePath($form), [
            'title' => 'Contact Form Updated',
            'slug' => 'contact-form',
            'description' => 'Updated description.',
            'success_message' => 'Updated success message.',
            'status' => 'draft',
        ])->assertRedirect($this->adminFormsIndexPath());

        $this->assertDatabaseHas('plugin_forms_forms', [
            'slug' => 'contact-form',
            'title' => 'Contact Form Updated',
            'status' => 'draft',
        ]);

        $this->actingAs($user)->delete($this->adminFormsDestroyPath($form))
            ->assertRedirect($this->adminFormsIndexPath());

        $this->assertDatabaseMissing('plugin_forms_forms', [
            'slug' => 'contact-form',
        ]);
    }

    public function test_it_can_create_and_edit_fields_with_real_permission(): void
    {
        $form = Form::query()->create([
            'title' => 'Lead Form',
            'slug' => 'lead-form',
            'status' => 'draft',
        ]);

        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'forms.view_forms',
            'forms.edit_forms',
        ]);

        $this->actingAs($user)->post($this->adminFormFieldsStorePath($form), [
            'label' => 'Preferred Plan',
            'name' => 'preferred_plan',
            'type' => 'select',
            'options_text' => "Starter\nGrowth",
            'is_required' => '1',
            'sort_order' => 10,
        ])->assertRedirect($this->adminFormFieldsIndexPath($form));

        $this->assertDatabaseHas('plugin_forms_fields', [
            'form_id' => $form->getKey(),
            'name' => 'preferred_plan',
            'type' => 'select',
            'is_required' => true,
        ]);

        $field = FormField::query()->where('form_id', $form->getKey())->where('name', 'preferred_plan')->firstOrFail();
        $this->assertSame(['Starter', 'Growth'], $field->optionValues());

        $this->actingAs($user)->put($this->adminFormFieldUpdatePath($form, $field), [
            'label' => 'Preferred Plan Updated',
            'name' => 'preferred_plan',
            'type' => 'select',
            'options_text' => "Growth\nEnterprise",
            'is_required' => '0',
            'sort_order' => 20,
        ])->assertRedirect($this->adminFormFieldsIndexPath($form));

        $field = $field->fresh();
        $this->assertSame('Preferred Plan Updated', $field->label);
        $this->assertFalse($field->is_required);
        $this->assertSame(['Growth', 'Enterprise'], $field->optionValues());
    }

    public function test_public_route_only_renders_published_forms(): void
    {
        Form::query()->create([
            'title' => 'Published Form',
            'slug' => 'published-form',
            'status' => 'published',
        ]);

        Form::query()->create([
            'title' => 'Draft Form',
            'slug' => 'draft-form',
            'status' => 'draft',
        ]);

        $this->get('/forms/published-form')
            ->assertOk()
            ->assertSee('Published Form');

        $this->get('/forms/draft-form')
            ->assertNotFound();
    }

    public function test_valid_public_submission_is_persisted_with_values(): void
    {
        $form = $this->createPublishedContactForm();

        $response = $this->post('/forms/'.$form->slug, [
            'full_name' => 'Jane Doe',
            'email_address' => 'jane@example.test',
            'message' => 'Hello from a valid submission.',
            'department' => 'Sales',
            'privacy_consent' => '1',
        ]);

        $response->assertRedirect('/forms/'.$form->slug);
        $response->assertSessionHas('status');

        $submission = FormSubmission::query()->with('values')->firstOrFail();

        $this->assertSame($form->getKey(), $submission->form_id);
        $this->assertCount(5, $submission->values);
        $this->assertDatabaseHas('plugin_forms_submission_values', [
            'submission_id' => $submission->getKey(),
            'field_name' => 'email_address',
            'value' => 'jane@example.test',
        ]);
        $this->assertDatabaseHas('plugin_forms_submission_values', [
            'submission_id' => $submission->getKey(),
            'field_name' => 'privacy_consent',
            'value' => '1',
        ]);
    }

    public function test_invalid_public_submission_is_blocked(): void
    {
        $form = $this->createPublishedContactForm();

        $response = $this->from('/forms/'.$form->slug)->post('/forms/'.$form->slug, [
            'full_name' => '',
            'email_address' => 'not-an-email',
            'department' => 'Invalid Option',
        ]);

        $response->assertRedirect('/forms/'.$form->slug);
        $response->assertSessionHasErrors(['full_name', 'email_address', 'department', 'privacy_consent']);
        $this->assertDatabaseCount('plugin_forms_submissions', 0);
    }

    public function test_admin_can_view_form_submissions_with_permission(): void
    {
        $form = $this->createPublishedContactForm();

        $this->post('/forms/'.$form->slug, [
            'full_name' => 'John Doe',
            'email_address' => 'john@example.test',
            'message' => 'Admin should see this.',
            'department' => 'Support',
            'privacy_consent' => '1',
        ])->assertRedirect();

        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'forms.view_forms',
            'forms.view_form_submissions',
        ]);

        $this->actingAs($user)
            ->get($this->adminFormSubmissionsPath($form))
            ->assertOk()
            ->assertSee('Submission #')
            ->assertSee('john@example.test')
            ->assertSee('Support');
    }

    public function test_public_form_uses_theme_override_and_falls_back_to_plugin_view(): void
    {
        $form = $this->createPublishedContactForm();

        $this->get('/forms/'.$form->slug)
            ->assertOk()
            ->assertSee('Official Forms Plugin')
            ->assertSee($form->title);

        $themePath = storage_path('framework/testing/themes/forms-override');
        $this->temporaryThemePaths[] = $themePath;

        File::ensureDirectoryExists($themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'forms');
        File::put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'forms'.DIRECTORY_SEPARATOR.'show.blade.php',
            <<<'BLADE'
<html>
<body>
    <h1>Theme Override For {{ $formRecord->title }}</h1>
</body>
</html>
BLADE
        );

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'forms-theme',
            'name' => 'Forms Theme',
            'description' => 'Temporary theme for forms tests.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $themePath,
            'manifest_path' => $themePath.DIRECTORY_SEPARATOR.'theme.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        app(CoreSettingsManager::class)->put('active_theme_slug', 'forms-theme', CoreSettingType::String, 'themes');

        $this->get('/forms/'.$form->slug)
            ->assertOk()
            ->assertSee('Theme Override For Contact Us')
            ->assertDontSee('Official Forms Plugin');
    }

    public function test_plugin_contributes_menu_and_dashboard_surfaces_when_user_can_view_forms(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'forms.view_forms',
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Forms')
            ->assertSee('Forms Inbox');
    }

    protected function bootFormsPlugin(): void
    {
        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'forms');

        $record = ExtensionRecord::query()->where('slug', 'forms')->firstOrFail();
        app(PluginMigrationService::class)->runPendingFor($record);

        app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, 'forms');
        app(PluginProviderBootstrapper::class)->bootstrap();
    }

    protected function createUserWithPermissions(array $permissionSlugs): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'forms-plugin-role-'.str()->random(8),
            'name' => 'Forms Plugin Role',
            'description' => 'Role used by Forms plugin tests.',
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }

    protected function createPublishedContactForm(): Form
    {
        $form = Form::query()->create([
            'title' => 'Contact Us',
            'slug' => 'contact-us',
            'description' => 'Simple public contact form.',
            'success_message' => 'Thanks for contacting our team.',
            'status' => 'published',
        ]);

        $form->fields()->createMany([
            [
                'label' => 'Full Name',
                'name' => 'full_name',
                'type' => 'text',
                'is_required' => true,
                'sort_order' => 10,
            ],
            [
                'label' => 'Email Address',
                'name' => 'email_address',
                'type' => 'email',
                'is_required' => true,
                'sort_order' => 20,
            ],
            [
                'label' => 'Message',
                'name' => 'message',
                'type' => 'textarea',
                'is_required' => false,
                'sort_order' => 30,
            ],
            [
                'label' => 'Department',
                'name' => 'department',
                'type' => 'select',
                'options' => ['Sales', 'Support'],
                'is_required' => true,
                'sort_order' => 40,
            ],
            [
                'label' => 'Privacy Consent',
                'name' => 'privacy_consent',
                'type' => 'checkbox',
                'is_required' => true,
                'sort_order' => 50,
            ],
        ]);

        return $form->fresh(['fields']);
    }

    protected function adminFormsIndexPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms';
    }

    protected function adminFormsStorePath(): string
    {
        return $this->adminFormsIndexPath();
    }

    protected function adminFormsUpdatePath(Form $form): string
    {
        return $this->adminFormsIndexPath().'/'.$form->getKey();
    }

    protected function adminFormsDestroyPath(Form $form): string
    {
        return $this->adminFormsUpdatePath($form);
    }

    protected function adminFormFieldsIndexPath(Form $form): string
    {
        return $this->adminFormsIndexPath().'/'.$form->getKey().'/fields';
    }

    protected function adminFormFieldsStorePath(Form $form): string
    {
        return $this->adminFormFieldsIndexPath($form);
    }

    protected function adminFormFieldUpdatePath(Form $form, FormField $field): string
    {
        return $this->adminFormFieldsIndexPath($form).'/'.$field->getKey();
    }

    protected function adminFormSubmissionsPath(Form $form): string
    {
        return $this->adminFormsIndexPath().'/'.$form->getKey().'/submissions';
    }
}
