<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.users.index') }}" variant="secondary">Back To Users</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if ($formMethod !== 'POST')
            @method($formMethod)
        @endif

        <div class="grid grid--two">
            <x-admin.glass-card title="User Profile" subtitle="Dados basicos do usuario governado pelo core.">
                <div class="form-grid form-grid--two">
                    <div class="field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                </div>

                <div class="form-grid form-grid--two" style="margin-top: 16px;">
                    <div class="field">
                        <label for="password">Password {{ $user->exists ? '(leave blank to keep current)' : '' }}</label>
                        <input id="password" name="password" type="password" {{ $user->exists ? '' : 'required' }}>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" {{ $user->exists ? '' : 'required' }}>
                    </div>
                </div>
            </x-admin.glass-card>

            <x-admin.glass-card title="Role Assignment" subtitle="Atribuicao de cargos so aparece para quem pode governar cargos do sistema.">
                @if ($canManageRoles)
                    <div class="checkbox-grid">
                        @foreach ($roles as $role)
                            <label class="checkbox-card" for="role-{{ $role->id }}">
                                <input id="role-{{ $role->id }}" name="role_ids[]" type="checkbox" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $assignedRoleIds)))>
                                <div>
                                    <strong>{{ $role->name }}</strong>
                                    <span class="subtle">{{ $role->slug }}</span><br>
                                    <span class="stat-note">{{ $role->users_count }} users, {{ $role->permissions_count }} permissions</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        Seu usuario atual nao possui permissao para gerenciar cargos. Os dados basicos podem ser atualizados, mas os cargos permanecem inalterados.
                    </div>
                @endif
            </x-admin.glass-card>
        </div>

        <div class="actions-row" style="margin-top: 20px;">
            <button type="submit" class="admin-button admin-button--primary">Save User</button>
            <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
