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
 * APCU cache driven storage for Circuit Breaker metrics statistics
 */
class ApcuStateStorage implements StateStorageInterface
{
    public const BUCKET_EXPIRE_SECONDS = 120;

    public const CACHE_PREFIX = 'phystrix_cb_';

    public const OPENED_NAME = 'opened';

    public const SINGLE_TEST_BLOCKED = 'single_test_blocked';

    /**
     * Constructor
     * @throws Exception\ApcNotLoadedException
     */
    public function __construct()
    {
        if (!extension_loaded('apcu')) {
            throw new Exception\ApcNotLoadedException('"apcu" PHP extension is required for Phystrix to work');
        }
    }

    /**
     * Prepends cache prefix and filters out invalid characters
     *
     * @param string $name
     * @return string
     */
    protected function prefix($name)
    {
        return self::CACHE_PREFIX . $name;
    }

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
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        return apcu_fetch($bucketName);
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
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        if (!apcu_add($bucketName, 1, self::BUCKET_EXPIRE_SECONDS)) {
            apcu_inc($bucketName);
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
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        if (apcu_exists($bucketName)) {
            apcu_store($bucketName, 0, self::BUCKET_EXPIRE_SECONDS);
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
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        $singleTestFlagKey = $this->prefix($commandKey . self::SINGLE_TEST_BLOCKED);

        apcu_store($openedKey, true);
        // the single test blocked flag will expire automatically in $sleepingWindowInMilliseconds
        // thus allowing us a single test. Notice, APC doesn't allow us to use
        // expire time less than a second.
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);
        apcu_add($singleTestFlagKey, true, $sleepingWindowInSeconds);
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
        $singleTestFlagKey = $this->prefix($commandKey . self::SINGLE_TEST_BLOCKED);
        // using 'add' enforces thread safety.
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);
        // another APCU limitation is that within the current request variables will never expire.
        return (bool) apcu_add($singleTestFlagKey, true, $sleepingWindowInSeconds);
    }

    /**
     * Whether a circuit is open
     *
     * @param string $commandKey Circuit breaker key
     * @return boolean
     */
    public function isCircuitOpen($commandKey)
    {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        return (bool) apcu_fetch($openedKey);
    }

    /**
     * Marks the given circuit as closed
     *
     * @param string $commandKey Circuit key
     */
    public function closeCircuit($commandKey)
    {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        apcu_store($openedKey, false);
    }
}
