<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validation\Validator;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredFieldThrowsWhenMissing(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['name' => ''], ['name' => 'required|string'])->validate();
    }

    public function testRequiredFieldPassesWhenPresent(): void
    {
        $result = Validator::make(['name' => 'Ada Lovelace'], ['name' => 'required|string|min:2'])->validate();

        $this->assertSame('Ada Lovelace', $result['name']);
    }

    public function testEmailRuleRejectsInvalidAddress(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['email' => 'not-an-email'], ['email' => 'required|email'])->validate();
    }

    public function testEmailRuleAcceptsValidAddress(): void
    {
        $result = Validator::make(['email' => 'user@example.com'], ['email' => 'required|email'])->validate();

        $this->assertSame('user@example.com', $result['email']);
    }

    public function testMinLengthRuleRejectsShortValue(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['password' => 'short'], ['password' => 'required|string|min:8'])->validate();
    }

    /**
     * @dataProvider validBooleanValueProvider
     */
    public function testBooleanRuleAcceptsNativeBooleansAndNumericStrings(mixed $value): void
    {
        $result = Validator::make(['flag' => $value], ['flag' => 'nullable|boolean'])->validate();

        $this->assertSame($value, $result['flag']);
    }

    /**
     * @return list<list<mixed>>
     */
    public static function validBooleanValueProvider(): array
    {
        return [[true], [false], [0], [1], ['0'], ['1']];
    }

    public function testBooleanRuleRejectsAmbiguousStrings(): void
    {
        // "true"/"false" as literal strings are deliberately rejected
        // rather than silently cast — see SECURITY.md's note on the
        // WebhookController::toggle boolean-coercion fix for why
        // ambiguous truthy/falsy strings are dangerous to auto-cast.
        $this->expectException(ValidationException::class);
        Validator::make(['flag' => 'true'], ['flag' => 'required|boolean'])->validate();
    }

    public function testNullableFieldSkipsValidationWhenAbsent(): void
    {
        $result = Validator::make([], ['nickname' => 'nullable|string|min:2'])->validate();

        $this->assertArrayNotHasKey('nickname', $result);
    }

    public function testInRuleRestrictsToAllowedValues(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['role' => 'superuser'], ['role' => 'required|in:admin,editor,viewer'])->validate();
    }

    public function testInRuleAcceptsAllowedValue(): void
    {
        $result = Validator::make(['role' => 'editor'], ['role' => 'required|in:admin,editor,viewer'])->validate();

        $this->assertSame('editor', $result['role']);
    }

    public function testValidationExceptionCarriesFieldErrors(): void
    {
        try {
            Validator::make(['age' => 'not-a-number'], ['age' => 'required|integer|min:18'])->validate();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('age', $e->getErrors());
        }
    }
}
