<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Dashboard\Controller;

use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Helper\VueControllerHelper;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Framework\Http\Request;

class DashboardController extends AbstractVueController
{
    use AuthUserTrait;
    use ServiceContainerTrait;
    use UserRoleManagerTrait;

    public function preRender(Request $request): void
    {
        $component = new Component('view-dashboard');
        $this->setComponent($component);

        $dataGroups = [
            'dashboard_subunit_widget',
            'dashboard_location_widget',
            'dashboard_leave_widget',
            'dashboard_time_widget',
            'dashboard_buzz_widget',
            'dashboard_employees_on_leave_today_config',
        ];

        $permissions = $this->getUserRoleManagerHelper()
            ->geEntityIndependentDataGroupPermissionCollection($dataGroups);

        $this->getContext()->set(
            VueControllerHelper::PERMISSIONS,
            $permissions->toArray()
        );
    }
}
