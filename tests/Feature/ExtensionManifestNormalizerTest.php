<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Validation\ExtensionManifestNormalizer;
use Tests\TestCase;

class ExtensionManifestNormalizerTest extends TestCase
{
    public function test_it_normalizes_a_valid_manifest(): void
    {
        $result = app(ExtensionManifestNormalizer::class)->normalize([
            'name' => 'Analytics Hub',
            'slug' => 'analytics-hub',
            'description' => 'Operational analytics plugin.',
            'version' => '1.2.0',
            'author' => 'Tests',
            'vendor' => 'OpenAI',
            'core' => ['min' => '0.1.0', 'max' => '1.0.0'],
            'provider' => 'Plugins\\AnalyticsHub\\AnalyticsServiceProvider',
            'critical' => 'true',
            'requires' => ['seo-kit', 'cms-base'],
            'capabilities' => ['widgets' => true, 'health_checks' => 'yes'],
            'permissions' => [
                [
                    'slug' => 'view_reports',
                    'name' => 'View Reports',
                    'description' => 'Read reports.',
                ],
                [
                    'slug' => 'manage_reports',
                    'name' => 'Manage Reports',
                ],
            ],
        ], ExtensionType::Plugin);

        $this->assertTrue($result->isValid());
        $this->assertSame('analytics-hub', $result->normalized()['slug']);
        $this->assertTrue($result->normalized()['critical']);
        $this->assertSame(['seo-kit', 'cms-base'], $result->normalized()['requires']);
        $this->assertSame(['widgets', 'health_checks'], $result->normalized()['capabilities']);
        $this->assertSame([
            [
                'slug' => 'view_reports',
                'name' => 'View Reports',
                'description' => 'Read reports.',
            ],
            [
                'slug' => 'manage_reports',
                'name' => 'Manage Reports',
                'description' => null,
            ],
        ], $result->normalized()['permissions']);
        $this->assertSame('OpenAI', $result->normalized()['vendor']);
        $this->assertSame([], $result->warnings());
    }

    public function test_it_reports_errors_for_missing_required_fields(): void
    {
        $result = app(ExtensionManifestNormalizer::class)->normalize([
            'description' => 'Missing required data.',
            'core' => ['min' => '0.1.0'],
        ], ExtensionType::Theme);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->errors());
    }

    public function test_it_normalizes_requires_and_capabilities_with_warnings(): void
    {
        $result = app(ExtensionManifestNormalizer::class)->normalize([
            'name' => 'Docs Theme',
            'slug' => 'docs-theme',
            'description' => 'Theme fixture.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
            'critical' => 'nope',
            'requires' => ['cms-base', 123, '', 'cms-base'],
            'capabilities' => ['admin_pages', 'custom_bridge', 'Bad Capability', 'widgets' => 'nope'],
        ], ExtensionType::Theme);

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->normalized()['critical']);
        $this->assertSame(['cms-base'], $result->normalized()['requires']);
        $this->assertSame(['admin_pages', 'custom_bridge'], $result->normalized()['capabilities']);
        $this->assertNotEmpty($result->warnings());
    }

    public function test_it_normalizes_plugin_permissions_with_warnings_for_invalid_entries(): void
    {
        $result = app(ExtensionManifestNormalizer::class)->normalize([
            'name' => 'Analytics Hub',
            'slug' => 'analytics-hub',
            'description' => 'Operational analytics plugin.',
            'version' => '1.2.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
            'permissions' => [
                ['slug' => 'view_reports', 'name' => 'View Reports'],
                ['slug' => 'view_reports', 'name' => 'Duplicate'],
                ['slug' => 'analytics-hub.manage_reports', 'name' => 'Prefixed Manage'],
                ['slug' => 'Invalid Slug', 'name' => 'Broken'],
                'broken-entry',
            ],
        ], ExtensionType::Plugin);

        $this->assertTrue($result->isValid());
        $this->assertSame([
            [
                'slug' => 'view_reports',
                'name' => 'View Reports',
                'description' => null,
            ],
        ], $result->normalized()['permissions']);
        $this->assertNotEmpty($result->warnings());
    }

    public function test_it_ignores_theme_permissions_with_warning(): void
    {
        $result = app(ExtensionManifestNormalizer::class)->normalize([
            'name' => 'Docs Theme',
            'slug' => 'docs-theme',
            'description' => 'Theme fixture.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
            'permissions' => [
                ['slug' => 'manage_theme', 'name' => 'Manage Theme'],
            ],
        ], ExtensionType::Theme);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->normalized()['permissions']);
        $this->assertNotEmpty($result->warnings());
    }
}
