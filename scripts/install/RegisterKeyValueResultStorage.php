<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoAltResultStorage\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoResultServer\models\classes\implementation\ResultServerService;

class RegisterKeyValueResultStorage extends InstallAction
{
    public function __invoke($params)
    {
        $service = $this->getServiceManager()->get(ResultServerService::SERVICE_ID);
        if ($service instanceof ResultServerService) {
            $service->setOption(ResultServerService::OPTION_RESULT_STORAGE, \taoAltResultStorage_models_classes_KeyValueResultStorage::SERVICE_ID);
            $this->getServiceManager()->register(ResultServerService::SERVICE_ID, $service);
            
            return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Key Value Result Storage registered!');
        }
        
        return new \common_report_Report(\common_report_Report::TYPE_WARNING, 'Key Value Storage could not be registered! Indeed, the ResultServerService is too old.');
    }
}
