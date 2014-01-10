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
    *      Implements tao results storage using the configured persistency "taoAltResultStorage"
    *      
  *
    * 
    *  The storage is done on a callId basis (the key). retrieval of all variables pertainign to a callid 
    *  is done using  get or hget for aparticular variable 0(1)
    *  The jsondata contains all the observations recorderd with the variable data + context 
    *   
    *   callId => { 
    *   (field)variableIdentifier : json data,
    *   (field)variableIdentifier : json data,
    *   ...
    * }
    * 
    */

class taoAltResultStorage_models_classes_KeyValueResultStorage
    extends tao_models_classes_GenerisService
    implements taoResultServer_models_classes_WritableResultStorage,
        taoResultServer_models_classes_ReadableResultStorage {
    
    //prefixes used for keys
    static $keyPrefixCallId = 'callIdVariables';
    static $keyPrefixTestTaker = 'resultsTestTaker';
    static $keyPrefixDelivery = 'resultsDelivery';
    
    private $persistence;
    /**
    * @param string deliveryResultIdentifier if no such deliveryResult with this identifier exists a new one gets created
    */
    public function __construct(){
        parent::__construct();
        $this->persistence = $this->getPersistence();
        common_ext_ExtensionsManager::singleton()->getExtensionById("taoAltResultStorage");
    }

    private function getPersistence(){
        $persistence =  common_persistence_KeyValuePersistence::getPersistence('keyValueResult');
        //check that persistence is a correct Key VAlue persistence 
        return $persistence;
    }
    /**
     * 
     * @param type $callId
     * @param type $variableIdentifier
     * @param json $data the actual variable-value object, 
     */
    private function storeVariableKeyValue($callId, $variableIdentifier, $data){
        
        $callId = self::$keyPrefixCallId.$callId;
        /*seems to be the same complexity, worse if not yet value set for that key
         *   to be benchmarked against the general case only*/
        //Time complexity: O(1)
        $observed = $this->persistence->hExists($callId, $variableIdentifier);
        if (!($observed)) {
            //Time complexity: O(1)
            $this->persistence->hSet($callId, $variableIdentifier, json_encode($data));
        } else {
            //Time complexity: O(1)
            $variableObservations = json_decode($this->persistence->hGet($callId, $variableIdentifier));
            if (is_array($variableObservations)) {
            $variableObservations[] = $data;
            } else {
            $variableObservations = array($data);
            }
            //Time complexity: O(1)
            $this->persistence->hSet($callId, $variableIdentifier, json_encode($variableObservations));
        }
       
    }
    public function getVariables($callId) {
        return $this->persistence->hGetAll(self::$keyPrefixCallId.$callId);
    }
    public function getVariable($callId, $variableIdentifier) {
        return $this->persistence->hGet(self::$keyPrefixCallId.$callId, $variableIdentifier );
    }
    
    public function getTestTaker($deliveryResultIdentifier) {
        return $this->persistence->hGetAll(self::$keyPrefixTestTaker.$deliveryResultIdentifier);
    }
    public function getDelivery($deliveryResultIdentifier) {
        return $this->persistence->hGetAll(self::$keyPrefixDelivery.$deliveryResultIdentifier);
    }
    
     public function getDeliveryResultIdentifiers(){}


    /**
     * @return taoResultServer_models_classes_Variable $variable
     * 
     */
    public function getItemVariables($deliveryResultIdentifier) {}
    

        public function getTestVariables($deliveryResultIdentifier){}
    
    
    
    
      /**
      * Ids must be delegated on key value persistency as we may want to load balance and keep unique identifier
     */
    public function spawnResult(){
        return $this->persistence->incr("resultsIdentifier");
        
    }   
    
    /**
     * @param type $deliveryResultIdentifier lis_result_sourcedid
     * @param type $test ignored
     * @param taoResultServer_models_classes_Variable $testVariable
     * @param type $callIdTest ignored
     */
    public function storeTestVariable($deliveryResultIdentifier, $test, taoResultServer_models_classes_Variable $testVariable, $callIdTest){
       
         $data = array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "test"  => $test,
            "item"  => null,
            "variable"  => $testVariable->toJson(),
            "callIdItem"    => $callIdTest
        );
        $this->storeVariableKeyValue($callIdItem, $itemVariable->getIdentifier(), $data);
    }
    /*
    * retrieve specific parameters from the resultserver to configure the storage
    */
    /*sic*/
    public function configure(core_kernel_classes_Resource $resultserver, $callOptions = array()) {
    }
   
    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier) {
        $this->persistence->hmSet(self::$keyPrefixTestTaker.$deliveryResultIdentifier, array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "testTakerIdentifier" => $testTakerIdentifier,
        ));
    }

    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier) {
         $this->persistence->hmSet(self::$keyPrefixDelivery.$deliveryResultIdentifier, array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "deliveryIdentifier" => $deliveryIdentifier,
        ));
    }

    public function storeItemVariable($deliveryResultIdentifier, $test, $item, taoResultServer_models_classes_Variable $itemVariable, $callIdItem){
           
        $data = array(
            "deliveryResultIdentifier" => $deliveryResultIdentifier,
            "test"  => $test,
            "item"  => $item,
            "variable"  => $itemVariable->toJson(),
            "callIdItem"    => $callIdItem
        );
        $this->storeVariableKeyValue($callIdItem, $itemVariable->getIdentifier(), $data);
           
    }

}
?>