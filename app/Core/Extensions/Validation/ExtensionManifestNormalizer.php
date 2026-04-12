<?php

namespace App\Core\Extensions\Validation;

use App\Core\Extensions\Capabilities\ExtensionCapabilityCatalog;
use App\Core\Extensions\Enums\ExtensionType;

class ExtensionManifestNormalizer
{
    public function __construct(
        protected ExtensionCapabilityCatalog $capabilities,
    ) {
    }

    public function normalize(array $data, ExtensionType $type): ExtensionManifestNormalizationResult
    {
        $errors = [];
        $warnings = [];

        $normalized = [
            'type' => $type->value,
            'name' => $this->requiredString($data, 'name', $errors),
            'slug' => $this->requiredString($data, 'slug', $errors),
            'description' => $this->requiredString($data, 'description', $errors),
            'version' => $this->requiredString($data, 'version', $errors),
            'author' => $this->requiredString($data, 'author', $errors),
            'vendor' => $this->optionalString($data, 'vendor', $warnings),
            'core' => [],
            'provider' => null,
            'critical' => false,
            'requires' => [],
            'capabilities' => [],
            'permissions' => [],
        ];

        if ($normalized['slug'] !== null && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized['slug']) !== 1) {
            $errors[] = 'Field [slug] must use lowercase letters, numbers and hyphens only.';
        }

        if ($normalized['version'] !== null && ! $this->isValidVersion($normalized['version'])) {
            $errors[] = 'Field [version] must use semantic versioning, for example [1.0.0].';
        }

        if (! isset($data['core']) || ! is_array($data['core'])) {
            $errors[] = 'Field [core] is required and must be an object.';
        } else {
            $min = $data['core']['min'] ?? null;
            $max = $data['core']['max'] ?? null;

            if (! is_string($min) || trim($min) === '') {
                $errors[] = 'Field [core.min] is required and must be a non-empty string.';
            } else {
                $normalized['core']['min'] = trim($min);

                if (! $this->isValidVersion($normalized['core']['min'])) {
                    $errors[] = 'Field [core.min] must use semantic versioning, for example [0.1.0].';
                }
            }

            if ($max !== null) {
                if (! is_string($max) || ! $this->isValidVersion(trim($max))) {
                    $errors[] = 'Field [core.max] must use semantic versioning when present.';
                } else {
                    $normalized['core']['max'] = trim($max);
                }
            }

            if (
                isset($normalized['core']['min'], $normalized['core']['max']) &&
                version_compare($normalized['core']['min'], $normalized['core']['max'], '>')
            ) {
                $errors[] = 'Field [core.max] must be greater than or equal to [core.min].';
            }
        }

        $provider = $this->optionalString($data, 'provider', $warnings);

        if ($type === ExtensionType::Plugin) {
            $normalized['provider'] = $provider;
        }

        $normalized['critical'] = $this->normalizeBoolean($data['critical'] ?? false, 'critical', $warnings);
        $normalized['requires'] = $this->normalizeRequires($data['requires'] ?? [], $warnings);
        $normalized['capabilities'] = $this->normalizeCapabilities($data['capabilities'] ?? [], $warnings);
        $normalized['permissions'] = $this->normalizePermissions($data['permissions'] ?? [], $type, $normalized['slug'], $warnings);

        return new ExtensionManifestNormalizationResult(
            normalized: $normalized,
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * @param  array<int, string>  &$errors
     */
    protected function requiredString(array $data, string $field, array &$errors): ?string
    {
        if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
            $errors[] = sprintf('Field [%s] is required and must be a non-empty string.', $field);

            return null;
        }

        return trim($data[$field]);
    }

