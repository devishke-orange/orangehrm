<?php

namespace OrangeHRM\Scim\Api\Model;

use OrangeHRM\Entity\User;

class ScimUserModel extends BaseScimModel
{
    /**
     * @var User
     */
    private User $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function toArray(): array
    {
        $now = new \DateTime();

        return [
            "schemas" => [
                self::SCIM_USER_SCHEMA
            ],
            "id" => $this->user->getId(),
            "meta" => [
                "resourceType" => "User",
                "created" => $now->format('Y-m-d\TH:i:s.000\Z'),
                "lastModified" => $now->format('Y-m-d\TH:i:s.000\Z'),
            ],
            "userName" => $this->user->getUserName(),
            "name" => [
                "familyName" => $this->user->getEmployee()->getLastName(),
                "givenName" => $this->user->getEmployee()->getFirstName(),
            ],
        ];
    }
}
