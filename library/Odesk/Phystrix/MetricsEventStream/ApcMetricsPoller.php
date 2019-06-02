<?php
/**
 * This file is a part of the phystrix-dashboard package
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
namespace Odesk\Phystrix\MetricsEventStream;

use APCIterator;
use Odesk\Phystrix\ApcStateStorage;
use Odesk\Phystrix\MetricsCounter;
use RuntimeException;
use Zend\Config\Config;

/**
 * The class attempts to find counters for all commands currently running
 * (within the rolling statistical window)
 */
class ApcMetricsPoller implements MetricsPollerInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $configsPerCommandKey = array();

    /**
     * @var \Odesk\Phystrix\CommandMetricsFactory
     */
    protected $commandMetricsFactory;

    /**
     * @var \Odesk\Phystrix\CircuitBreakerFactory
     */
    protected $circuitBreakerFactory;

    /**
     * Constructor
     *
     * @param Config $config Final Phystrix configuration used for command factory
     * @throws \Odesk\Phystrix\Exception\ApcNotLoadedException
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        $stateStorage = new \Odesk\Phystrix\ApcStateStorage();
        $this->commandMetricsFactory = new \Odesk\Phystrix\CommandMetricsFactory($stateStorage);
        $this->circuitBreakerFactory = new \Odesk\Phystrix\CircuitBreakerFactory($stateStorage);
    }

    /**
     * Gets the config for given command key
     *
     * @param string $commandKey
     * @return Config
     */
    protected function getCommandConfig($commandKey)
    {
        if (isset($this->configsPerCommandKey[$commandKey])) {
            return $this->configsPerCommandKey[$commandKey];
        }
        $config = new Config($this->config->get('default')->toArray(), true);
        if ($this->config->__isset($commandKey)) {
            $commandConfig = $this->config->get($commandKey);
            $config->merge($commandConfig);
        }
        $this->configsPerCommandKey[$commandKey] = $config;
        return $config;
    }

    /**
     * Finds all commands currently running (having any metrics recorded within the statistical rolling window).
     *
     * @return array
     * @throws RuntimeException When entry with invalid name found in cache
     */
    protected function getCommandsRunning()
    {
        $commandKeys = array();
        foreach (new APCIterator('user', '/^' . ApcStateStorage::CACHE_PREFIX . '/') as $counter) {
            // APC entries do not expire within one request context so we have to check manually:
            if ($counter['creation_time'] + $counter['ttl'] < time()) {
                continue;
            }
            $expressionToExtractCommandKey = '/^' . ApcStateStorage::CACHE_PREFIX . '(.*)_(?:.*)_(?:[0-9]+)$/';
            preg_match($expressionToExtractCommandKey, $counter['key'], $match);
            if (!isset($match[1])) {
                continue;
//                throw new RuntimeException('Invalid counter key: ' . $counter['key']);
            }
            $commandKey = $match[1];
            if (!in_array($commandKey, $commandKeys)) {
                // as we store APC entries longer than statistical window, we need to check manually if it's in it
                $statisticalWindowInSeconds = $this->getCommandConfig($commandKey)->get('metrics')
                    ->get('rollingStatisticalWindowInMilliseconds') / 1000;
                if ($statisticalWindowInSeconds + $counter['creation_time'] >= time()) {
                    $commandKeys[] = $commandKey;
                }
            }
        }
        return $commandKeys;
    }

    /**
     * Returns current time on the server in milliseconds
     *
     * @return float
     */
    private function getTimeInMilliseconds()
    {
        return floor(microtime(true) * 1000);
    }

    /**
     * Finds all commands currently running (having any metrics recorded within the statistical rolling window).
     * For each command key prepares a set of statistic. Returns all sets.
     *
     * @return array
     */
    public function getStatsForCommandsRunning()
    {
        $stats = array();
        $commandKeys = $this->getCommandsRunning();
        foreach ($commandKeys as $commandKey) {
            $commandConfig = $this->getCommandConfig($commandKey);
            $commandMetrics = $this->commandMetricsFactory->get($commandKey, $commandConfig);
            $circuitBreaker = $this->circuitBreakerFactory->get($commandKey, $commandConfig, $commandMetrics);
            $healtCounts = $commandMetrics->getHealthCounts();
            $commandStats = array(
                // We have to use "HystrixCommand" exactly, otherwise it won't work
                'type' => 'HystrixCommand',
                'name' => $commandKey,
                'group' => $commandKey, // unused in Phystrix
                'currentTime' => $this->getTimeInMilliseconds(),

                'isCircuitBreakerOpen' => $circuitBreaker->isOpen(),

                'errorPercentage' => $healtCounts->getErrorPercentage(),
                'errorCount' => $healtCounts->getFailure(),
                'requestCount' => $healtCounts->getTotal(),

                'rollingCountCollapsedRequests' => 0, // unused in Phystrix
                'rollingCountExceptionsThrown' => $commandMetrics->getRollingCount(MetricsCounter::EXCEPTION_THROWN),
                'rollingCountFailure' => $commandMetrics->getRollingCount(MetricsCounter::FAILURE),
                'rollingCountFallbackFailure' => $commandMetrics->getRollingCount(MetricsCounter::FALLBACK_FAILURE),
                'rollingCountFallbackRejection' => 0, // unused in Phystrix (concurrency thing)
                'rollingCountFallbackSuccess' => $commandMetrics->getRollingCount(MetricsCounter::FALLBACK_SUCCESS),
                'rollingCountResponsesFromCache' =>
                    $commandMetrics->getRollingCount(MetricsCounter::RESPONSE_FROM_CACHE),
                'rollingCountSemaphoreRejected' => 0, // unused in Phystrix
                'rollingCountShortCircuited' => $commandMetrics->getRollingCount(MetricsCounter::SHORT_CIRCUITED),
                'rollingCountSuccess' => $commandMetrics->getRollingCount(MetricsCounter::SUCCESS),
                'rollingCountThreadPoolRejected' => 0, // unused in Phystrix
                'rollingCountTimeout' => 0, // unused in Phystrix.

                'currentConcurrentExecutionCount' => 0,

                // Latency is not tracked in phystrix
                'latencyExecute_mean' => 0,
                'latencyExecute' => array('0' => 0, '25' => 0, '50' => 0, '75' => 0, '90' => 0, '95' => 0, '99' => 0,
                    '99.5' => 0, '100' => 0),
                'latencyTotal_mean' => 15,
                'latencyTotal' => array('0' => 0, '25' => 0, '50' => 0, '75' => 0, '90' => 0, '95' => 0, '99' => 0,
                    '99.5' => 0, '100' => 0),

                'propertyValue_circuitBreakerRequestVolumeThreshold' =>
                    $commandConfig->get('circuitBreaker')->get('requestVolumeThreshold'),
                'propertyValue_circuitBreakerSleepWindowInMilliseconds' =>
                    $commandConfig->get('circuitBreaker')->get('sleepWindowInMilliseconds'),
                'propertyValue_circuitBreakerErrorThresholdPercentage' =>
                    $commandConfig->get('circuitBreaker')->get('errorThresholdPercentage'),
                'propertyValue_circuitBreakerForceOpen' => $commandConfig->get('circuitBreaker')->get('forceOpen'),
                'propertyValue_circuitBreakerForceClosed' => $commandConfig->get('circuitBreaker')->get('forceClosed'),
                'propertyValue_circuitBreakerEnabled' => $commandConfig->get('circuitBreaker')->get('enabled'),

                'propertyValue_executionIsolationStrategy' => 'THREAD', // unused in Phystrix
                'propertyValue_executionIsolationThreadTimeoutInMilliseconds' => 0, // unused in Phystrix
                'propertyValue_executionIsolationThreadInterruptOnTimeout' => false, // unused in Phystrix
                'propertyValue_executionIsolationThreadPoolKeyOverride' => 'null', // unused in Phystrix
                'propertyValue_executionIsolationSemaphoreMaxConcurrentRequests' => 0, // unused in Phystrix
                'propertyValue_fallbackIsolationSemaphoreMaxConcurrentRequests' => 0, // unused in Phystrix

                'propertyValue_metricsRollingStatisticalWindowInMilliseconds' =>
                    $commandConfig->get('metrics')->get('rollingStatisticalWindowInMilliseconds'),

                'propertyValue_requestCacheEnabled' => $commandConfig->get('requestCache')->get('enabled'),
                'propertyValue_requestLogEnabled' => $commandConfig->get('requestLog')->get('enabled'),

                'reportingHosts' => 1,
            );

            $stats[] = $commandStats;
        }
        return $stats;
    }
}