    /**
     * @param  array<int, string>  &$warnings
     */
    protected function optionalString(array $data, string $field, array &$warnings): ?string
    {
        if (! array_key_exists($field, $data) || $data[$field] === null) {
            return null;
        }

        if (! is_string($data[$field])) {
            $warnings[] = sprintf('Field [%s] was ignored because it must be a string when present.', $field);

            return null;
        }

        $value = trim($data[$field]);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<int, string>  &$warnings
     */
    protected function normalizeBoolean(mixed $value, string $field, array &$warnings): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return match ($normalized) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off', '' => false,
                default => $this->warnBooleanFallback($field, $warnings),
            };
        }

        if ($value === null) {
            return false;
        }

        $warnings[] = sprintf('Field [%s] was normalized to [false] because the provided type is unsupported.', $field);

        return false;
    }

    /**
     * @param  array<int, string>  &$warnings
     * @return array<int, string>
     */
    protected function normalizeRequires(mixed $value, array &$warnings): array
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed !== '' ? [$trimmed] : [];
        }

        if (! is_array($value)) {
            if ($value !== null) {
                $warnings[] = 'Field [requires] was ignored because it must be an array or string when present.';
            }

            return [];
        }

        $requires = [];

        foreach ($value as $dependency) {
            if (! is_string($dependency) || trim($dependency) === '') {
                $warnings[] = 'One or more values in [requires] were ignored because they are not non-empty strings.';

                continue;
            }

            $requires[] = trim($dependency);
        }

        return array_values(array_unique($requires));
    }

    /**
     * @param  array<int, string>  &$warnings
     * @return array<int|string, mixed>
     */
    protected function normalizeCapabilities(mixed $value, array &$warnings): array
    {
        if ($value === null) {
            return [];
        }

        $entries = [];

        if (is_string($value)) {
            $entries = [$value];
        } elseif (is_array($value)) {
            if (array_is_list($value)) {
                $entries = $value;
            } else {
                foreach ($value as $capability => $enabled) {
                    if (is_int($capability)) {
                        $entries[] = $enabled;

                        continue;
                    }

                    if (! is_string($capability) || trim($capability) === '') {
                        $warnings[] = 'One or more capability keys were ignored because they are not non-empty strings.';

                        continue;
                    }

                    $normalized = $this->normalizeCapabilityBoolean($enabled, $capability, $warnings);

                    if ($normalized === true) {
                        $entries[] = $capability;
                    }
                }
            }
        } else {
            $warnings[] = 'Field [capabilities] was ignored because it must be an array or string when present.';

            return [];
        }

        $capabilities = [];

        foreach ($entries as $capability) {
            if (! is_string($capability) || trim($capability) === '') {
                $warnings[] = 'One or more capabilities were ignored because they are not non-empty strings.';

                continue;
            }

            $normalized = strtolower(trim($capability));

            if (preg_match('/^[a-z0-9_]+(?:-[a-z0-9_]+)*$/', $normalized) !== 1) {
                $warnings[] = sprintf(
                    'Capability [%s] was ignored because it must use lowercase letters, numbers, underscores or hyphens only.',
                    trim($capability),
                );

                continue;
            }

            if (! $this->capabilities->isRecognized($normalized)) {
                $warnings[] = sprintf(
                    'Capability [%s] is not recognized by the core and will be treated as custom metadata.',
                    $normalized,
                );
            }

            $capabilities[] = $normalized;
        }

        return array_values(array_unique($capabilities));
    }

    /**
     * @param  array<int, string>  &$warnings
     */
    protected function normalizeCapabilityBoolean(mixed $value, string $capability, array &$warnings): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return match ($normalized) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off', '' => false,
                default => $this->warnCapabilityFallback($capability, $warnings),
            };
        }

        if ($value === null) {
            return false;
        }

        $warnings[] = sprintf(
            'Capability [%s] was ignored because its flag uses an unsupported type.',
            $capability,
        );

        return false;
    }

    /**
     * @param  array<int, string>  &$warnings
     */
    protected function warnCapabilityFallback(string $capability, array &$warnings): bool
    {
        $warnings[] = sprintf(
            'Capability [%s] was ignored because its flag value is not recognized.',
            $capability,
        );

        return false;
    }

    /**
     * @param  array<int, string>  &$warnings
     */
    protected function warnBooleanFallback(string $field, array &$warnings): bool
    {
        $warnings[] = sprintf('Field [%s] was normalized to [false] because the provided value is not recognized.', $field);

        return false;
    }

    protected function isValidVersion(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version) === 1;
    }

    /**
     * @param  array<int, string>  &$warnings
     * @return array<int, array{slug: string, name: string, description: ?string}>
     */
    protected function normalizePermissions(mixed $value, ExtensionType $type, ?string $extensionSlug, array &$warnings): array
    {
        if ($value === null || $value === []) {
            return [];
        }

        if ($type !== ExtensionType::Plugin) {
            $warnings[] = 'Field [permissions] was ignored because only plugins can declare permissions in this phase.';

            return [];
        }

        if (! is_array($value)) {
            $warnings[] = 'Field [permissions] was ignored because it must be an array when present.';

            return [];
        }

        $permissions = [];
        $seen = [];

        foreach ($value as $index => $permission) {
            if (! is_array($permission)) {
                $warnings[] = sprintf(
                    'Permission entry [%s] was ignored because it must be an object with [slug] and [name].',
                    (string) $index,
                );

                continue;
            }

            $slug = $permission['slug'] ?? null;
            $name = $permission['name'] ?? null;
            $description = $permission['description'] ?? null;

            if (! is_string($slug) || trim($slug) === '') {
                $warnings[] = sprintf(
                    'Permission entry [%s] was ignored because field [slug] is required and must be a non-empty string.',
                    (string) $index,
                );

                continue;
            }

            if (! is_string($name) || trim($name) === '') {
                $warnings[] = sprintf(
                    'Permission [%s] was ignored because field [name] is required and must be a non-empty string.',
                    trim((string) $slug),
                );

                continue;
            }

            $normalizedSlug = strtolower(trim($slug));

            if (preg_match('/^[a-z0-9_]+(?:[.-][a-z0-9_]+)*$/', $normalizedSlug) !== 1) {
                $warnings[] = sprintf(
                    'Permission [%s] was ignored because it must use lowercase letters, numbers, underscores, dots or hyphens only.',
                    trim($slug),
                );

                continue;
            }

            if (isset($seen[$normalizedSlug])) {
                $warnings[] = sprintf(
                    'Permission [%s] was ignored because the slug was declared more than once in the same plugin manifest.',
                    $normalizedSlug,
                );

                continue;
            }

            $seen[$normalizedSlug] = true;

            if ($extensionSlug !== null && str_starts_with($normalizedSlug, $extensionSlug.'.')) {
                $warnings[] = sprintf(
                    'Permission [%s] should declare a local slug only. The plugin prefix [%s.] will be added automatically by the core.',
                    $normalizedSlug,
                    $extensionSlug,
                );

                continue;
            }

            $permissions[] = [
                'slug' => $normalizedSlug,
                'name' => trim($name),
                'description' => is_string($description) && trim($description) !== '' ? trim($description) : null,
            ];
        }

        return $permissions;
    }
}
