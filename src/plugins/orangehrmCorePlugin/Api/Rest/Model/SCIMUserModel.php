<?php

namespace OrangeHRM\Core\Api\Rest\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

class SCIMUserModel implements Normalizable
{
    private string $test;

    public function __construct(string $test)
    {
        $this->test = $test;
    }

    public function toArray(): array
    {
        if ("create" === $this->test) {
            return [
                "schemas" => ["urn:ietf:params:scim:api:messages:2.0:ListResponse"],
                "totalResults" => 1,
                "Resources" => [
                    [
                        "schemas" => [
                            "urn:ietf:params:scim:schemas:core:2.0:User",
                            "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User"
                        ],
                        "externalId" => "0a21f0f2-8d2a-4f8e-bf98-7363c4aed4ef",
                        "userName" => "Test_User_ab6490ee-1e48-479e-a20b-2d77186b5dd1",
                        "active" => true,
                        "emails" => [
                            [
                                "primary" => true,
                                "type" => "work",
                                "value" => "Test_User_fd0ea19b-0777-472c-9f96-4f70d2226f2e@testuser.com"
                            ]
                        ],
                        "meta" => [
                            "resourceType" => "User"
                        ],
                        "name" => [
                            "formatted" => "givenName familyName",
                            "familyName" => "familyName",
                            "givenName" => "givenName"
                        ],
                        "roles" => []
                    ]
                ]
            ];
        }

        if ("empty" === $this->test) {
            return [
                "schemas" => ["urn:ietf:params:scim:api:messages:2.0:ListResponse"],
                "totalResults" => 0,
                "Resources" => [],
                "startIndex" => 1,
                "itemsPerPage" => 20
            ];
        }

        return [
            "schemas" => ["urn:ietf:params:scim:api:messages:2.0:ListResponse"],
            "totalResults" => 1,
            "Resources" => [
                [
                    "schemas" => ["urn:ietf:params:scim:schemas:core:2.0:User"],
                    "id" => "2441309d85324e7793ae",
                    "externalId" => "7fce0092-d52e-4f76-b727-3955bd72c939",
                    "meta" => [
                        "resourceType" => "User",
                        "created" => "2018-03-27T19:59:26.000Z",
                        "lastModified" => "2018-03-27T19:59:26.000Z"
                    ],
                    "userName" => "Test_User_dfeef4c5-5681-4387-b016-bdf221e82081",
                    "name" => [
                        "familyName" => "familyName",
                        "givenName" => "givenName"
                    ],
                    "active" => true,
                    "emails" => [
                        [
                            "value" => "test@example.com",
                            "type" => "work",
                            "primary" => true
                        ]
                    ]
                ]
            ],
            "startIndex" => 1,
            "itemsPerPage" => 1
        ];
    }
}