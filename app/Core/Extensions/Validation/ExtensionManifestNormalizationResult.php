<?php

namespace App\Core\Extensions\Validation;

class ExtensionManifestNormalizationResult
{
    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        protected array $normalized,
        protected array $errors = [],
        protected array $warnings = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalized(): array
    {
        return $this->normalized;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
