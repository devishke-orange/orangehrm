<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Admin\Api;

use OrangeHRM\Admin\Api\Model\SubunitModel;
use OrangeHRM\Admin\Api\Model\SubunitTreeModel;
use OrangeHRM\Admin\Service\CompanyStructureService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Serializer\EndpointCreateResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointDeleteResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointGetAllResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointGetOneResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointUpdateResult;
use OrangeHRM\Entity\Subunit;

class SubunitAPI extends Endpoint implements CrudEndpoint
{
    public const PARAMETER_PARENT_ID = "parentId";
    public const PARAMETER_UNIT_ID = "unitId";
    public const PARAMETER_NAME = "name";
    public const PARAMETER_DESCRIPTION = "description";

    public const FILTER_DEPTH = "depth";
    public const FILTER_MODE = "mode";

    public const MODE_LIST = 'list';
    public const MODE_TREE = 'tree';

    /**
     * @var CompanyStructureService|null
     */
    protected ?CompanyStructureService $companyStructureService = null;

    /**
     * @return CompanyStructureService
     */
    public function getCompanyStructureService(): CompanyStructureService
    {
        if (!($this->companyStructureService instanceof CompanyStructureService)) {
            $this->companyStructureService = new CompanyStructureService();
        }
        return $this->companyStructureService;
    }

    /**
     * @param CompanyStructureService $companyStructureService
     */
    public function setCompanyStructureService(CompanyStructureService $companyStructureService): void
    {
        $this->companyStructureService = $companyStructureService;
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointGetOneResult
    {
        $unitId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $subunit = $this->getCompanyStructureService()->getSubunitById($unitId);
        if (!$subunit instanceof Subunit) {
            throw new RecordNotFoundException();
        }

        return new EndpointGetOneResult(SubunitModel::class, $subunit);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointGetAllResult
    {
        $depth = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::FILTER_DEPTH);
        $mode = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_MODE,
            self::MODE_LIST
        );
        $subunits = $this->getCompanyStructureService()->getSubunitTree($depth);

        $modelClass = SubunitModel::class;
        if ($mode === self::MODE_TREE) {
            $modelClass = SubunitTreeModel::class;
        }

        return new EndpointGetAllResult($modelClass, $subunits);
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointCreateResult
    {
        $parentUnitId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PARENT_ID);
        $parentSubunit = $this->getCompanyStructureService()->getSubunitById($parentUnitId);
        if (!$parentSubunit instanceof Subunit) {
            throw new RecordNotFoundException();
        }

        $subunit = new Subunit();
        $subunit = $this->setParamsToSubunit($subunit);
        $this->getCompanyStructureService()->addSubunit($parentSubunit, $subunit);

        return new EndpointCreateResult(SubunitModel::class, $subunit);
    }

    /**
     * @param Subunit $subunit
     * @return Subunit
     */
    private function setParamsToSubunit(Subunit $subunit): Subunit
    {
        $unitId = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_UNIT_ID);
        $name = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NAME);
        $description = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_DESCRIPTION
        );
        $subunit->setUnitId($unitId);
        $subunit->setName($name);
        $subunit->setDescription($description);
        return $subunit;
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointUpdateResult
    {
        $unitId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $subunit = $this->getCompanyStructureService()->getSubunitById($unitId);
        if (!$subunit instanceof Subunit) {
            throw new RecordNotFoundException();
        }

        $subunit = $this->setParamsToSubunit($subunit);

        $this->getCompanyStructureService()->saveSubunit($subunit);
        return new EndpointUpdateResult(SubunitModel::class, $subunit);
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointDeleteResult
    {
        $unitId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $subunit = $this->getCompanyStructureService()->getSubunitById($unitId);
        if (!$subunit instanceof Subunit) {
            throw new RecordNotFoundException();
        }
        $resultSubunit = clone $subunit;
        $this->getCompanyStructureService()->deleteSubunit($subunit);

        return new EndpointDeleteResult(SubunitModel::class, $resultSubunit);
    }
}