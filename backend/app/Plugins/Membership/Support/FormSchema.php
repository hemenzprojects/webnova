<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MembershipType;
use Illuminate\Validation\Rule;

/**
 * The registration form's structure, stored as JSON on MembershipForm:
 *
 * {
 *   "sections": [
 *     { "title": "Personal Information", "description": "...", "fields": [
 *       { "key": "first_name", "type": "text", "label": "First name",
 *         "required": true, "width": "half", "new_row": false,
 *         "placeholder": "", "help": "", "options": [],
 *         "show_for_types": [],      // membership type ids; empty = everyone
 *         "role": "first_name" }     // marks name/email fields for the admin list
 *     ] }
 *   ],
 *   "submit_text": "Submit & Proceed",
 *   "show_amount": true
 * }
 */
class FormSchema
{
    public const FIELD_TYPES = [
        'text' => 'Short text',
        'textarea' => 'Long text',
        'email' => 'Email',
        'tel' => 'Phone number',
        'number' => 'Number',
        'date' => 'Date',
        'select' => 'Dropdown',
        'radio' => 'Choice (radio buttons)',
        'checkboxes' => 'Multiple choice (checkboxes)',
        'checkbox' => 'Single checkbox (e.g. "I agree")',
        'country' => 'Country',
        'membership_type' => 'Membership type (sets the amount)',
    ];

    /** Types whose answers come from a list of options typed into the builder */
    public const OPTION_TYPES = ['select', 'radio', 'checkboxes'];

    /** Column widths on a 12-column grid */
    public const WIDTHS = [
        'full' => 'Full width',
        'half' => 'Half',
        'third' => 'One third',
        'two_thirds' => 'Two thirds',
        'quarter' => 'One quarter',
    ];

