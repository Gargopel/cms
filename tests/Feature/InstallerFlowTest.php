<?php

namespace Tests\Feature;

use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerFlowTest extends TestCase
{
    protected string $installMarkerPath;

    protected string $installEnvPath;

    protected string $sqlitePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/installer-installed.json');
        $this->installEnvPath = storage_path('framework/testing/.env.installer');
        $this->sqlitePath = storage_path('framework/testing/installer.sqlite');

        config()->set('platform.install.marker_path', $this->installMarkerPath);
        config()->set('platform.install.env_path', $this->installEnvPath);
        config()->set('platform.install.env_example_path', base_path('.env.example'));
        config()->set('platform.install.force_uninstalled', false);

        app(InstallationState::class)->clear();
        File::delete([$this->installMarkerPath, $this->installEnvPath, $this->sqlitePath]);
    }

    protected function tearDown(): void
    {
        File::delete([$this->installMarkerPath, $this->installEnvPath, $this->sqlitePath]);

        parent::tearDown();
    }

    public function test_installer_is_accessible_before_installation(): void
    {
        $response = $this->get(route('install.welcome'));

        $response->assertOk();
        $response->assertSee('Start Installation');
    }

    public function test_installer_is_blocked_after_installation_is_completed(): void
    {
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
        ]);

        $response = $this->get(route('install.welcome'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_installer_validates_invalid_database_data(): void
    {
        $response = $this->from(route('install.database'))->post(route('install.database.store'), [
            'driver' => 'mysql',
            'database' => '',
            'host' => '',
            'port' => '',
            'username' => '',
        ]);

        $response->assertRedirect(route('install.database'));
        $response->assertSessionHasErrors(['database']);
    }

    public function test_installation_flow_completes_successfully_and_blocks_reinstallation(): void
    {
        $response = $this->performInstallation();

        $response->assertOk();
        $response->assertSee('Installation Complete');
        $response->assertSee('owner@example.test');
        $this->assertFileExists($this->installMarkerPath);

        $blocked = $this->get(route('install.welcome'));
        $blocked->assertRedirect(route('admin.login'));
    }

    public function test_installation_creates_the_initial_administrator(): void
    {
        $this->performInstallation();

        /** @var User|null $user */
        $user = User::query()->where('email', 'owner@example.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('Owner', $user->name);
        $this->assertTrue($user->hasRole('core_administrator'));
    }

    protected function performInstallation()
    {
        $this->post(route('install.database.store'), [
            'driver' => 'sqlite',
            'database' => $this->sqlitePath,
        ])->assertRedirect(route('install.admin'));

        $this->post(route('install.admin.store'), [
            'app_name' => 'Installed Platform',
            'app_url' => 'http://installed.example.test',
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('install.requirements'));

        return $this->followingRedirects()->post(route('install.run'));
    }
}
