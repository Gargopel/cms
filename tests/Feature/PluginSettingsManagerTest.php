<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Settings\PluginSettingsManager;
use App\Core\Install\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginSettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/plugin-settings-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        parent::tearDown();
    }

    public function test_it_resolves_catalog_and_default_values_for_valid_installed_plugin(): void
    {
        $plugin = $this->makePluginRecord('blog', ExtensionDiscoveryStatus::Valid, ExtensionLifecycleStatus::Installed, [
            'settings' => [
                'permission' => 'blog.manage_settings',
                'fields' => [
                    [
                        'key' => 'blog_title',
                        'label' => 'Blog Title',
                        'description' => 'Public title.',
                        'type' => 'string',
                        'input' => 'text',
                        'default' => 'Blog',
                    ],
                    [
                        'key' => 'show_excerpts',
                        'label' => 'Show Excerpts',
                        'description' => 'Toggle excerpts.',
                        'type' => 'boolean',
                        'input' => 'checkbox',
                        'default' => true,
                    ],
                ],
            ],
            'permissions' => [
                ['slug' => 'manage_settings', 'name' => 'Manage Settings'],
            ],
        ]);

        $catalog = app(PluginSettingsManager::class)->catalogFor($plugin);

        $this->assertNotNull($catalog);
        $this->assertSame('plugin:blog', $catalog->groupName());
        $this->assertSame('blog.manage_settings', $catalog->resolvedPermission());
        $this->assertSame('Blog', app(PluginSettingsManager::class)->get($plugin, 'blog_title'));
        $this->assertTrue(app(PluginSettingsManager::class)->get($plugin, 'show_excerpts'));
    }

    public function test_it_persists_plugin_settings_with_fallback_defaults(): void
    {
        $plugin = $this->makePluginRecord('blog', ExtensionDiscoveryStatus::Valid, ExtensionLifecycleStatus::Installed, [
            'settings' => [
                'permission' => 'blog.manage_settings',
                'fields' => [
                    [
                        'key' => 'blog_title',
                        'label' => 'Blog Title',
                        'description' => 'Public title.',
                        'type' => 'string',
                        'input' => 'text',
                        'default' => 'Blog',
                    ],
                    [
                        'key' => 'blog_intro',
                        'label' => 'Blog Intro',
                        'description' => 'Intro.',
                        'type' => 'text',
                        'input' => 'textarea',
                        'default' => 'Default intro',
                    ],
                    [
                        'key' => 'show_excerpts',
                        'label' => 'Show Excerpts',
                        'description' => 'Toggle excerpts.',
                        'type' => 'boolean',
                        'input' => 'checkbox',
                        'default' => true,
                    ],
                ],
            ],
            'permissions' => [
                ['slug' => 'manage_settings', 'name' => 'Manage Settings'],
            ],
        ]);

        $values = app(PluginSettingsManager::class)->update($plugin, [
            'blog_title' => 'Newsroom',
            'blog_intro' => 'Operational stories',
            'show_excerpts' => false,
        ]);

        $this->assertSame('Newsroom', $values['blog_title']);
        $this->assertSame('Operational stories', $values['blog_intro']);
        $this->assertFalse($values['show_excerpts']);
        $this->assertSame('Newsroom', app(PluginSettingsManager::class)->get($plugin, 'blog_title'));
        $this->assertFalse(app(PluginSettingsManager::class)->get($plugin, 'show_excerpts'));
        $this->assertDatabaseHas('core_settings', [
            'group_name' => 'plugin:blog',
            'key_name' => 'blog_title',
            'value' => 'Newsroom',
        ]);
    }

    public function test_it_ignores_invalid_or_removed_plugins(): void
    {
        $invalid = $this->makePluginRecord('broken-blog', ExtensionDiscoveryStatus::Invalid, ExtensionLifecycleStatus::Installed, [
            'settings' => [
                'fields' => [
                    ['key' => 'x', 'label' => 'X', 'type' => 'string', 'input' => 'text'],
                ],
            ],
        ]);
        $removed = $this->makePluginRecord('removed-blog', ExtensionDiscoveryStatus::Valid, ExtensionLifecycleStatus::Removed, [
            'settings' => [
                'fields' => [
                    ['key' => 'x', 'label' => 'X', 'type' => 'string', 'input' => 'text'],
                ],
            ],
        ]);

        $this->assertNull(app(PluginSettingsManager::class)->catalogFor($invalid));
        $this->assertNull(app(PluginSettingsManager::class)->catalogFor($removed));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makePluginRecord(
        string $slug,
        ExtensionDiscoveryStatus $discoveryStatus,
        ExtensionLifecycleStatus $lifecycleStatus,
        array $overrides = [],
    ): ExtensionRecord {
        $normalized = array_replace_recursive([
            'type' => 'plugin',
            'name' => ucfirst($slug),
            'slug' => $slug,
            'description' => 'Plugin fixture.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'vendor' => null,
            'core' => ['min' => '0.1.0'],
            'provider' => null,
            'critical' => false,
            'requires' => [],
            'capabilities' => [],
            'permissions' => [],
            'settings' => [],
        ], $overrides);

        return ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => $slug,
            'name' => ucfirst($slug),
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/'.str($slug)->studly()),
            'manifest_path' => base_path('plugins/'.str($slug)->studly().'/plugin.json'),
            'discovery_status' => $discoveryStatus,
            'lifecycle_status' => $lifecycleStatus,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => $normalized,
        ]);
    }
}
