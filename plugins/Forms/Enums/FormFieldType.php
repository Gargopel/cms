<?php

namespace Plugins\Forms\Enums;

use Illuminate\Validation\Rule;

enum FormFieldType: string
{
    case Text = 'text';
    case Email = 'email';
    case Textarea = 'textarea';
    case Select = 'select';
    case Checkbox = 'checkbox';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Email => 'Email',
            self::Textarea => 'Textarea',
            self::Select => 'Select',
            self::Checkbox => 'Checkbox',
        };
    }

    public function supportsOptions(): bool
    {
        return $this === self::Select;
    }

    /**
     * @param  array<int, string>  $options
     * @return array<int, mixed>
     */
    public function submissionRules(bool $required, array $options = []): array
    {
        return match ($this) {
            self::Text => array_filter([$required ? 'required' : 'nullable', 'string', 'max:255']),
            self::Email => array_filter([$required ? 'required' : 'nullable', 'email', 'max:255']),
            self::Textarea => array_filter([$required ? 'required' : 'nullable', 'string', 'max:5000']),
            self::Select => array_filter([$required ? 'required' : 'nullable', 'string', Rule::in($options)]),
            self::Checkbox => $required ? ['accepted'] : ['nullable', 'boolean'],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
