<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle" eyebrow="Core Media">
        <span class="status-badge status-badge--neutral">{{ $assets->total() }} assets</span>
    </x-admin.page-header>

    <div class="grid grid--two">
        <x-admin.glass-card title="Upload Media" subtitle="Allowlist simples e segura para arquivos suportados nesta fase do core.">
            @if ($canUploadMedia)
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="stack">
                    @csrf

                    <div class="field">
                        <label for="file">File</label>
                        <input id="file" name="file" type="file" required>
                        <span class="stat-note">Allowed extensions: {{ implode(', ', $policy['allowed_extensions']) }}</span>
                        <span class="stat-note">Max upload: {{ number_format($policy['max_upload_kilobytes'] / 1024, 2) }} MB</span>
                        @error('file')
                            <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="actions-row">
                        <button type="submit" class="admin-button admin-button--primary">Upload File</button>
                    </div>
                </form>
            @else
                <div class="notice">
                    Seu usuario pode consultar a biblioteca, mas nao possui permissao para novos uploads nesta fase.
                </div>
            @endif
        </x-admin.glass-card>

        <div class="stack">
            <x-admin.glass-card title="Storage Policy" subtitle="Parametros operacionais usados pelo media manager do core.">
                <div class="key-value">
                    <div class="key-value-item">
                        <span>Disk</span>
                        <strong>{{ $policy['disk'] }}</strong>
                    </div>
                    <div class="key-value-item">
                        <span>Directory</span>
                        <strong>{{ $policy['directory'] }}</strong>
                    </div>
                    <div class="key-value-item">
                        <span>Allowed MIME Types</span>
                        <strong>{{ count($policy['allowed_mime_types']) }}</strong>
                    </div>
                    <div class="key-value-item">
                        <span>Allowed Extensions</span>
                        <strong>{{ count($policy['allowed_extensions']) }}</strong>
                    </div>
                </div>
            </x-admin.glass-card>

            <x-admin.glass-card title="Scope Notes" subtitle="Limite claro desta primeira camada de media manager.">
                <div class="stack">
                    <div class="notice">Sem editor de imagem, transformacoes avancadas, folders complexos ou integracoes externas nesta etapa.</div>
                    <div class="notice">A biblioteca foi desenhada para reutilizacao futura por plugins como Pages e Blog, sem acoplar o core a um DAM enterprise.</div>
                    <div class="notice">Exclusao bloqueia assets em uso conhecido por featured image de Pages e Blog para evitar quebra operacional silenciosa.</div>
                </div>
            </x-admin.glass-card>
        </div>
    </div>

    <x-admin.glass-card title="Media Library" subtitle="Arquivos persistidos pelo core com metadados basicos e reutilizaveis." style="margin-top: 20px;">
        <form method="GET" action="{{ route('admin.media.index') }}" class="stack" style="margin-bottom: 18px;">
            <div class="grid grid--two">
                <div class="field">
                    <label for="search">Search</label>
                    <input id="search" name="search" type="text" value="{{ $filters['search'] }}" placeholder="Search by file name, path or mime type">
                </div>

                <div class="field">
                    <label for="kind">Type</label>
                    <select id="kind" name="kind">
                        <option value="all" @selected($filters['kind'] === 'all')>All assets</option>
                        <option value="images" @selected($filters['kind'] === 'images')>Images only</option>
                        <option value="files" @selected($filters['kind'] === 'files')>Non-image files</option>
                    </select>
                </div>
            </div>

            <div class="actions-row">
                <button type="submit" class="admin-button admin-button--secondary">Apply Filters</button>
                <a href="{{ route('admin.media.index') }}" class="admin-button admin-button--ghost">Reset</a>
            </div>
        </form>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Uploader</th>
                        <th>Stored Path</th>
                        <th>Usage</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php
                            $usageSummary = $asset->usage_summary ?? [];
                        @endphp
                        <tr>
                            <td>
                                @if ($asset->isImage() && $asset->url())
                                    <div style="margin-bottom: 10px;">
                                        <img src="{{ $asset->url() }}" alt="{{ $asset->original_name }}" style="width: 88px; height: 64px; object-fit: cover; border-radius: 14px; border: 1px solid rgba(148, 170, 226, 0.18);">
                                    </div>
                                @endif

                                <strong>{{ $asset->original_name }}</strong><br>
                                <span class="subtle">{{ $asset->extension ?: 'no extension' }}</span>

                                @if ($asset->url())
                                    <br><a href="{{ $asset->url() }}" class="stat-note" target="_blank" rel="noopener">Open asset</a>
                                @endif
                            </td>
                            <td>{{ $asset->mime_type }}</td>
                            <td>{{ $asset->humanSize() }}</td>
                            <td>{{ $asset->uploader?->name ?? 'system' }}</td>
                            <td><code>{{ $asset->path }}</code></td>
                            <td>
                                @if ($usageSummary !== [])
                                    <span class="status-badge status-badge--warning">In use</span>
                                    <div class="subtle" style="margin-top: 8px;">
                                        {{ implode(' | ', $usageSummary) }}
                                    </div>
                                @else
                                    <span class="status-badge status-badge--success">Unused</span>
                                @endif
                            </td>
                            <td>{{ $asset->created_at?->format('Y-m-d H:i') ?? 'n/a' }}</td>
                            <td>
                                @if ($canManageMedia)
                                    <div class="stack">
                                        <form method="POST" action="{{ route('admin.media.replace', $asset) }}" enctype="multipart/form-data" class="stack">
                                            @csrf
                                            <input name="file" type="file" required>
                                            <button type="submit" class="admin-button admin-button--secondary">Replace</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.media.destroy', $asset) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-button admin-button--ghost">Delete</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="subtle">Read only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">Nenhum arquivo enviado ainda para a biblioteca de midia do core.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($assets, 'links'))
            <div style="margin-top: 18px;" class="subtle">
                Showing {{ $assets->firstItem() ?? 0 }}-{{ $assets->lastItem() ?? 0 }} of {{ $assets->total() }} media assets.
            </div>
        @endif
    </x-admin.glass-card>
</x-layouts.admin>
