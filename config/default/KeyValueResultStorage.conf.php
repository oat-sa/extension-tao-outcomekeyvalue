<?php
/**
 * Default config header
 *
 * To replace this add a file /home/bout/code/php/taoTrunk/taoResultServer/config/header/default_resultserver.conf.php
 */

return new \taoAltResultStorage_models_classes_KeyValueResultStorage([
    \taoAltResultStorage_models_classes_KeyValueResultStorage::OPTION_PERSISTENCE => 'keyValueResult'
]);
