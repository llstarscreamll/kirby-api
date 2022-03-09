<?php

namespace Kirby\Core\Rules;

use DateTime;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsoDateTimeRule.
 *
 * Validates that an string complains with the date ISO format. So the next
 * dates are valid:
 * 2020-01-01T05:00:00.000Z
 * 2020-01-01T05:00:00.000000Z
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class IsoDateTimeRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        return count(array_filter([
            DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $value),
            DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $value),
        ])) > 0;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return trans('core::validation.iso_date_time');
    }
}
