<?php

namespace App\Core\Extensions\Validation;

use App\Core\Extensions\Enums\ExtensionType;

class ExtensionManifestValidator
{
    public function validate(array $data, ExtensionType $type): array
    {
        $errors = [];

        foreach (['name', 'slug', 'description', 'version', 'author'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[] = sprintf('Field [%s] is required and must be a non-empty string.', $field);
            }
        }

        if (isset($data['slug']) && is_string($data['slug']) && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = 'Field [slug] must use lowercase letters, numbers and hyphens only.';
        }

        if (isset($data['version']) && is_string($data['version']) && ! $this->isValidVersion($data['version'])) {
            $errors[] = 'Field [version] must use semantic versioning, for example [1.0.0].';
        }

        if (! isset($data['core']) || ! is_array($data['core'])) {
            $errors[] = 'Field [core] is required and must be an object.';

            return $errors;
        }

        if (! isset($data['core']['min']) || ! is_string($data['core']['min']) || trim($data['core']['min']) === '') {
            $errors[] = 'Field [core.min] is required and must be a non-empty string.';
        } elseif (! $this->isValidVersion($data['core']['min'])) {
            $errors[] = 'Field [core.min] must use semantic versioning, for example [0.1.0].';
        }

        if (isset($data['core']['max']) && (! is_string($data['core']['max']) || ! $this->isValidVersion($data['core']['max']))) {
            $errors[] = 'Field [core.max] must use semantic versioning when present.';
        }

        if (
            isset($data['core']['min'], $data['core']['max']) &&
            is_string($data['core']['min']) &&
            is_string($data['core']['max']) &&
            $this->isValidVersion($data['core']['min']) &&
            $this->isValidVersion($data['core']['max']) &&
            version_compare($data['core']['min'], $data['core']['max'], '>')
        ) {
            $errors[] = 'Field [core.max] must be greater than or equal to [core.min].';
        }

        if ($type === ExtensionType::Plugin && isset($data['provider']) && (! is_string($data['provider']) || trim($data['provider']) === '')) {
            $errors[] = 'Field [provider] must be a non-empty string when present.';
        }

        return $errors;
    }

    protected function isValidVersion(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version) === 1;
    }
}
