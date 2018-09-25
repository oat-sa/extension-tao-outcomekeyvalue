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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoAltResultStorage\scripts\update;

use oat\tao\scripts\update\OntologyUpdater;
use taoAltResultStorage_models_classes_KeyValueResultStorage as KeyValueResultStorage;
/**
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater {

	/**
     * 
     * @param string $currentVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion) {
        

        $this->skip('1.0','2.1.0');

        if ($this->isVersion('2.1.0')) {
            OntologyUpdater::syncModels();
            $this->getServiceManager()->register(KeyValueResultStorage::SERVICE_ID, new KeyValueResultStorage([
                KeyValueResultStorage::OPTION_PERSISTENCE => 'keyValueResult'
            ]));
            $this->setVersion('2.2.0');
        }
        
        $this->skip('2.2.0', '5.2.1');
    }
}
