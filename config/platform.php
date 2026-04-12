<?php

return [
    'core' => [
        'name' => 'CMS Platform Core',
        'version' => env('PLATFORM_CORE_VERSION', '0.1.0'),
    ],

    'extensions' => [
        'plugins_path' => 'plugins',
        'themes_path' => 'themes',
        'plugin_manifest' => 'plugin.json',
        'theme_manifest' => 'theme.json',
    ],

    'admin' => [
        'prefix' => env('PLATFORM_ADMIN_PREFIX', 'admin'),
        'seed_local_admin' => env('CORE_ADMIN_SEED_LOCAL', true),
        'local_admin_name' => env('CORE_ADMIN_LOCAL_NAME', 'Local Administrator'),
        'local_admin_email' => env('CORE_ADMIN_LOCAL_EMAIL', 'admin@example.test'),
        'local_admin_password' => env('CORE_ADMIN_LOCAL_PASSWORD', 'admin12345'),
    ],

    'observability' => [
        'plugin_bootstrap_report_cache_key' => 'platform.extensions.bootstrap.last_report',
    ],

    'settings' => [
        'cache_key' => 'platform.core.settings.cache',
    ],

    'install' => [
        'prefix' => env('PLATFORM_INSTALL_PREFIX', 'install'),
        'marker_path' => env('PLATFORM_INSTALL_MARKER_PATH', storage_path('app/platform/install/installed.json')),
        'env_path' => env('PLATFORM_INSTALL_ENV_PATH', base_path('.env')),
        'env_example_path' => env('PLATFORM_INSTALL_ENV_EXAMPLE_PATH', base_path('.env.example')),
        'required_php' => '8.3.0',
        'force_uninstalled' => env('PLATFORM_FORCE_UNINSTALLED', false),
    ],
];
