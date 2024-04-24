<?php

namespace OrangeHRM\Scim\Api;

use OrangeHRM\Admin\Dto\UserSearchFilterParams;
use OrangeHRM\Admin\Traits\Service\UserServiceTrait;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\LoggerTrait;
use OrangeHRM\Core\Traits\Service\TextHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\User;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;
use OrangeHRM\Scim\Api\Model\ScimErrorModel;
use OrangeHRM\Scim\Api\Model\ScimFilterModel;
use OrangeHRM\Scim\Api\Model\ScimUserModel;

class ScimUserAPI extends Endpoint implements CrudEndpoint
{
    use UserServiceTrait;
    use EmployeeServiceTrait;
    use TextHelperTrait;
    use LoggerTrait;

    public const FILTER_PARAM = 'filter';
    public const PARAMETER_USERNAME = 'userName';
    public const PARAMETER_NAME = 'name';
    public const PARAMETER_FAMILY_NAME = 'familyName';
    public const PARAMETER_GIVEN_NAME = 'givenName';
    public const PARAMETER_SCHEMAS = 'schemas';
    public const PARAMETER_META = 'meta';
    public const PARAMETER_ACTIVE = 'active';
    public const PARAMETER_OPERATIONS = 'Operations';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $filter = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_PARAM
        );

        $users = [];

        if (null !== $filter && $this->getTextHelper()->strStartsWith($filter, "userName eq")) {
            $userName = trim(explode(' ', $filter)[2], '"');
            $userSearchFilterParams = new UserSearchFilterParams();
            $userSearchFilterParams->setUserName($userName);
            $users = $this->getUserService()->searchSystemUsers($userSearchFilterParams);
        }

        return new EndpointResourceResult(ScimFilterModel::class, $users);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_PARAM)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $this->getLogger()->info($this->getRequest()->getHttpRequest()->getContent());

        $userName = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_USERNAME);
        $name = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NAME);

        $firstName = $name[self::PARAMETER_GIVEN_NAME] ?? null;
        $lastName = $name[self::PARAMETER_FAMILY_NAME] ?? null;

        // HANDLE null first name last name

        $employee = new Employee();
        $employee->setFirstName($firstName);
        $employee->setLastName($lastName);

        $employee = $this->getEmployeeService()->saveEmployee($employee);

        $user = new User();
        $user->setUserName($userName);
        $user->setEmployee($employee);
        $user->getDecorator()->setUserRoleById(2);

        $user = $this->getUserService()->saveSystemUser($user);

        return new EndpointResourceResult(ScimUserModel::class, $user);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_USERNAME,
                new Rule(Rules::STRING_TYPE)
            ),
            new ParamRule(
                self::PARAMETER_NAME,
                new Rule(Rules::EACH, [
                    new Rules\Composite\AllOf(
                        new Rule(Rules::STRING_TYPE)
                    )
                ])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_SCHEMAS)),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_ACTIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_META)),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );

        $user = $this->getUserService()->getSystemUser($id);

        if (null !== $user && 'Admin' !== $user->getUserRole()->getName()) {
            $user->setDeleted(true);
            $this->getUserService()->saveSystemUser($user);
        }

        return new EndpointResourceResult(ScimErrorModel::class, null);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );

        $user = $this->getUserService()->getSystemUser($id);

        if (null === $user) {
            return new EndpointResourceResult(ScimErrorModel::class, null);
        }

        return new EndpointResourceResult(ScimUserModel::class, $user);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );

        $user = $this->getUserService()->getSystemUser($id);

        if (null === $user) {
            return new EndpointResourceResult(ScimErrorModel::class, null);
        }

        $operations = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_OPERATIONS
        );

        $employee = $user->getEmployee();

        foreach ($operations as $operation) {
            if ('Replace' === $operation['op']) {
                ['path' => $path, 'value' => $value] = $operation;

                switch ($path) {
                    case 'userName':
                        $user->setUserName($value);
                        break;
                    case 'name.familyName':
                        $employee->setLastName($value);
                        break;
                    case 'name.givenName':
                        $employee->setFirstName($value);
                        break;
                }
            }
        }

        $this->getUserService()->saveSystemUser($user);
        $this->getEmployeeService()->saveEmployee($employee);

        return new EndpointResourceResult(ScimUserModel::class, $user);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_OPERATIONS,
                new Rule(Rules::ARRAY_TYPE)
            )
        );
    }
}
