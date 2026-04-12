<?php

namespace App\Core\Admin\Http\Requests;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Settings\CoreSettingsCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(CorePermission::ManageSettings->value);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return app(CoreSettingsCatalog::class)->validationRules('general');
    }
}
