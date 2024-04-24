<?php

namespace OrangeHRM\Scim\Api\Model;

use OrangeHRM\Entity\User;

class ScimFilterModel extends BaseScimModel
{
    /**
     * @var User[]
     */
    private array $users;

    /**
     * @param User[] $users
     */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $users = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'userName' => $user->getUserName(),
                'name' => [
                    'givenName' => $user->getEmployee()->getFirstName(),
                    'familyName' => $user->getEmployee()->getLastName()
                ]
            ];
        }, $this->users);

        return [
            "schemas" => [
                self::SCIM_FILTER_SCHEMA
            ],
            'totalResults' => count($users),
            "Resources" => $users
        ];
    }
}
