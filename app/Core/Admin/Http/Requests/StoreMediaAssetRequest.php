<?php

namespace App\Core\Admin\Http\Requests;

use App\Core\Media\MediaManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('upload_media');
    }

    public function rules(): array
    {
        /** @var MediaManager $media */
        $media = app(MediaManager::class);

        return [
            'file' => [
                'required',
                File::types($media->allowedExtensions())
                    ->max($media->maxUploadKilobytes()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Selecione um arquivo para enviar ao media manager.',
        ];
    }
}
