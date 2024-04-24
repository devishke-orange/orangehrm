<?php

namespace OrangeHRM\Scim\Api\Model;

class ScimErrorModel extends BaseScimModel
{
    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $now = new \DateTime();

        return [
            "schemas" => [
                self::SCIM_ERROR_SCHEMA
            ],
            "id" => $now->format("YmdHis") . "-" . mt_rand(10000, 99999),
            "status" => "404"
        ];
    }
}
