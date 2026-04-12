<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.roles.index') }}" variant="secondary">Back To Roles</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if ($formMethod !== 'POST')
            @method($formMethod)
        @endif

        <div class="grid grid--two">
            <x-admin.glass-card title="Role Profile" subtitle="Definicao do cargo, mantida enxuta para a fase atual do core.">
                <div class="form-grid form-grid--two">
                    <div class="field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" required>
                    </div>
                    <div class="field">
                        <label for="slug">Slug</label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug', $role->slug) }}" {{ $isEditing ? 'readonly' : 'required' }}>
                    </div>
                </div>

                <div class="field" style="margin-top: 16px;">
                    <label for="description">Description</label>
                    <textarea id="description" name="description">{{ old('description', $role->description) }}</textarea>
                </div>

                @if ($isEditing)
                    <div class="notice" style="margin-top: 16px;">
                        O slug do cargo permanece estavel depois da criacao para evitar quebrar referencias e governanca do sistema.
                    </div>
                @endif
            </x-admin.glass-card>

            <x-admin.glass-card title="Permission Assignment" subtitle="Permissoes do cargo. O catalogo ja esta pronto para crescer com registros futuros de plugins.">
                @if ($canManagePermissions)
                    @foreach ($permissions as $scope => $scopePermissions)
                        <div class="stack" style="margin-bottom: 16px;">
                            <strong>{{ strtoupper($scope) }}</strong>
                            <div class="checkbox-grid">
                                @foreach ($scopePermissions as $permission)
                                    <label class="checkbox-card" for="permission-{{ $permission->id }}">
                                        <input id="permission-{{ $permission->id }}" name="permission_ids[]" type="checkbox" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permission_ids', $assignedPermissionIds)))>
                                        <div>
                                            <strong>{{ $permission->name }}</strong>
                                            <span class="subtle">{{ $permission->slug }}</span><br>
                                            <span class="stat-note">{{ $permission->roles_count }} roles currently linked</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        Seu usuario atual nao possui permissao para atribuir permissoes a cargos. Os metadados do cargo ainda podem ser editados.
                    </div>
                @endif
            </x-admin.glass-card>
        </div>

        <div class="actions-row" style="margin-top: 20px;">
            <button type="submit" class="admin-button admin-button--primary">Save Role</button>
            <a href="{{ route('admin.roles.index') }}" class="admin-button admin-button--secondary">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
