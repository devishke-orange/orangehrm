<?php

namespace OrangeHRM\Scim\Api\Validator;

use Exception;
use Respect\Validation\Rules;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Validator\Exceptions\ValidationEscapableException;
use OrangeHRM\Core\Api\V2\Validator\Exceptions\ValidationException;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Validator;

/**
 * This validator ignores paramKeys that are not defined in the rules
 * I.E., an error is NOT thrown
 */
class ScimValidator extends Validator
{
    /**
     * @inheritDoc
     */
    public static function validate(array $values, ?ParamRuleCollection $rules = null): bool
    {
        $paramRules = $rules->getMap();
        $paramKeys = array_keys($paramRules);
        $values = self::getOnlyNecessaryValues($values, $rules);

        if ($rules->isStrict()) {
            $paramKeys = array_unique(array_merge($paramKeys, array_keys($values)));
        }

        $errorBag = [];
        foreach ($paramKeys as $paramKey) {
            try {
                if (isset($paramRules[$paramKey])) {
                    $paramRule = $paramRules[$paramKey];

                    $compositeClass = $paramRule->getCompositeClass();
                    $paramValidatorRule = new $compositeClass(...$paramRule->getRules());
                    $paramValidator = new Rules\Key($paramKey, $paramValidatorRule);
                    $paramValidator->check(
                        [$paramKey => $values[$paramKey] ?? $paramRule->getDefault()]
                    );
                }
            } catch (ValidationException | Exception $e) {
                if ($e instanceof ValidationEscapableException) {
                    throw $e;
                }
                $errorBag[$paramKey] = $e;
            }
        }
        if (!empty($errorBag)) {
            throw new InvalidParamException($errorBag);
        }

        return true;
    }
}
