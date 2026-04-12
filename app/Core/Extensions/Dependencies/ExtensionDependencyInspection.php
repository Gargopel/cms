<?php

namespace App\Core\Extensions\Dependencies;

class ExtensionDependencyInspection
{
    /**
     * @param  array<int, array<string, mixed>>  $requirements
     * @param  array<int, array<string, mixed>>  $activeDependents
     * @param  array<int, array<string, mixed>>  $warnings
     */
    public function __construct(
        protected array $requirements = [],
        protected array $activeDependents = [],
        protected array $warnings = [],
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function requirements(): array
    {
        return $this->requirements;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function missingRequirements(): array
    {
        return array_values(array_filter(
            $this->requirements(),
            static fn (array $requirement): bool => ($requirement['status'] ?? null) === 'missing',
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function disabledRequirements(): array
    {
        return array_values(array_filter(
            $this->requirements(),
            static fn (array $requirement): bool => ($requirement['status'] ?? null) === 'disabled',
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeDependents(): array
    {
        return $this->activeDependents;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasMissingRequirements(): bool
    {
        return $this->missingRequirements() !== [];
    }

    public function hasDisabledRequirements(): bool
    {
        return $this->disabledRequirements() !== [];
    }

    public function hasActiveDependents(): bool
    {
        return $this->activeDependents() !== [];
    }

    public function toArray(): array
    {
        return [
            'requirements' => $this->requirements(),
            'missing_requirements' => $this->missingRequirements(),
            'disabled_requirements' => $this->disabledRequirements(),
            'active_dependents' => $this->activeDependents(),
            'warnings' => $this->warnings(),
        ];
    }
}
