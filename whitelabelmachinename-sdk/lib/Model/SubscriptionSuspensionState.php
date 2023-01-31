<?php
/**
 * WhiteLabelName SDK
 *
 * This library allows to interact with the WhiteLabelName payment service.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


namespace WhiteLabelMachineName\Sdk\Model;
use \WhiteLabelMachineName\Sdk\ObjectSerializer;

/**
 * SubscriptionSuspensionState model
 *
 * @category    Class
 * @description 
 * @package     WhiteLabelMachineName\Sdk
 * @author      customweb GmbH
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 */
class SubscriptionSuspensionState
{
    /**
     * Possible values of this enum
     */
    const RUNNING = 'RUNNING';
    const ENDED = 'ENDED';
    
    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::RUNNING,
            self::ENDED,
        ];
    }
}

