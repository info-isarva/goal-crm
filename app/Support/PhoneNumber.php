<?php

namespace App\Support;

/**
 * Shared mobile validation: exactly 10 digits.
 */
class PhoneNumber
{
    public const MOBILE_REGEX = '/^\d{10}$/';

    public const MOBILE_MESSAGE = 'Mobile number must be exactly 10 digits.';

    /**
     * @return list<string>
     */
    public static function mobileRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'digits:10',
            'regex:'.self::MOBILE_REGEX,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $attribute = 'mobile'): array
    {
        return [
            "{$attribute}.required" => 'Mobile number is required.',
            "{$attribute}.digits" => self::MOBILE_MESSAGE,
            "{$attribute}.regex" => self::MOBILE_MESSAGE,
            "{$attribute}.size" => self::MOBILE_MESSAGE,
        ];
    }
}
