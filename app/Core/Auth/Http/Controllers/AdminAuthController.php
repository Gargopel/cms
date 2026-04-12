<?php

namespace App\Core\Auth\Http\Controllers;

use App\Core\Audit\AdminAuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'pageTitle' => 'Admin Login',
            'pageSubtitle' => 'Acesso administrativo do core protegido por autenticacao e autorizacao baseadas em papeis e permissoes.',
        ]);
    }

    public function store(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: (bool) $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'As credenciais informadas nao permitem acesso ao admin.',
            ]);
        }

        $request->session()->regenerate();

        $auditLogger->log(
            action: 'admin.auth.login',
            actor: $request->user(),
            target: $request->user(),
            summary: 'Administrative login completed.',
            metadata: [
                'remember' => (bool) $request->boolean('remember'),
            ],
            request: $request,
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();

        $auditLogger->log(
            action: 'admin.auth.logout',
            actor: $user,
            target: $user,
            summary: 'Administrative logout completed.',
            request: $request,
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
