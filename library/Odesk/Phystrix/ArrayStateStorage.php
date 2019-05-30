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

/**
 * Plain PHP array storage for Circuit Breaker metrics statistics
 *
 * This storage only works within the current request,
 * therefore can ONLY be used for testing purposes and in development environment
 */
class ArrayStateStorage implements StateStorageInterface
{
    /**
     * E.g. array('CommandKey' => array('success' => array(2 => 123))); where 2 is index and 123 is the metric value
     *
     * @var array
     */
    protected $buckets = array();

    /**
     * E.g. array('CommandKey' => 1234567) where 123456789 is the time in milliseconds when a single test is allowed
     *
     * @var array
     */
    protected $openCircuits = array();

    /**
     * Returns counter value for the given bucket
     *
     * @param string $commandKey
     * @param string $type
     * @param integer $index
     * @return integer
     */
    public function getBucket($commandKey, $type, $index)
    {
        return isset($this->buckets[$commandKey][$type][$index])
            ? $this->buckets[$commandKey][$type][$index]
            : null;
    }

    /**
     * Increments counter value for the given bucket
     *
     * @param string $commandKey
     * @param string $type
     * @param integer $index
     */
    public function incrementBucket($commandKey, $type, $index)
    {
        if (!isset($this->buckets[$commandKey][$type][$index])) {
            $this->buckets[$commandKey][$type][$index] = 1;
        } else {
            $this->buckets[$commandKey][$type][$index]++;
        }
    }

    /**
     * If the given bucket is found, sets counter value to 0.
     *
     * @param string $commandKey Circuit breaker key
     * @param string $type
     * @param integer $index
     */
    public function resetBucket($commandKey, $type, $index)
    {
        if (isset($this->buckets[$commandKey][$type][$index])) {
            $this->buckets[$commandKey][$type][$index] = 0;
        }
    }

    /**
     * Marks the given circuit  as open
     *
     * @param string $commandKey Circuit key
     * @param integer $sleepingWindowInMilliseconds In how much time we should allow a single test
     */
    public function openCircuit($commandKey, $sleepingWindowInMilliseconds)
    {
        $this->openCircuits[$commandKey] = $this->getTimeInMilliseconds() + $sleepingWindowInMilliseconds;
    }

    /**
     * Whether a single test is allowed
     *
     * @param string $commandKey Circuit breaker key
     * @param integer $sleepingWindowInMilliseconds In how much time we should allow the next single test
     * @return boolean
     */
    public function allowSingleTest($commandKey, $sleepingWindowInMilliseconds)
    {
        if (!isset($this->openCircuits[$commandKey])) {
            return true;
        } else {
            $allow = $this->openCircuits[$commandKey] < $this->getTimeInMilliseconds();
            $this->openCircuits[$commandKey] = $this->getTimeInMilliseconds() + $sleepingWindowInMilliseconds;
            return $allow;
        }
    }

    /**
     * Whether a circuit is open
     *
     * @param string $commandKey Circuit breaker key
     * @return boolean
     */
    public function isCircuitOpen($commandKey)
    {
        return isset($this->openCircuits[$commandKey]);
    }

    /**
     * Marks the given circuit as closed
     *
     * @param string $commandKey Circuit key
     */
    public function closeCircuit($commandKey)
    {
        if (isset($this->openCircuits[$commandKey])) {
            unset($this->openCircuits[$commandKey]);
        }
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
}
