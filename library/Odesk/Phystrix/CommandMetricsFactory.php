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
 * Factory to keep track of and instantiate new command metrics objects when needed
 */
class CommandMetricsFactory
{
    /**
     * @var array
     */
    protected $commandMetricsByCommand = array();

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
     * Get command metrics instance by command key for given command config
     *
     * @param string $commandKey
     * @param Config $commandConfig
     * @return CommandMetrics
     */
    public function get($commandKey, Config $commandConfig)
    {
        if (!isset($this->commandMetricsByCommand[$commandKey])) {
            $metricsConfig = $commandConfig->get('metrics');
            $statisticalWindow = $metricsConfig->get('rollingStatisticalWindowInMilliseconds');
            $windowBuckets = $metricsConfig->get('rollingStatisticalWindowBuckets');
            $snapshotInterval = $metricsConfig->get('healthSnapshotIntervalInMilliseconds');

            $counter = new MetricsCounter($commandKey, $this->stateStorage, $statisticalWindow, $windowBuckets);
            $this->commandMetricsByCommand[$commandKey] = new CommandMetrics($counter, $snapshotInterval);
        }

        return $this->commandMetricsByCommand[$commandKey];
    }
}
