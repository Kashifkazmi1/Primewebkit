<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Http\Request;
use App\Core\Validation\Validator;

/**
 * Base class for form-request style input validators. Concrete
 * subclasses declare `rules()` (and optionally `messages()`); the
 * controller calls `::validate($request)` to get back a clean,
 * whitelisted array of validated input or have a ValidationException
 * thrown automatically.
 */
abstract class FormRequest
{
    /**
     * @return array<string, string>
     */
    abstract public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function validate(Request $request): array
    {
        $instance = new static();

        return Validator::make($request->allInput(), $instance->rules(), $instance->messages())->validate();
    }
}
