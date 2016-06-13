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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

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
class taoAltResultStorage_models_classes_KeyValueResultStorage extends tao_models_classes_GenerisService
    implements taoResultServer_models_classes_WritableResultStorage, \oat\taoResultServer\models\classes\ResultManagement
{
    const KEY_NAMESPACE = "taoAltResultStorage"; 


    
    // prefixes used for keys
    static $keyPrefixCallId = 'taoAltResultStorage:callIdVariables'; // keyPrefixCallId.$callId --> variables
    static $keyPrefixTestTaker = 'taoAltResultStorage:resultsTestTaker'; // keyPrefixTestTaker.$deliveryResultIdentifier -->testtaker
    static $keyPrefixDelivery = 'taoAltResultStorage:resultsDelivery'; // keyPrefixDelivery.$deliveryResultIdentifier -->testtaker
    static $keyPrefixDeliveryExecution = 'taoAltResultStorage:resultsExecutionId'; // keyPrefixExecution.$deliveryResultIdentifier -->testtaker

    static $keyPrefixResultsId='taoAltResultStorage:id';
    
    private $persistence;

    public function __construct()
    {
        parent::__construct();
        $this->persistence = $this->getPersistence();
        common_ext_ExtensionsManager::singleton()->getExtensionById("taoAltResultStorage");
    }

    private function getPersistence()
    {
        $persistence = common_persistence_AdvKeyValuePersistence::getPersistence('keyValueResult');
        // check that persistence is a correct Key VAlue persistence
        return $persistence;
    }

    /**
     *
     * @param type $callId            
     * @param type $variableIdentifier            
     * @param json $data
     *            the actual variable-value object,
     */
    private function storeVariableKeyValue($callId, $variableIdentifier, $data)
    {
        $callId = self::$keyPrefixCallId . $callId;
        /*
         * seems to be the same complexity, worse if not yet value set for that key to be benchmarked against the general case only
         */
        // Time complexity: O(1)
        $observed = $this->persistence->hExists($callId, $variableIdentifier);
        if (! ($observed)) {
            // Time complexity: O(1)
            $this->persistence->hSet($callId, $variableIdentifier, json_encode(array(
                $data
            )));
        } else {
            // Time complexity: O(1)
            $variableObservations = json_decode($this->persistence->hGet($callId, $variableIdentifier));
            // if (is_array($variableObservations)) {
            $variableObservations[] = $data;
            /*
             * } else { $variableObservations = array($data); }
             */
            // Time complexity: O(1)
            $this->persistence->hSet($callId, $variableIdentifier, json_encode($variableObservations));
        }
    }

    /**
     * Ids must be delegated on key value persistency as we may want to load balance and keep unique identifier
     */
    public function spawnResult(){
        return "id_".$this->persistence->incr(self::$keyPrefixResultsId);
        
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
        $data = array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "test" => $test,
            "item" => null,
            "variable" => serialize($testVariable),
            "callIdItem" => null,
            "uri" => $deliveryResultIdentifier.$callIdTest,
            "callIdTest" => $callIdTest,
            "class" => get_class($testVariable)
        );
        $this->storeVariableKeyValue($callIdTest, $testVariable->getIdentifier(), $data);
    }
    /*
     * retrieve specific parameters from the resultserver to configure the storage
     */
    /*sic*/
    public function configure(core_kernel_classes_Resource $resultserver, $callOptions = array())
    {}

    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier)
    {
        $this->persistence->hmSet(self::$keyPrefixTestTaker . $deliveryResultIdentifier, array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "testTakerIdentifier" => $testTakerIdentifier
        ));
    }

    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier)
    {
        $this->persistence->hmSet(self::$keyPrefixDelivery . $deliveryResultIdentifier, array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "deliveryIdentifier" => $deliveryIdentifier
        ));
    }

    public function storeRelatedDeliveryExecution($deliveryResultIdentifier, $executionIdentifier)
    {
        $this->persistence->hmSet(self::$keyPrefixDeliveryExecution . $deliveryResultIdentifier, array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "executionIdentifier" => $executionIdentifier
        ));
    }

    public function storeItemVariable($deliveryResultIdentifier, $test, $item, taoResultServer_models_classes_Variable $itemVariable, $callIdItem)
    {
        if (! ($itemVariable->isSetEpoch())) {
            $itemVariable->setEpoch(microtime());
        }
        $data = array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "test" => $test,
            "item" => $item,
            "variable" => serialize($itemVariable),
            "callIdItem" => $callIdItem,
            "callIdTest" => null,
            "uri" => $deliveryResultIdentifier.$callIdItem,
            "class" => get_class($itemVariable)
        );
        $this->storeVariableKeyValue($callIdItem, $itemVariable->getIdentifier(), $data);
    }

 /**
     * @param callId an item execution identifier
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
        $variables = $this->persistence->hGetAll(self::$keyPrefixCallId . $callId);
        foreach ($variables as $variableIdentifier=>$variableObservations){
            $observations = json_decode($variableObservations);
            foreach ($observations as $key=>$observation) {
                $observation->variable = unserialize($observation->variable);
            }


            $variables[$variableIdentifier] = $observations;
        }
//        common_Logger::w('test : '.print_r($variables,true));
        return $variables;
    }

    public function getVariable($callId, $variableIdentifier)
    {
        $observations = json_decode($this->persistence->hGet(self::$keyPrefixCallId . $callId, $variableIdentifier));
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

    public function getDeliveryExecution($deliveryResultIdentifier)
    {
        $delivery = $this->getDeliveryExecutionArray($deliveryResultIdentifier);
        return $delivery['executionIdentifier'];
    }

    public function getTestTakerArray($deliveryResultIdentifier)
    {
        return $this->persistence->hGetAll(self::$keyPrefixTestTaker . $deliveryResultIdentifier);
    }

    public function getDeliveryArray($deliveryResultIdentifier)
    {
        return $this->persistence->hGetAll(self::$keyPrefixDelivery . $deliveryResultIdentifier);
    }

    public function getDeliveryExecutionArray($deliveryResultIdentifier)
    {
        return $this->persistence->hGetAll(self::$keyPrefixDeliveryExecution . $deliveryResultIdentifier);
    }

    /**
     * @return array the list of item executions ids (across all results)
     * o(n) do not use real time (postprocessing)
     */

    public function getAllCallIds()
    {
        $keys = $this->persistence->keys(self::$keyPrefixCallId . '*');
        array_walk($keys, 'self::subStrPrefix', self::$keyPrefixCallId);
        return $keys;
    }
    /**
     * @return array each element is a two fields array deliveryResultIdentifier, testTakerIdentifier
     */
    public function getAllTestTakerIds()
    {
        $deliveryResults = array();
        $keys = $this->persistence->keys(self::$keyPrefixTestTaker . '*');
        array_walk($keys, 'self::subStrPrefix', self::$keyPrefixTestTaker);
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
        $keys = $this->persistence->keys(self::$keyPrefixDelivery . '*');
        array_walk($keys, 'self::subStrPrefix', self::$keyPrefixDelivery);
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
        $variableIds = explode('http://',$variableId);
        $variableId = "http://".$variableIds[2];
        $response = json_decode($this->persistence->hGet(self::$keyPrefixCallId.$variableId, "RESPONSE"));
        $variable = unserialize($response[0]->variable);

        $getter = 'get'.ucfirst($property);
        if(method_exists($variable, $getter)){
            return $variable->$getter();
        }
        return '';
    }

    /**
     * @todo Only works for QTI Tests, fix this in a more generic way
     * 
     * (non-PHPdoc)
     * @see \oat\taoResultServer\models\classes\ResultManagement::getRelatedItemCallIds()
     */
    public function getRelatedItemCallIds($deliveryResultIdentifier)
    {
        $keys = $this->persistence->keys(self::$keyPrefixCallId . $deliveryResultIdentifier . '.*');
        array_walk($keys, 'self::subStrPrefix', self::$keyPrefixCallId);
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
        $keys = $this->persistence->keys(self::$keyPrefixCallId . $deliveryResultIdentifier);
        array_walk($keys, 'self::subStrPrefix', self::$keyPrefixCallId);
        return $keys;
    }


    public function getResultByDelivery($delivery, $options = array())
    {

        $returnValue = array();
        $keys = $this->persistence->keys(self::$keyPrefixDelivery . '*');
        array_walk($keys, 'self::subStrPrefix', self::$keyPrefixDelivery);
        foreach ($keys as $key) {
            $deliveryResultId = $this->getDelivery($key);
            if(empty($delivery) || in_array($deliveryResultId, $delivery)){
                $returnValue[] = array(
                    "deliveryResultIdentifier" => $key,
                    "testTakerIdentifier" => $this->getTestTaker($key),
                    "deliveryIdentifier" => $deliveryResultId,
                    "deliveryExecutionIdentifier" => $this->getDeliveryExecution($key)
                );
            }
        }
        return $returnValue;
    }

    public function countResultByDelivery($delivery)
    {
        $count = 0;
        $keys = $this->persistence->keys(self::$keyPrefixDelivery . '*');
        array_walk($keys, 'self::subStrPrefix', self::$keyPrefixDelivery);
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
            $return = $return && $this->persistence->del(self::$keyPrefixCallId . $key);
        }
        foreach ($this->getRelatedItemCallIds($deliveryResultIdentifier) as $key) {
            $return = $return && $this->persistence->del(self::$keyPrefixCallId . $key);
        }
        $return = $return && $this->persistence->del(self::$keyPrefixDelivery . $deliveryResultIdentifier);
        $return = $return && $this->persistence->del(self::$keyPrefixDeliveryExecution . $deliveryResultIdentifier);
        $return = $return && $this->persistence->del(self::$keyPrefixTestTaker . $deliveryResultIdentifier);
        return $return;
    }
}
