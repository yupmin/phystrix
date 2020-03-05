<?php

/**
 * This file is a part of the Phystrix library
 *
 * Copyright 2013-2014 oDesk Corporation. All Rights Reserved.
 *
 * This file is licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Odesk\Phystrix;

use Laminas\Config\Config;

/**
 * Factory to keep track of and instantiate new circuit breakers when needed
 */
class CircuitBreakerFactory
{
    /**
     * @var array
     */
    protected $circuitBreakersByCommand = array();

    /**
     * @var StateStorageInterface
     */
    protected $stateStorage;

    /**
     * Constructor
     *
     * @param StateStorageInterface $stateStorage
     */
    public function __construct(StateStorageInterface $stateStorage)
    {
        $this->stateStorage = $stateStorage;
    }

    /**
     * Get circuit breaker instance by command key for given command config
     *
     * @param string $commandKey
     * @param Config $commandConfig
     * @param CommandMetrics $metrics
     * @return CircuitBreakerInterface
     */
    public function get($commandKey, Config $commandConfig, CommandMetrics $metrics)
    {
        if (!isset($this->circuitBreakersByCommand[$commandKey])) {
            $circuitBreakerConfig = $commandConfig->get('circuitBreaker');
            if ($circuitBreakerConfig->get('enabled')) {
                $this->circuitBreakersByCommand[$commandKey] =
                    new CircuitBreaker($commandKey, $metrics, $commandConfig, $this->stateStorage);
            } else {
                $this->circuitBreakersByCommand[$commandKey] = new NoOpCircuitBreaker();
            }
        }

        return $this->circuitBreakersByCommand[$commandKey];
    }
}
