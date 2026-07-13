<?php

declare(strict_types=1);

namespace App\Core\Validation;

use App\Core\Database\Connection;
use App\Exceptions\ValidationException;

/**
 * Lightweight, dependency-free request validator.
 *
 * Usage:
 *   $validated = Validator::make($data, [
 *       'email' => 'required|email|max:190|unique:users,email',
 *       'password' => 'required|string|min:8|confirmed',
 *   ])->validate();
 *
 * Throws ValidationException (422) with per-field error messages on
 * failure; returns only the validated (whitelisted) fields on success.
 */
final class Validator
{
    /**
     * @var array<string, list<string>>
     */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules field => pipe-delimited rule string
     * @param array<string, string> $messages optional field.rule => custom message
     */
    private function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $messages = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Run validation and return the validated subset of data, or throw
     * ValidationException containing all accumulated field errors.
     *
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        $validated = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = array_dot_get($this->data, $field);

            $nullable = in_array('nullable', $rules, true);

            if ($value === null && $nullable) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                $this->applyRule($field, $value, $rule);
            }

            if ($value !== null) {
                $validated[$field] = $value;
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return $validated;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $parameter] = str_contains($rule, ':') ? explode(':', $rule, 2) : [$rule, null];

        $passes = match ($name) {
            'required' => $this->isPresent($value),
            'string' => $value === null || is_string($value),
            'numeric' => $value === null || is_numeric($value),
            'integer' => $value === null || filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean' => $value === null || in_array($value, [true, false, 0, 1, '0', '1'], true),
            'email' => $value === null || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false,
            'uuid' => $value === null || (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $value),
            'min' => $this->passesMin($value, (float) $parameter),
            'max' => $this->passesMax($value, (float) $parameter),
            'between' => $this->passesBetween($value, $parameter),
            'in' => $value === null || in_array((string) $value, explode(',', (string) $parameter), true),
            'regex' => $value === null || (bool) preg_match($parameter, (string) $value),
            'confirmed' => $this->passesConfirmed($field, $value),
            'same' => $value === array_dot_get($this->data, (string) $parameter),
            'unique' => $this->passesUnique($value, (string) $parameter),
            'exists' => $this->passesExists($value, (string) $parameter),
            'array' => $value === null || is_array($value),
            'date' => $value === null || strtotime((string) $value) !== false,
            'alpha_dash' => $value === null || (bool) preg_match('/^[a-zA-Z0-9_-]+$/', (string) $value),
            default => true,
        };

        if (!$passes) {
            $this->addError($field, $name, $parameter);
        }
    }

    private function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return true;
    }

    private function passesMin(mixed $value, float $min): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return (float) $value >= $min;
    }

    private function passesMax(mixed $value, float $max): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return (float) $value <= $max;
    }

    private function passesBetween(mixed $value, ?string $parameter): bool
    {
        if ($value === null || $parameter === null) {
            return true;
        }

        [$min, $max] = array_map('floatval', explode(',', $parameter));
        $length = is_string($value) ? mb_strlen($value) : (float) $value;

        return $length >= $min && $length <= $max;
    }

    private function passesConfirmed(string $field, mixed $value): bool
    {
        $confirmation = array_dot_get($this->data, $field . '_confirmation');

        return $value === $confirmation;
    }

    /**
     * unique:table,column[,ignoreId[,ignoreColumn]]
     */
    private function passesUnique(mixed $value, string $parameter): bool
    {
        if ($value === null) {
            return true;
        }

        $parts = explode(',', $parameter);
        $table = $parts[0];
        $column = $parts[1] ?? 'id';
        $ignoreId = $parts[2] ?? null;
        $ignoreColumn = $parts[3] ?? 'id';

        $sql = "SELECT COUNT(*) AS total FROM `{$table}` WHERE `{$column}` = :value AND deleted_at IS NULL";
        $bindings = ['value' => $value];

        if ($ignoreId !== null) {
            $sql .= " AND `{$ignoreColumn}` != :ignore_id";
            $bindings['ignore_id'] = $ignoreId;
        }

        $statement = Connection::get()->prepare($sql);
        $statement->execute($bindings);

        return (int) ($statement->fetch()['total'] ?? 0) === 0;
    }

    /**
     * exists:table,column
     */
    private function passesExists(mixed $value, string $parameter): bool
    {
        if ($value === null) {
            return true;
        }

        [$table, $column] = array_pad(explode(',', $parameter), 2, 'id');

        $statement = Connection::get()->prepare(
            "SELECT COUNT(*) AS total FROM `{$table}` WHERE `{$column}` = :value"
        );
        $statement->execute(['value' => $value]);

        return (int) ($statement->fetch()['total'] ?? 0) > 0;
    }

    private function addError(string $field, string $rule, ?string $parameter): void
    {
        $key = "{$field}.{$rule}";
        $message = $this->messages[$key] ?? $this->defaultMessage($field, $rule, $parameter);

        $this->errors[$field][] = $message;
    }

    private function defaultMessage(string $field, string $rule, ?string $parameter): string
    {
        $label = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => "The {$label} field is required.",
            'string' => "The {$label} must be a string.",
            'numeric' => "The {$label} must be a number.",
            'integer' => "The {$label} must be an integer.",
            'boolean' => "The {$label} must be true or false.",
            'email' => "The {$label} must be a valid email address.",
            'url' => "The {$label} must be a valid URL.",
            'uuid' => "The {$label} must be a valid UUID.",
            'min' => "The {$label} must be at least {$parameter}.",
            'max' => "The {$label} may not be greater than {$parameter}.",
            'between' => "The {$label} must be between {$parameter}.",
            'in' => "The selected {$label} is invalid.",
            'regex' => "The {$label} format is invalid.",
            'confirmed' => "The {$label} confirmation does not match.",
            'same' => "The {$label} does not match.",
            'unique' => "The {$label} has already been taken.",
            'exists' => "The selected {$label} is invalid.",
            'array' => "The {$label} must be an array.",
            'date' => "The {$label} is not a valid date.",
            'alpha_dash' => "The {$label} may only contain letters, numbers, dashes and underscores.",
            default => "The {$label} is invalid.",
        };
    }
}
