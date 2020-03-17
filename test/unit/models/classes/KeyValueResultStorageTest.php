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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace test\unit\models\classes;

use oat\generis\test\TestCase;

use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use \taoAltResultStorage_models_classes_KeyValueResultStorage as KeyValueResultStorage;

/**
 * Class KeyValueResultStorageTest
 * @package test\unit\models\classes
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class KeyValueResultStorageTest extends TestCase
{
    public function testStoreItemVariable()
    {
        $storage = $this->getStorage();
        $storage->storeItemVariable('de_id#1', 'test_id#1', 'item_id#1', $this->getVariable(1), 'call_id_item#1');
        $storage->storeItemVariable('de_id#1', 'test_id#1', 'item_id#2', $this->getVariable(2), 'call_id_item#1');
        $this->assertCount(2, $storage->getVariables('call_id_item#1'));
    }

    public function testStoreItemVariableException()
    {
        $this->expectException(DuplicateVariableException::class);
        $variable = $this->getVariable(1);
        $storage = $this->getStorage();
        $storage->storeItemVariable('de_id#1', 'test_id#1', 'item_id#1', $variable, 'call_id_item#1');
        $storage->storeItemVariable('de_id#1', 'test_id#1', 'item_id#1', $variable, 'call_id_item#1');
    }

    /**
     * @param $id
     * @return \taoResultServer_models_classes_OutcomeVariable
     * @throws \common_exception_InvalidArgumentType
     */
    private function getVariable($id)
    {
        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'ItemIdentifier#'.$id;
        $value = 'MyValue';

        $itemVariable = new \taoResultServer_models_classes_OutcomeVariable();
        $itemVariable->setBaseType($baseType);
        $itemVariable->setCardinality($cardinality);
        $itemVariable->setIdentifier($identifier);
        $itemVariable->setValue($value);
        $itemVariable->setEpoch(microtime());

        return $itemVariable;
    }

    /**
     * @return KeyValueResultStorage
     */
    private function getStorage()
    {
        $storage = new KeyValueResultStorage([
            KeyValueResultStorage::OPTION_PERSISTENCE => 'test',
        ]);

        $persistenceManager = new \common_persistence_Manager([
            'persistences' => [
                'test' => [
                    'driver' => 'no_storage_adv'
                ],
            ]
        ]);

        $sl = $this->getServiceLocatorMock([
            \common_persistence_Manager::SERVICE_ID => $persistenceManager,
            KeyValueResultStorage::SERVICE_ID => $storage
        ]);
        $storage->setServiceLocator($sl);

        return $storage;
    }

}
