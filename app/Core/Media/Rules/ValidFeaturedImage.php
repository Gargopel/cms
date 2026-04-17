<?php

namespace App\Core\Media\Rules;

use App\Core\Media\Models\MediaAsset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidFeaturedImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $asset = MediaAsset::query()->find($value);

        if (! $asset instanceof MediaAsset) {
            $fail('The selected featured image does not exist.');

            return;
        }

        if (! $asset->isImage()) {
            $fail('The selected media asset cannot be used as a featured image.');
        }
    }
}
