<?php

namespace App\Core\Install\Http\Controllers;

use App\Core\Install\InstallationState;
use App\Core\Install\Setup\InstallApplication;
use App\Core\Install\Setup\InstallException;
use App\Core\Install\Support\InstallRequirementChecker;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class InstallWizardController extends Controller
{
    public function welcome(): View
    {
        return view('install.welcome', $this->baseViewData(
            title: 'Welcome',
            subtitle: 'Instalador guiado do core para preparar o ambiente, o banco e o administrador inicial.',
            step: 'welcome',
        ));
    }

    public function requirements(InstallRequirementChecker $checker): View
    {
        return view('install.requirements', $this->baseViewData(
            title: 'Requirements',
            subtitle: 'Checagem minima de ambiente e permissoes antes de tocar configuracao e banco.',
            step: 'requirements',
        ) + [
            'report' => $checker->inspect(),
        ]);
    }

    public function database(Request $request): View
    {
        return view('install.database', $this->baseViewData(
            title: 'Database',
            subtitle: 'Informe a conexao de banco que sera usada pelo core apos a instalacao.',
            step: 'database',
        ) + [
            'database' => $request->session()->get('installer.database', [
                'driver' => 'sqlite',
                'database' => 'database/database.sqlite',
                'host' => '127.0.0.1',
                'port' => '3306',
                'username' => '',
                'password' => '',
            ]),
        ]);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'driver' => ['required', Rule::in(['sqlite', 'mysql', 'pgsql'])],
            'database' => ['required', 'string', 'max:255'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'string', 'max:20'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['driver'] !== 'sqlite') {
            validator($data, [
                'host' => ['required', 'string', 'max:255'],
                'port' => ['required', 'string', 'max:20'],
                'username' => ['required', 'string', 'max:255'],
            ])->validate();
        }

        $request->session()->put('installer.database', $data);

        return redirect()->route('install.admin');
    }

    public function administrator(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('installer.database')) {
            return redirect()->route('install.database');
        }

        return view('install.admin', $this->baseViewData(
            title: 'Administrator',
            subtitle: 'Defina o primeiro administrador real do produto e os dados principais da instalacao.',
            step: 'admin',
        ) + [
            'administrator' => $request->session()->get('installer.admin', [
                'app_name' => config('platform.core.name', 'CMS Platform Core'),
                'app_url' => url('/'),
                'name' => 'Administrator',
                'email' => '',
            ]),
        ]);
    }

    public function storeAdministrator(Request $request): RedirectResponse
    {
        if (! $request->session()->has('installer.database')) {
            return redirect()->route('install.database');
        }

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->session()->put('installer.admin', $data);

        return redirect()->route('install.requirements');
    }

    public function install(Request $request, InstallApplication $installer): RedirectResponse
    {
        $database = $request->session()->get('installer.database');
        $administrator = $request->session()->get('installer.admin');

        if (! is_array($database) || ! is_array($administrator)) {
            return redirect()->route('install.database');
        }

        try {
            $completed = $installer->run($database, $administrator);
        } catch (InstallException $exception) {
            return redirect()
                ->route('install.requirements')
                ->withErrors(['install' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('install.requirements')
                ->withErrors(['install' => 'Nao foi possivel concluir a instalacao com os dados informados. Verifique o banco, as permissoes e tente novamente.']);
        }

        $request->session()->forget(['installer.database', 'installer.admin']);
        $request->session()->put('installer.completed', $completed);

        return redirect()->route('install.complete');
    }

    public function complete(Request $request, InstallationState $state): View|RedirectResponse
    {
        if (! $state->isInstalled()) {
            return redirect()->route('install.welcome');
        }

        $completed = $request->session()->pull('installer.completed');

        if (! is_array($completed)) {
            return redirect()->route('admin.login');
        }

        return view('install.complete', $this->baseViewData(
            title: 'Installation Complete',
            subtitle: 'O core foi configurado com sucesso e o admin ja pode ser acessado.',
            step: 'complete',
        ) + [
            'completed' => $completed,
        ]);
    }

    protected function baseViewData(string $title, string $subtitle, string $step): array
    {
        return [
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'currentStep' => $step,
            'steps' => [
                'welcome' => 'Welcome',
                'database' => 'Database',
                'admin' => 'Administrator',
                'requirements' => 'Requirements',
                'complete' => 'Complete',
            ],
        ];
    }
}
