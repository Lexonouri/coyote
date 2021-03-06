<?php

namespace Coyote\Http\Validators;

use Coyote\Services\Parser\Helpers\City;
use Illuminate\Validation\Validator;

class CityValidator
{
    const REGEXP = '^([a-zA-Z\x{0080}-\x{024F}]+(?:. |-| |\'))*[a-zA-Z\x{0080}-\x{024F}]*$';

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @param Validator $validator
     * @return bool
     */
    public function validateCity($attribute, $value, $parameters, $validator)
    {
        $grabber = new City();

        foreach ($grabber->grab($value) as $city) {
            if (!preg_match('/' . self::REGEXP . '/u', trim($city))) {
                if ($validator instanceof Validator) {
                    $validator->addReplacer('city', function ($message) use ($city) {
                        return str_replace(':value', $city, $message);
                    });
                }

                return false;
            }
        }

        return true;
    }
}
