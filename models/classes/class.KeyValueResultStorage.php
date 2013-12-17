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
 * Implements tao results storage using Key Value Persistency
 *
 */

class taoLtiBasicOutcome_models_classes_KeyValueResultStorage
    extends tao_models_classes_GenerisService
    implements taoResultServer_models_classes_ResultStorage {

    private $persistence;
    /**
    * @param string deliveryResultIdentifier if no such deliveryResult with this identifier exists a new one gets created
    */
    public function __construct(){
		parent::__construct();
                common_ext_ExtensionsManager::singleton()->getExtensionById("KeyValueResultStorage");
        $this->sessionPersistence = common_persistence_Manager::getPersistence('ResultsKeyValueStorage');
    }
    /**
     * @param type $deliveryResultIdentifier lis_result_sourcedid
     * @param type $test ignored
     * @param taoResultServer_models_classes_Variable $testVariable
     * @param type $callIdTest ignored
     */
    public function storeTestVariable($deliveryResultIdentifier, $test, taoResultServer_models_classes_Variable $testVariable, $callIdTest){
       
        if (get_class($testVariable)=="taoResultServer_models_classes_OutcomeVariable") {
            
        }
       
    }
    /*
    * retrieve specific parameters from the resultserver to configure the storage
    */
    /*sic*/
    public function configure(core_kernel_classes_Resource $resultserver, $callOptions = array()) {
        /**
         * Retrieve the lti consumer associated with the result server in the KB , those rpoperties are available within taoLtiBasicComponent only
         */
       
        if (isset($callOptions["service_url"])) {
            $this->serviceUrl =  $callOptions["service_url"];
        } else {

            throw new common_Exception("LtiBasicOutcome Storage requires a call parameter service_url");
        }
        if (isset($callOptions["consumer_key"])) {
            $this->consumerKey =  $callOptions["consumer_key"];
        } else {
            throw new common_Exception("LtiBasicOutcome Storage requires a call parameter consumerKey");
        }

        common_Logger::i("ResultServer configured with ".$callOptions["service_url"]. " and ".$callOptions["consumer_key"]);
        
    }
     /**
     * In the case of An LtiBasic OutcomeSubmission, spawnResult has no effect
     */
    public function spawnResult(){
       //
    }
    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier) {
    }

    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier) {
    }

    public function storeItemVariable($deliveryResultIdentifier, $test, $item, taoResultServer_models_classes_Variable $itemVariable, $callIdItem){
            //for testing purpose
            common_Logger::i("Item Variable Submission: ".$itemVariable->getIdentifier() );
            $this->storeTestVariable($deliveryResultIdentifier, $test, $itemVariable, $callIdItem);
    }

}
?>