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
 * Copyright (c) 2013-2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

use oat\oatbox\service\ConfigurableService;
use oat\taoResultServer\models\Collection\VariableStorableCollection;
use oat\taoResultServer\models\Entity\ItemVariableStorable;
use oat\taoResultServer\models\Entity\TestVariableStorable;
use oat\taoResultServer\models\Entity\VariableStorable;
use oat\taoResultServer\models\classes\ResultDeliveryExecutionDelete;
use oat\taoResultServer\models\classes\ResultManagement;

/**
 * Implements tao results storage using the configured persistency "taoAltResultStorage"
 *
 *
 *
 * The storage is done on a callId basis (the key). retrieval of all variables pertainign to a callid
 * is done using get or hget for aparticular variable 0(1)
 * The jsondata contains all the observations recorderd with the variable data + context
 *
 * keyPrefixCallId.$callId =>
 * (field)variableIdentifier : json data ,
 * (field)variableIdentifier : json data,
 * ...
 * }
 */
class taoAltResultStorage_models_classes_KeyValueResultStorage extends ConfigurableService
    implements taoResultServer_models_classes_WritableResultStorage, ResultManagement
{
    use ResultDeliveryExecutionDelete;
    const SERVICE_ID = 'taoAltResultStorage/KeyValueResultStorage';

    /** result storage persistence identifier */
    const OPTION_PERSISTENCE = 'persistence_id';

    // prefixes used for keys
    const PREFIX_CALL_ID = 'taoAltResultStorage:callIdVariables'; // keyPrefixCallId.$callId --> variables
    const PREFIX_TESTTAKER = 'taoAltResultStorage:resultsTestTaker'; // keyPrefixTestTaker.$deliveryResultIdentifier -->testtaker
    const PREFIX_DELIVERY = 'taoAltResultStorage:resultsDelivery'; // keyPrefixDelivery.$deliveryResultIdentifier -->testtaker
    const PREFIX_RESULT_ID ='taoAltResultStorage:id';

    /**
     * Property separator string.
     */
    const PROPERTY_SEPARATOR = '_prop_';

    /**
     * @var common_persistence_AdvKeyValuePersistence
     */
    private $persistence;

    /**
     * Initialise the persistence and return it
     *
     * @return common_persistence_AdvKeyValuePersistence
     */
    private function getPersistence()
    {
        if (is_null($this->persistence)) {
            $perisistenceManager = $this->getServiceLocator()->get(common_persistence_Manager::SERVICE_ID);
            $this->persistence = $perisistenceManager->getPersistenceById($this->getOption(self::OPTION_PERSISTENCE));
        }

        return $this->persistence;
    }

    /**
     *
     * @param string $callId
     * @param string $variableIdentifier
     * @param VariableStorable $data
     *            the actual variable-value object,
     */
    private function storeVariableKeyValue($callId, $variableIdentifier, $data)
    {
        $callId = self::PREFIX_CALL_ID . $callId;

        /*
         * seems to be the same complexity, worse if not yet value set for that key to be benchmarked against the general case only
         */
        // Time complexity: O(1)
        $observed = $this->getPersistence()->hExists($callId, $variableIdentifier);
        if (! ($observed)) {
            // Time complexity: O(1)
            $this->getPersistence()->hSet($callId, $variableIdentifier, $this->serializeVariableValue(array(
                $data
            )));
        } else {
            // Time complexity: O(1)
            $variableObservations = $this->unserializeVariableValue($this->getPersistence()->hGet($callId, $variableIdentifier));
            // if (is_array($variableObservations)) {
            $variableObservations[] = $data;
            /*
             * } else { $variableObservations = array($data); }
             */
            // Time complexity: O(1)
            $this->getPersistence()->hSet($callId, $variableIdentifier, $this->serializeVariableValue($variableObservations));
        }
    }

    /**
     * Ids must be delegated on key value persistency as we may want to load balance and keep unique identifier
     */
    public function spawnResult()
    {
        return "id_".$this->getPersistence()->incr(self::PREFIX_RESULT_ID);
    }   
    
    /**
     *
     * @param type $deliveryResultIdentifier
     *            lis_result_sourcedid
     * @param type $test
     *            ignored
     * @param taoResultServer_models_classes_Variable $testVariable            
     * @param type $callIdTest
     *            ignored
     */
    public function storeTestVariable($deliveryResultIdentifier, $test, taoResultServer_models_classes_Variable $testVariable, $callIdTest)
    {
        if (! ($testVariable->isSetEpoch())) {
            $testVariable->setEpoch(microtime());
        }

        $variable = new TestVariableStorable($deliveryResultIdentifier, $test, $testVariable, $callIdTest);

        $this->storeVariableKeyValue($callIdTest, $variable->getIdentifier(), $variable);
    }

    /**
     * @param $deliveryResultIdentifier
     * @param $test
     * @param array $testVariables
     * @param $callIdTest
     */
    public function storeTestVariables($deliveryResultIdentifier, $test, array $testVariables, $callIdTest)
    {
        foreach ($testVariables as $testVariable) {
            $this->storeTestVariable($deliveryResultIdentifier, $test, $testVariable, $callIdTest);
        }
    }
    
    /*
     * retrieve specific parameters from the resultserver to configure the storage
     */
    /*sic*/
    public function configure($callOptions = array())
    {}

    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier)
    {
        $this->getPersistence()->hmSet(self::PREFIX_TESTTAKER . $deliveryResultIdentifier, array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "testTakerIdentifier" => $testTakerIdentifier
        ));
    }

    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier)
    {
        $this->getPersistence()->hmSet(self::PREFIX_DELIVERY . $deliveryResultIdentifier, array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "deliveryIdentifier" => $deliveryIdentifier
        ));
    }

    public function storeItemVariable($deliveryResultIdentifier, $test, $item, taoResultServer_models_classes_Variable $itemVariable, $callIdItem)
    {
        if (! ($itemVariable->isSetEpoch())) {
            $itemVariable->setEpoch(microtime());
        }

        $variable = new ItemVariableStorable($deliveryResultIdentifier, $test, $itemVariable, $item, $callIdItem);

        $this->storeVariableKeyValue($callIdItem, $variable->getIdentifier(), $variable);
    }
    
    public function storeItemVariables($deliveryResultIdentifier, $test, $item, array $itemVariables, $callIdItem)
    {
        foreach ($itemVariables as $itemVariable) {
            $this->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $callIdItem);
        }
    }

    /**
     * @param string|array one or more callIds (item execution identifier)
     * @return array keys as variableIdentifier , values is an array of observations , 
     * each observation is an object with deliveryResultIdentifier, test, taoResultServer_models_classes_Variable variable, callIdTest
     * Array
    (
    [LtiOutcome] => Array
        (
            [0] => stdClass Object
                (
                    [deliveryResultIdentifier] => con-777:::rlid-777:::777777
                    [test] => http://tao26/tao26.rdf#i1402389674744647
                    [variable] => taoResultServer_models_classes_OutcomeVariable Object
                        (
                            [normalMaximum] => 
                            [normalMinimum] => 
                            [value] => MC41
                            [identifier] => LtiOutcome
                            [cardinality] => single
                            [baseType] => float
                            [epoch] => 0.10037600 1402390997
                        )
                    [callIdTest] => http://tao26/tao26.rdf#i14023907995907103
                )

        )

    )
     */
    public function getVariables($callId)
    {
        $variables = [];

        if (is_array($callId)) {
            foreach ($callId as $id) {
                $variables = array_merge($variables, $this->getVariables($id));
            }
        } else {
            $tmpVariables = $this->getPersistence()->hGetAll(self::PREFIX_CALL_ID . $callId);

            foreach ($tmpVariables as $variableIdentifier => $variableObservations) {
                $observations = $this->unserializeVariableValue($variableObservations);
                foreach ($observations as $key => $observation) {
                    $observation->variable = unserialize($observation->variable);
                    $observation->uri = $observation->uri . static::PROPERTY_SEPARATOR . $observation->variable->getIdentifier();
                }

                $variables[$callId . $variableIdentifier] = $observations;
            }

            unset($tmpVariables);
        }

        return $variables;
    }

    /**
     * @param string|array $deliveryResultIdentifier
     * @return array
     */
    public function getDeliveryVariables($deliveryResultIdentifier)
    {
        $variables = [];

        if (is_array($deliveryResultIdentifier)) {
            $deliveryVariables = [];
            foreach ($deliveryResultIdentifier as $id) {
                $deliveryVariables[] = $this->getDeliveryVariables($id);
            }
            $variables = array_merge(...$deliveryVariables);
        } else {
            $keys = $this->getPersistence()->keys(self::PREFIX_CALL_ID . $deliveryResultIdentifier . '.*');
            foreach ($keys as $key) {
                foreach ($this->getVariables(str_replace(self::PREFIX_CALL_ID, '', $key)) as $varId => $variable) {
                    $variables[$variable[0]->uri.$varId] = $variable;
                }
            }
        }
        return $variables;
    }

    public function getVariable($callId, $variableIdentifier)
    {
        $observations = $this->unserializeVariableValue($this->getPersistence()->hGet(self::PREFIX_CALL_ID . $callId, $variableIdentifier));
        foreach ($observations as $key => $observation) {
            $observation->variable = unserialize($observation->variable);
            $observations[$key] = $observation;
        }

        return  $observations;   
    }

    public function getTestTaker($deliveryResultIdentifier)
    {
        $testTaker = $this->getTestTakerArray($deliveryResultIdentifier);

        return $testTaker['testTakerIdentifier'];
    }

    public function getDelivery($deliveryResultIdentifier)
    {
        $delivery = $this->getDeliveryArray($deliveryResultIdentifier);

        return $delivery['deliveryIdentifier'];
    }

    public function getTestTakerArray($deliveryResultIdentifier)
    {
        return $this->getPersistence()->hGetAll(self::PREFIX_TESTTAKER . $deliveryResultIdentifier);
    }

    public function getDeliveryArray($deliveryResultIdentifier)
    {
        return $this->getPersistence()->hGetAll(self::PREFIX_DELIVERY . $deliveryResultIdentifier);
    }

    /**
     * @return array the list of item executions ids (across all results)
     * o(n) do not use real time (postprocessing)
     */

    public function getAllCallIds()
    {
        $keys = $this->getPersistence()->keys(self::PREFIX_CALL_ID . '*');
        array_walk($keys, 'self::subStrPrefix', self::PREFIX_CALL_ID);

        return $keys;
    }
    /**
     * @return array each element is a two fields array deliveryResultIdentifier, testTakerIdentifier
     */
    public function getAllTestTakerIds()
    {
        $deliveryResults = array();
        $keys = $this->getPersistence()->keys(self::PREFIX_TESTTAKER . '*');
        array_walk($keys, 'self::subStrPrefix', self::PREFIX_TESTTAKER);
        foreach ($keys as $key) {
            $deliveryResults[$key] = $this->getTestTakerArray($key);
        }

        return $deliveryResults;
    }
    /**
     * @return array each element is a two fields array deliveryResultIdentifier, deliveryIdentifier
     */
    public function getAllDeliveryIds()
    {
        $deliveryResults = array();
        $keys = $this->getPersistence()->keys(self::PREFIX_DELIVERY . '*');
        array_walk($keys, 'self::subStrPrefix', self::PREFIX_DELIVERY);
        foreach ($keys as $key) {
            $deliveryResults[$key] = $this->getDeliveryArray($key);
        }

        return $deliveryResults;
    }

    /**
     * helper
     */
    private function subStrPrefix(&$value, $key, $prefix)
    {
        $value = str_replace($prefix, '', $value);
    }

    /**
     * Get only one property from a variable
     * @param string $variableId on which we want the property
     * @param string $property to retrieve
     * @return int|string the property retrieved
     */
    public function getVariableProperty($variableId, $property)
    {
        list($itemUri, $propertyName) = $this->extractResultVariableProperty($variableId);
        $response =  $this->unserializeVariableValue(
            $this->getPersistence()->hGet(
                self::PREFIX_CALL_ID.$itemUri,
                $propertyName
            )
        );
        $variable = unserialize($response[0]->variable);

        $getter = 'get'.ucfirst($property);
        if (method_exists($variable, $getter)) {
            return $variable->$getter();
        }

        return '';
    }

    /**
     * Returns the variable property key from the absolute variable key.
     *
     * @param string $variableId
     *
     * @return array
     */
    public function extractResultVariableProperty($variableId)
    {
        $variableIds = explode('http://',$variableId);
        $parts = explode(static::PROPERTY_SEPARATOR, $variableIds[2]);

        $itemUri = $variableIds[0] . 'http://' . $parts[0];
        $propertyName = empty($parts[1]) ? 'RESPONSE' : $parts[1];

        return [
            $itemUri,
            $propertyName
        ];
    }

    /**
     * @todo Only works for QTI Tests, fix this in a more generic way
     * 
     * (non-PHPdoc)
     * @see \oat\taoResultServer\models\classes\ResultManagement::getRelatedItemCallIds()
     */
    public function getRelatedItemCallIds($deliveryResultIdentifier)
    {
        $keys = $this->getPersistence()->keys(self::PREFIX_CALL_ID . $deliveryResultIdentifier . '.*');
        array_walk($keys, 'self::subStrPrefix', self::PREFIX_CALL_ID);

        return $keys;
    }

    /**
     * @todo Only works for QTI Tests, fix this in a more generic way
     * 
     * (non-PHPdoc)
     * @see \oat\taoResultServer\models\classes\ResultManagement::getRelatedTestCallIds()
     */
    public function getRelatedTestCallIds($deliveryResultIdentifier)
    {
        $keys = $this->getPersistence()->keys(self::PREFIX_CALL_ID . $deliveryResultIdentifier);
        array_walk($keys, 'self::subStrPrefix', self::PREFIX_CALL_ID);

        return $keys;
    }


    public function getResultByDelivery($delivery, $options = array())
    {
        $returnValue = array();
        $keys = $this->getPersistence()->keys(self::PREFIX_DELIVERY . '*');
        array_walk($keys, 'self::subStrPrefix', self::PREFIX_DELIVERY);
        foreach ($keys as $key) {
            $deliveryExecution = $this->getDelivery($key);
            if(empty($delivery) || in_array($deliveryExecution,$delivery)){
                $returnValue[] = array(
                    "deliveryResultIdentifier" => $key,
                    "testTakerIdentifier" => $this->getTestTaker($key),
                    "deliveryIdentifier" => $deliveryExecution
                );
            }
        }

        return $returnValue;
    }

    public function countResultByDelivery($delivery)
    {
        $count = 0;
        $keys = $this->getPersistence()->keys(self::PREFIX_DELIVERY . '*');
        array_walk($keys, 'self::subStrPrefix', self::PREFIX_DELIVERY);
        foreach ($keys as $key) {
            $deliveryExecution = $this->getDelivery($key);
            if(empty($delivery) || in_array($deliveryExecution,$delivery)){
                $count++;
            }
        }

        return $count;
    }


    /**
     * Remove the result and all the related variables
     * @param string $deliveryResultIdentifier The identifier of the delivery execution
     * @return boolean if the deletion was successful or not
     */
    public function deleteResult($deliveryResultIdentifier)
    {
        $return = true;
        foreach ($this->getRelatedTestCallIds($deliveryResultIdentifier) as $key) {
            $return = $return && $this->getPersistence()->del(self::PREFIX_CALL_ID . $key);
        }
        foreach ($this->getRelatedItemCallIds($deliveryResultIdentifier) as $key) {
            $return = $return && $this->getPersistence()->del(self::PREFIX_CALL_ID . $key);
        }
        $return = $return && $this->getPersistence()->del(self::PREFIX_DELIVERY . $deliveryResultIdentifier);
        $return = $return && $this->getPersistence()->del(self::PREFIX_TESTTAKER . $deliveryResultIdentifier);

        return $return;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function unserializeVariableValue($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     * @return string
     */
    protected function serializeVariableValue($value)
    {
        return json_encode($value);
    }
}
