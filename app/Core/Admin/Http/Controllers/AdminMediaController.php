<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Http\Requests\ReplaceMediaAssetRequest;
use App\Core\Admin\Http\Requests\StoreMediaAssetRequest;
use App\Core\Audit\AdminAuditLogger;
use App\Core\Media\MediaManager;
use App\Core\Media\Models\MediaAsset;
use App\Core\Media\MediaUsageInspector;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMediaController extends Controller
{
    public function index(Request $request, MediaManager $media, MediaUsageInspector $usageInspector): View
    {
        $search = trim((string) $request->query('search', ''));
        $kind = trim((string) $request->query('kind', 'all'));

        $assets = MediaAsset::query()
            ->with('uploader')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('original_name', 'like', '%'.$search.'%')
                        ->orWhere('stored_name', 'like', '%'.$search.'%')
                        ->orWhere('path', 'like', '%'.$search.'%')
                        ->orWhere('mime_type', 'like', '%'.$search.'%');
                });
            })
            ->when($kind === 'images', fn ($query) => $query->where('mime_type', 'like', 'image/%'))
            ->when($kind === 'files', fn ($query) => $query->where('mime_type', 'not like', 'image/%'))
            ->latest('created_at')
            ->paginate(24)
            ->withQueryString();

        $usageMap = $usageInspector->usageMapFor($assets->getCollection());

        $assets->getCollection()->transform(function (MediaAsset $asset) use ($usageMap): MediaAsset {
            $asset->setAttribute('usage_summary', $usageMap[$asset->getKey()] ?? []);

            return $asset;
        });

        return view('admin.media.index', [
            'pageTitle' => 'Media Library',
            'pageSubtitle' => 'Upload seguro e listagem operacional minima de arquivos reutilizaveis pelo core e por plugins futuros.',
            'assets' => $assets,
            'policy' => [
                'disk' => $media->disk(),
                'directory' => $media->directory(),
                'max_upload_kilobytes' => $media->maxUploadKilobytes(),
                'allowed_extensions' => $media->allowedExtensions(),
                'allowed_mime_types' => $media->allowedMimeTypes(),
            ],
            'filters' => [
                'search' => $search,
                'kind' => in_array($kind, ['all', 'images', 'files'], true) ? $kind : 'all',
            ],
            'canUploadMedia' => request()->user()?->can('upload_media') ?? false,
            'canManageMedia' => request()->user()?->can('manage_media') ?? false,
        ]);
    }

    public function store(
        StoreMediaAssetRequest $request,
        MediaManager $media,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $result = $media->store(
            file: $request->file('file'),
            uploadedBy: $request->user()?->getAuthIdentifier(),
        );

        $auditLogger->log(
            action: $result->success() ? 'admin.media.uploaded' : 'admin.media.upload_blocked',
            actor: $request->user(),
            target: $result->asset(),
            summary: $result->message(),
            metadata: [
                'disk' => $media->disk(),
                'directory' => $media->directory(),
                'mime_type' => $request->file('file')?->getClientMimeType(),
                'original_name' => $request->file('file')?->getClientOriginalName(),
            ],
            request: $request,
        );

        if (! $result->success()) {
            return redirect()
                ->route('admin.media.index')
                ->withErrors([
                    'media' => $result->message(),
                ]);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('status', $result->message());
    }

    public function replace(
        ReplaceMediaAssetRequest $request,
        MediaAsset $asset,
        MediaManager $media,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $result = $media->replace($asset, $request->file('file'));

        $auditLogger->log(
            action: $result->success() ? 'admin.media.replaced' : 'admin.media.replace_blocked',
            actor: $request->user(),
            target: $asset,
            summary: $result->message(),
            metadata: [
                'asset_id' => $asset->getKey(),
                'path' => $asset->path,
                'mime_type' => $request->file('file')?->getClientMimeType(),
                'original_name' => $request->file('file')?->getClientOriginalName(),
                'reasons' => $result->reasons(),
            ],
            request: $request,
        );

        if (! $result->success()) {
            return redirect()
                ->route('admin.media.index')
                ->withErrors([
                    'media' => $result->message(),
                ]);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('status', $result->message());
    }

    public function destroy(
        Request $request,
        MediaAsset $asset,
        MediaManager $media,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('manage_media') ?? false, 403);

        $result = $media->delete($asset);

        $auditLogger->log(
            action: $result->success() ? 'admin.media.deleted' : 'admin.media.delete_blocked',
            actor: $request->user(),
            target: $asset,
            summary: $result->message(),
            metadata: [
                'asset_id' => $asset->getKey(),
                'path' => $asset->path,
                'reasons' => $result->reasons(),
            ],
            request: $request,
        );

        if (! $result->success()) {
            return redirect()
                ->route('admin.media.index')
                ->withErrors([
                    'media' => $result->message().' '.implode(' | ', $result->reasons()),
                ]);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('status', $result->message());
    }
}
