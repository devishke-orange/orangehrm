<?php

namespace OrangeHRM\Scim\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

abstract class BaseScimModel implements Normalizable
{
    protected const SCIM_FILTER_SCHEMA = "urn:ietf:params:scim:api:messages:2.0:ListResponse";
    protected const SCIM_USER_SCHEMA = "urn:ietf:params:scim:schemas:core:2.0:User";
    protected const SCIM_ERROR_SCHEMA = "urn:ietf:params:scim:api:messages:2.0:Error";
}