    public const ROLES = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'full_name' => 'Full name',
        'email' => 'Email address',
    ];

    /**
     * Starting form, modelled on a typical association sign-up.
     */
    public static function default(): array
    {
        $f = fn (string $key, string $type, string $label, string $width, array $extra = []) => array_merge([
            'key' => $key, 'type' => $type, 'label' => $label, 'required' => true, 'width' => $width,
            'new_row' => false, 'placeholder' => '', 'help' => '', 'options' => [], 'show_for_types' => [], 'role' => null,
        ], $extra);

        return [
            'sections' => [
                [
                    'title' => 'Personal Information',
                    'description' => 'Provide your personal details in the fields provided. All fields marked (*) are required.',
                    'fields' => [
                        $f('membership_type', 'membership_type', 'Status', 'half'),
                        $f('title', 'select', 'Title', 'half', ['options' => ['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Prof.']]),
                        $f('first_name', 'text', 'First name', 'half', ['role' => 'first_name']),
                        $f('surname', 'text', 'Surname', 'half', ['role' => 'last_name']),
                        $f('gender', 'select', 'Gender', 'half', ['options' => ['Female', 'Male']]),
                        $f('email', 'email', 'Email', 'half', ['role' => 'email', 'new_row' => true]),
                        $f('phone', 'tel', 'Phone Number', 'half'),
                        $f('date_of_birth', 'date', 'Date of Birth', 'half'),
                        $f('country', 'country', 'Country', 'quarter'),
                        $f('state', 'text', 'State/Province', 'quarter'),
                    ],
                ],
            ],
            'submit_text' => 'Submit & Proceed',
            'show_amount' => true,
        ];
    }

    /**
     * Every field, in order, across sections.
     *
     * @return array<int, array>
     */
    public static function fields(array $schema): array
    {
        return collect($schema['sections'] ?? [])->flatMap(fn ($section) => $section['fields'] ?? [])->values()->all();
    }

    public static function fieldByRole(array $schema, string $role): ?array
    {
        return collect(static::fields($schema))->firstWhere('role', $role);
    }

    public static function membershipTypeField(array $schema): ?array
    {
        return collect(static::fields($schema))->firstWhere('type', 'membership_type');
    }

    /**
     * Problems that would make the form unusable, for the builder to show.
     *
     * @return string[]
     */
    public static function problems(array $schema): array
    {
        $fields = static::fields($schema);
        $problems = [];

        if (! $fields) {
            $problems[] = 'Add at least one field.';
        }

        $keys = array_column($fields, 'key');
        foreach (array_unique(array_diff_assoc($keys, array_unique($keys))) as $duplicate) {
            $problems[] = "Two fields use the same key \"{$duplicate}\". Each field needs its own key.";
        }

        if (count(array_filter($fields, fn ($f) => $f['type'] === 'membership_type')) > 1) {
            $problems[] = 'Use only one "Membership type" field.';
        }

        if (! static::fieldByRole($schema, 'email') && ! collect($fields)->firstWhere('type', 'email')) {
            $problems[] = 'Add an Email field so you can contact applicants and send payment receipts.';
        }

        foreach ($fields as $field) {
            if (in_array($field['type'], static::OPTION_TYPES, true) && empty(array_filter($field['options'] ?? []))) {
                $problems[] = "\"{$field['label']}\" needs at least one option.";
            }
        }

        return $problems;
    }

    /**
     * Whether a field applies to the chosen membership type.
     */
    public static function appliesTo(array $field, ?int $typeId): bool
    {
        $only = array_map('intval', $field['show_for_types'] ?? []);

        return ! $only || ($typeId !== null && in_array($typeId, $only, true));
    }

    /**
     * Laravel validation rules for a submission, keyed "answers.{key}".
     * Fields hidden for the chosen membership type are not validated.
     */
    public static function rules(array $schema, ?int $typeId): array
    {
        $rules = [];

        foreach (static::fields($schema) as $field) {
            if (! static::appliesTo($field, $typeId)) {
                continue;
            }

            $required = ! empty($field['required']);
            $base = [$required ? 'required' : 'nullable'];
            $options = array_values(array_filter($field['options'] ?? [], fn ($o) => $o !== null && $o !== ''));

            $typeRules = match ($field['type']) {
                'text' => ['string', 'max:255'],
                'textarea' => ['string', 'max:5000'],
                'email' => ['email', 'max:255'],
                'tel' => ['string', 'max:30', 'regex:/^[0-9+()\-.\s]{5,30}$/'],
                'number' => ['numeric'],
                'date' => ['date'],
                'select', 'radio' => [Rule::in($options)],
                'checkboxes' => ['array'],
                'checkbox' => $required ? ['accepted'] : ['boolean'],
                'country' => [Rule::in(array_values(config('countries', [])))],
                'membership_type' => [Rule::exists(MembershipType::class, 'id')->where('is_active', true)],
                default => ['string', 'max:255'],
            };

            if ($field['type'] === 'checkbox' && $required) {
                $base = [];
            }

            $rules["answers.{$field['key']}"] = array_merge($base, $typeRules);

            if ($field['type'] === 'checkboxes') {
                $rules["answers.{$field['key']}.*"] = [Rule::in($options)];
            }
        }

        return $rules;
    }

    /**
     * Readable attribute names for validation messages ("First name is required").
     */
    public static function attributes(array $schema): array
    {
        return collect(static::fields($schema))
            ->mapWithKeys(fn ($field) => ["answers.{$field['key']}" => $field['label']])
            ->all();
    }

    /**
     * Keep only answers to fields that apply, so stray input is never stored.
     */
    public static function clean(array $schema, array $answers, ?int $typeId): array
    {
        $out = [];
        foreach (static::fields($schema) as $field) {
            if (static::appliesTo($field, $typeId) && array_key_exists($field['key'], $answers)) {
                $value = $answers[$field['key']];
                $out[$field['key']] = $field['type'] === 'checkbox' ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value;
            }
        }

        return $out;
    }

    /**
     * Applicant's display name and email from the fields marked with a role.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function nameAndEmail(array $schema, array $answers): array
    {
        $value = fn (?array $field) => $field ? trim((string) ($answers[$field['key']] ?? '')) : '';

        $name = $value(static::fieldByRole($schema, 'full_name'))
            ?: trim($value(static::fieldByRole($schema, 'first_name')) . ' ' . $value(static::fieldByRole($schema, 'last_name')));

        $emailField = static::fieldByRole($schema, 'email') ?? collect(static::fields($schema))->firstWhere('type', 'email');

        return [$name ?: null, $value($emailField) ?: null];
    }
}
