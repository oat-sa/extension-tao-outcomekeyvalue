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
 *
 *
 */

namespace oat\taoAltResultStorage\models\Collection;

use oat\taoAltResultStorage\models\Entity\ItemVariableStorable;
use oat\taoAltResultStorage\models\Entity\TestVariableStorable;
use oat\taoAltResultStorage\models\Entity\VariableStorable;

class VariableStorableCollection
{
    /** @var  string */
    private $identifier;

    /** @var VariableStorable[] */
    private $variables = [];

    /**
     * @param $identifier
     * @param VariableStorable[] $variables
     */
    public function __construct($identifier, array $variables)
    {
        $this->identifier = $identifier;
        $this->variables  = $variables;
    }

    /**
     * @param $callIdTest
     * @param $deliveryResultIdentifier
     * @param $testIdentifier
     * @param \taoResultServer_models_classes_Variable[] $testVariables
     *
     * @return VariableStorableCollection
     */
    public static function createTestVariableCollection($callIdTest, $deliveryResultIdentifier, $testIdentifier, array $testVariables)
    {
        $storableVariables = [];

        foreach ($testVariables as $testVariable) {

            if (!($testVariable->isSetEpoch())) {
                $testVariable->setEpoch(microtime());
            }

            $storableVariables[] = new TestVariableStorable($deliveryResultIdentifier, $testIdentifier, $testVariable, $callIdTest);
        }

        return new self($callIdTest, $storableVariables);
    }

    /**
     * @param $callIdItem
     * @param $item
     * @param $deliveryResultIdentifier
     * @param $testIdentifier
     * @param \taoResultServer_models_classes_Variable[] $testVariables
     *
     * @return VariableStorableCollection
     */
    public static function createItemVariableCollection($callIdItem, $item, $deliveryResultIdentifier, $testIdentifier, array $testVariables)
    {
        $storableVariables = [];

        foreach ($testVariables as $testVariable) {

            if (!($testVariable->isSetEpoch())) {
                $testVariable->setEpoch(microtime());
            }

            $storableVariables[] = new ItemVariableStorable($deliveryResultIdentifier, $testIdentifier, $testVariable, $item, $callIdItem);
        }

        return new self($callIdItem, $storableVariables);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function toStorableArray()
    {
       $data = [];

       foreach ($this->variables as $variable) {
           $data[$variable->getIdentifier()] = json_encode([
               $variable
           ]);
       }

       return $data;
    }
}