<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Http\Requests\StoreMediaAssetRequest;
use App\Core\Audit\AdminAuditLogger;
use App\Core\Media\MediaManager;
use App\Core\Media\Models\MediaAsset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminMediaController extends Controller
{
    public function index(MediaManager $media): View
    {
        return view('admin.media.index', [
            'pageTitle' => 'Media Library',
            'pageSubtitle' => 'Upload seguro e listagem operacional minima de arquivos reutilizaveis pelo core e por plugins futuros.',
            'assets' => MediaAsset::query()
                ->with('uploader')
                ->latest('created_at')
                ->paginate(24),
            'policy' => [
                'disk' => $media->disk(),
                'directory' => $media->directory(),
                'max_upload_kilobytes' => $media->maxUploadKilobytes(),
                'allowed_extensions' => $media->allowedExtensions(),
                'allowed_mime_types' => $media->allowedMimeTypes(),
            ],
            'canUploadMedia' => request()->user()?->can('upload_media') ?? false,
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
}
